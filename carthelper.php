<?php
/**
 * Cart helper functions
 * 
 * This file contains functions related to shopping cart operations
 * with database persistence support
 */

/**
 * Syncs session cart with database cart when user logs in
 *
 * @param int $user_id The user ID
 * @param object $conn Database connection
 * @return void
 */
function sync_cart_on_login($user_id, $conn) {
    // Load any items from database to session
    load_cart_from_database($user_id, $conn);
    
    // If user had items in session before login, save those to database
    if (isset($_SESSION['temp_cart']) && !empty($_SESSION['temp_cart'])) {
        foreach ($_SESSION['temp_cart'] as $item) {
            if (isset($item['type']) && $item['type'] === 'giftcard') {
                add_giftcard_to_db($user_id, $item, $conn);
            } else {
                add_book_to_db($user_id, $item, $conn);
            }
        }
        // Clear temporary cart
        unset($_SESSION['temp_cart']);
    }
}

/**
 * Loads cart items from database into session
 *
 * @param int $user_id The user ID
 * @param object $conn Database connection
 * @return void
 */
function load_cart_from_database($user_id, $conn) {
    if (!$user_id) return;
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }
    
    $stmt = $conn->prepare("SELECT * FROM user_cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Clear current cart to prevent duplicates
    $_SESSION['cart'] = array();
    
    while ($row = $result->fetch_assoc()) {
        if ($row['item_type'] === 'giftcard') {
            $_SESSION['cart'][] = [
                'id' => $row['item_id'],
                'type' => 'giftcard',
                'recipient_email' => $row['recipient_email'],
                'sender_email' => $row['sender_email'],
                'value' => $row['value'],
                'message' => $row['message'],
                'price' => $row['price'],
                'quantity' => $row['quantity']
            ];
        } else {
            $_SESSION['cart'][] = [
                'id' => $row['item_id'],
                'type' => 'book',
                'title' => $row['title'],
                'author' => $row['author'],
                'price' => $row['price'],
                'image' => $row['image'],
                'quantity' => $row['quantity']
            ];
        }
    }
}

/**
 * Add book to database cart
 *
 * @param int $user_id User ID
 * @param array $item Book item details
 * @param object $conn Database connection
 * @return bool Success status
 */
function add_book_to_db($user_id, $item, $conn) {
    if (!$user_id) return false;
    
    $book_id = $item['id'];
    $title = $item['title'];
    $author = $item['author'];
    $price = $item['price'];
    $image = $item['image'];
    $quantity = $item['quantity'];
    $item_type = 'book';
    
    // Check if item already exists in user's cart
    $check_stmt = $conn->prepare("SELECT quantity FROM user_cart WHERE user_id = ? AND item_id = ? AND item_type = ?");
    $check_stmt->bind_param("iss", $user_id, $book_id, $item_type);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing item
        $row = $result->fetch_assoc();
        $new_quantity = $row['quantity'] + $quantity;
        
        $update_stmt = $conn->prepare("UPDATE user_cart SET quantity = ? WHERE user_id = ? AND item_id = ? AND item_type = ?");
        $update_stmt->bind_param("iiss", $new_quantity, $user_id, $book_id, $item_type);
        return $update_stmt->execute();
    } else {
        // Insert new item
        $insert_stmt = $conn->prepare("INSERT INTO user_cart (user_id, item_id, item_type, title, author, price, image, quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("issssdsi", $user_id, $book_id, $item_type, $title, $author, $price, $image, $quantity);
        return $insert_stmt->execute();
    }
}

/**
 * Add gift card to database cart
 *
 * @param int $user_id User ID
 * @param array $item Gift card item details
 * @param object $conn Database connection
 * @return bool Success status
 */
