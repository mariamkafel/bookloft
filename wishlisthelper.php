<?php
/**
 * Helper function to get a user's wishlist items
 * 
 * @param mysqli $conn Database connection
 * @param int|null $user_id User ID
 * @return array Array of wishlist items
 */
function get_user_wishlist($conn, $user_id) {
    $items = [];
    
    if (!$user_id) {
        return $items;
    }
    
    $stmt = $conn->prepare("SELECT w.id, w.book_id, w.added_at, b.title, b.author, b.price, b.image_link as imageLink 
                           FROM wishlist w 
                           JOIN books b ON w.book_id = b.id 
                           WHERE w.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    return $items;
}

/**
 * Helper function to get simplified wishlist data (only IDs)
 * 
 * @param mysqli $conn Database connection
 * @param int|null $user_id User ID
 * @return array Array of wishlist items with minimal data
 */
function get_user_wishlist_simple($conn, $user_id) {
    $items = [];
    
    if (!$user_id) {
        return $items;
    }
    
    $stmt = $conn->prepare("SELECT w.id, w.book_id FROM wishlist w WHERE w.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    return $items;
}

/**
 * Helper function to check if a book is in user's wishlist
 * 
 * @param mysqli $conn Database connection
 * @param int|null $user_id User ID
 * @param int $book_id Book ID
 * @return bool True if book is in wishlist, false otherwise
 */
function is_book_in_wishlist($conn, $user_id, $book_id) {
    if (!$user_id) {
        return false;
    }
    
    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND book_id = ? LIMIT 1");
    $stmt->bind_param("ii", $user_id, $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $in_wishlist = ($result->num_rows > 0);
    $stmt->close();
    
    return $in_wishlist;
}

/**
 * Helper function to check if a book is in user's wishlist
 * Alternative implementation using num_rows
 * 
 * @param mysqli $conn Database connection
 * @param int|null $user_id User ID
 * @param int $book_id Book ID
 * @return bool True if book is in wishlist, false otherwise
 */
function check_if_in_wishlist($conn, $user_id, $book_id) {
    if (!$user_id) return false;
    
    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND book_id = ?");
    $stmt->bind_param("ii", $user_id, $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}
/**
 * Add a book to a user's wishlist
 *
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param int $book_id Book ID
 * @return array Response with success status and message
 */
function add_to_wishlist($conn, $user_id, $book_id) {
    if (!$user_id) {
        return array(
            'success' => false,
            'message' => 'login_required'
        );
    }
    
    // Check if already in wishlist
    if (check_if_in_wishlist($conn, $user_id, $book_id)) {
        return array(
            'success' => false,
            'message' => 'Book is already in your wishlist'
        );
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO wishlist (user_id, book_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $book_id);
        $success = $stmt->execute();
        $stmt->close();
        
        if ($success) {
            $count = count_wishlist_items($conn, $user_id);
            return array(
                'success' => true,
                'message' => 'Book added to wishlist successfully',
                'wishlist_count' => $count
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to add book to wishlist'
            );
        }
    } catch (Exception $e) {
        error_log("Error adding to wishlist: " . $e->getMessage());
        return array(
            'success' => false,
            'message' => 'Database error occurred'
        );
    }
}

/**
 * Remove a book from a user's wishlist
 *
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param int $book_id Book ID
 * @return array Response with success status and message
 */
function remove_from_wishlist($conn, $user_id, $book_id) {
    if (!$user_id) {
        return array(
            'success' => false,
            'message' => 'login_required'
        );
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND book_id = ?");
        $stmt->bind_param("ii", $user_id, $book_id);
        $success = $stmt->execute();
        $stmt->close();
        
        if ($success) {
            $count = count_wishlist_items($conn, $user_id);
            return array(
                'success' => true,
                'message' => 'Book removed from wishlist successfully',
                'wishlist_count' => $count
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to remove book from wishlist'
            );
        }
    } catch (Exception $e) {
        error_log("Error removing from wishlist: " . $e->getMessage());
        return array(
            'success' => false,
            'message' => 'Database error occurred'
        );
    }
}

/**
 * Count the number of items in a user's wishlist
 *
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return int Number of wishlist items
 */
function count_wishlist_items($conn, $user_id) {
    if (!$user_id) {
        return 0;
    }
    
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = $row['count'];
        $stmt->close();
        
        return $count;
    } catch (Exception $e) {
        error_log("Error counting wishlist: " . $e->getMessage());
        return 0;
    }
}

/**
 * Process wishlist action from POST data
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return array|null Response if action was handled, null otherwise
 */
function handle_wishlist_action($conn, $user_id) {
    // Check for CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
        $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        return array(
            'success' => false,
            'message' => 'Invalid request'
        );
    }
    
    if (isset($_POST['action']) && isset($_POST['book_id'])) {
        $book_id = (int)$_POST['book_id'];
        $action = $_POST['action'];
        
        if ($action === 'add') {
            return add_to_wishlist($conn, $user_id, $book_id);
        } elseif ($action === 'remove') {
            return remove_from_wishlist($conn, $user_id, $book_id);
        }
    }
    
    return null;
}