function add_giftcard_to_db($user_id, $item, $conn) {
    if (!$user_id) return false;
    
    $giftcard_id = $item['id'];
    $value = $item['value'];
    $price = $item['price'];
    $recipient_email = $item['recipient_email'];
    $sender_email = $item['sender_email'];
    $message = isset($item['message']) ? $item['message'] : '';
    $quantity = 1; // Gift cards always have quantity of 1
    $item_type = 'giftcard';
    
    // For gift cards, we always insert a new record as each is unique
    $insert_stmt = $conn->prepare("INSERT INTO user_cart (user_id, item_id, item_type, price, quantity, value, recipient_email, sender_email, message) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insert_stmt->bind_param("issdiisss", $user_id, $giftcard_id, $item_type, $price, $quantity, $value, $recipient_email, $sender_email, $message);
    return $insert_stmt->execute();
}

/**
 * Remove item from database cart
 *
 * @param int $user_id User ID
 * @param string $item_id Item ID to remove
 * @param string $item_type Type of item ('book' or 'giftcard')
 * @param object $conn Database connection
 * @return bool Success status
 */
function remove_item_from_db($user_id, $item_id, $item_type, $conn) {
    if (!$user_id) return false;
    
    $stmt = $conn->prepare("DELETE FROM user_cart WHERE user_id = ? AND item_id = ? AND item_type = ?");
    $stmt->bind_param("iss", $user_id, $item_id, $item_type);
    return $stmt->execute();
}

/**
 * Update quantity of item in database cart
 *
 * @param int $user_id User ID
 * @param string $item_id Item ID to update
 * @param string $item_type Type of item ('book' or 'giftcard')
 * @param int $quantity New quantity
 * @param object $conn Database connection
 * @return bool Success status
 */
function update_quantity_in_db($user_id, $item_id, $item_type, $quantity, $conn) {
    if (!$user_id) return false;
    
    if ($quantity <= 0) {
        return remove_item_from_db($user_id, $item_id, $item_type, $conn);
    }
    
    $stmt = $conn->prepare("UPDATE user_cart SET quantity = ? WHERE user_id = ? AND item_id = ? AND item_type = ?");
    $stmt->bind_param("iiss", $quantity, $user_id, $item_id, $item_type);
    return $stmt->execute();
}

/**
 * Clear all items from database cart for a user
 *
 * @param int $user_id User ID
 * @param object $conn Database connection
 * @return bool Success status
 */
function clear_db_cart($user_id, $conn) {
    if (!$user_id) return false;
    
    $stmt = $conn->prepare("DELETE FROM user_cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    return $stmt->execute();
}



/**
 * Add an item to the cart (both session and database if logged in)
 *
 * @param array $item Item details 
 * @param int $quantity Quantity to add (default: 1)
 * @param object $conn Database connection (optional)
 * @return array Response with success status and message
 */
function add_to_cart($item, $quantity = 1, $conn = null) {
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }
    
    // Add to session cart
    if (isset($item['type']) && $item['type'] === 'giftcard') {
        // Gift cards are always added as new items
        $_SESSION['cart'][] = $item;
    } else {
        // For books, check if already in cart
        $found = false;
        foreach ($_SESSION['cart'] as &$cart_item) {
            if ($cart_item['id'] == $item['id'] && (!isset($cart_item['type']) || $cart_item['type'] === 'book')) {
                $cart_item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $_SESSION['cart'][] = $item;
        }
    }
    
    // If user is logged in, also add to database
    if (isset($_SESSION['user_id']) && $conn) {
        $user_id = $_SESSION['user_id'];
        if (isset($item['type']) && $item['type'] === 'giftcard') {
            add_giftcard_to_db($user_id, $item, $conn);
        } else {
            add_book_to_db($user_id, $item, $conn);
        }
    } else if (!isset($_SESSION['user_id'])) {
        // Store in temporary cart for later sync
        if (!isset($_SESSION['temp_cart'])) {
            $_SESSION['temp_cart'] = array();
        }
        $_SESSION['temp_cart'][] = $item;
    }
    
    return array(
        'success' => true,
        'message' => 'Item added to cart successfully',
        'cart_count' => get_cart_count()
    );
}

/**
 * Remove an item from the cart
 *
 * @param int $index Index in the cart array
 * @param object $conn Database connection (optional)
 * @return array Response with success status and message
 */
function remove_from_cart($index, $conn = null) {
    if (isset($_SESSION['cart'][$index])) {
        $item = $_SESSION['cart'][$index];
        
        // If user is logged in, also remove from database
        if (isset($_SESSION['user_id']) && $conn) {
            $user_id = $_SESSION['user_id'];
            $item_id = $item['id'];
            $item_type = isset($item['type']) ? $item['type'] : 'book';
            remove_item_from_db($user_id, $item_id, $item_type, $conn);
        }
        
        // Remove from session
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index array
        
        return array(
            'success' => true,
            'message' => 'Item removed from cart',
            'cart_count' => get_cart_count()
        );
    }
    
    return array(
        'success' => false,
        'message' => 'Item not found in cart',
        'cart_count' => get_cart_count()
    );
}

/**
 * Update the quantity of an item in the cart
 *
 * @param int $index Index in the cart array
 * @param int $change Change in quantity (positive or negative)
 * @param object $conn Database connection (optional)
 * @return array Response with success status and message
 */
function update_cart_quantity($index, $change, $conn = null) {
    if (isset($_SESSION['cart'][$index])) {
        $current_qty = $_SESSION['cart'][$index]['quantity'];
        $new_qty = $current_qty + $change;
        
        if ($new_qty <= 0) {
            return remove_from_cart($index, $conn);
        }
        
        $_SESSION['cart'][$index]['quantity'] = $new_qty;
        
        // If user is logged in, also update in database
        if (isset($_SESSION['user_id']) && $conn) {
            $user_id = $_SESSION['user_id'];
            $item = $_SESSION['cart'][$index];
            $item_id = $item['id'];
            $item_type = isset($item['type']) ? $item['type'] : 'book';
            update_quantity_in_db($user_id, $item_id, $item_type, $new_qty, $conn);
        }
        
        return array(
            'success' => true,
            'message' => 'Cart updated successfully',
            'cart_count' => get_cart_count()
        );
    }
    
    return array(
        'success' => false,
        'message' => 'Item not found in cart',
        'cart_count' => get_cart_count()
    );
}

/**
 * Get the total number of items in the cart
 *
 * @return int Total number of items
 */
function get_cart_count() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return 0;
    }
    
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += isset($item['quantity']) ? $item['quantity'] : 1;
    }
    
    return $count;
}

/**
 * Calculate the total price of items in the cart
 *
 * @return float Total price
 */
function get_cart_total() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return 0;
    }
    
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $quantity = isset($item['quantity']) ? $item['quantity'] : 1;
        $total += $item['price'] * $quantity;
    }
    
    return $total;
}

/**
 * Clear all items from the cart
 *
 * @param object $conn Database connection (optional) 
 * @return array Response with success status and message
 */
function clear_cart($conn = null) {
    $_SESSION['cart'] = array();
    
    // If user is logged in, also clear database cart
    if (isset($_SESSION['user_id']) && $conn) {
        $user_id = $_SESSION['user_id'];
        clear_db_cart($user_id, $conn);
    }
    
    return array(
        'success' => true,
        'message' => 'Cart cleared successfully',
        'cart_count' => 0
    );
}
/**
 * Function to load a user's cart from database after login
 * Add this to your login processing script after successful authentication
 * 
 * @param int $user_id The logged-in user's ID
 * @param mysqli $conn Database connection
 */
function load_user_cart_from_db($user_id, $conn) {
    // Initialize cart if not set
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Query user's cart items from database
    $stmt = $conn->prepare("
        SELECT uc.item_id, uc.quantity, b.title, b.author, b.price, b.image_link 
        FROM user_cart uc
        JOIN books b ON uc.item_id = b.id
        WHERE uc.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Create a new cart array to replace the session cart
        $db_cart = [];
        
        while ($row = $result->fetch_assoc()) {
            $db_cart[] = [
                'id' => $row['item_id'],
                'title' => $row['title'],
                'author' => $row['author'],
                'price' => $row['price'],
                'image' => $row['image_link'],
                'quantity' => $row['quantity']
            ];
        }
        
        // Replace session cart with database cart
        $_SESSION['cart'] = $db_cart;
    }
    
    $stmt->close();
}
function merge_carts_on_login($user_id, $conn) {
    // Check if there are items in the session cart before login
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        $session_cart = $_SESSION['cart'];
        
        // Process each item in the session cart
        foreach ($session_cart as $item) {
            $item_id = $item['id'];
            $quantity = $item['quantity'] ?? 1;
            
            // Check if this item exists in the user's database cart
            $stmt = $conn->prepare("SELECT quantity FROM user_cart WHERE user_id = ? AND item_id = ?");
            $stmt->bind_param("ii", $user_id, $item_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Item exists in database - update quantity
                $row = $result->fetch_assoc();
                $new_quantity = $row['quantity'] + $quantity;
                
                $update_stmt = $conn->prepare("UPDATE user_cart SET quantity = ? WHERE user_id = ? AND item_id = ?");
                $update_stmt->bind_param("iii", $new_quantity, $user_id, $item_id);
                $update_stmt->execute();
            } else {
                // Item doesn't exist in database - insert it
                $insert_stmt = $conn->prepare("INSERT INTO user_cart (user_id, item_id, quantity) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("iii", $user_id, $item_id, $quantity);
                $insert_stmt->execute();
            }
        }
    }
    
    // Now load the complete cart from database
    load_user_cart_from_db($user_id, $conn);
}
function initialize_cart($conn) {
    // Check if user is logged in
    if (isset($_SESSION['user_id'])) {
        // Get user ID
        $user_id = $_SESSION['user_id'];
        
        // Check if we need to load cart from database
        if (!isset($_SESSION['cart_loaded']) || $_SESSION['cart_loaded'] !== true) {
            // Load cart from database
            load_user_cart_from_db($user_id, $conn);
            
            // Mark cart as loaded
            $_SESSION['cart_loaded'] = true;
        }
    } else {
        // Not logged in - ensure cart array exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }
}