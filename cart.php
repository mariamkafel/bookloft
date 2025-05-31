<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'carthelper.php';
// Helper functions
function is_logged_in() {
    return isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
}
// Get cart count
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += isset($item['quantity']) ? $item['quantity'] : 1;
    }
}
function check_if_in_wishlist($conn, $user_id, $book_id) {
    if (!$user_id) return false;
    
    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND book_id = ?");
    $stmt->bind_param("ii", $user_id, $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

// Get user's wishlist items
function get_user_wishlist($conn, $user_id) {
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

// CSRF protection - generate token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if cart exists in session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get the user's wishlist items
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

$wishlist_items = [];
if ($user_id) {
    $wishlist_items = get_user_wishlist($conn, $user_id);
}

// Handle AJAX requests first, before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Get parameters from ajax request
        $item_id = isset($_POST['item_id']) ? $_POST['item_id'] : null;
        $change = isset($_POST['change']) ? intval($_POST['change']) : 0;
        $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
        
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            exit;
        }

        $response = ['success' => false];

        // Validate parameters
        if ($item_id !== null && $change !== 0 && $index >= 0 && isset($_SESSION['cart'][$index])) {
            // Update quantity
            $current_qty = $_SESSION['cart'][$index]['quantity'];
            $new_qty = $current_qty + $change;
            
            // Remove item if quantity becomes 0 or less
            if ($new_qty <= 0) {
                unset($_SESSION['cart'][$index]);
                $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index array
            } else {
                $_SESSION['cart'][$index]['quantity'] = $new_qty;
            }
            
            $response = ['success' => true];
        }

        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode($response);
        exit; // Stop execution after handling AJAX
    }
}
initialize_cart($conn);
// Calculate cart totals
$subtotal = 0;
if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
    foreach($_SESSION['cart'] as $item) {
        $itemPrice = isset($item['price']) ? $item['price'] : 0;
        $itemQuantity = isset($item['quantity']) ? $item['quantity'] : 1;
        $subtotal += $itemPrice * $itemQuantity;
    }
    $tax = $subtotal * 0.1;
    $total = $subtotal + $tax;
} else {
    $subtotal = 0;
    $tax = 0;
    $total = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - The Book Loft</title>
    <link rel="stylesheet" href="css/cart.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/main.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="css/nav.css">
</head>
<body>
    <!-- Add CSRF token hidden input -->
    <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    
    <header>
        <nav class="nav" aria-label="Main Navigation">
            <div class="nav-left">
                <a href="index.php"><img src="imgs/logo2.png" class="img-style" alt="The Book Loft Logo"></a>
                <p>THE BOOK LOFT</p>
            </div>
            <input type="checkbox" id="menu-toggle" aria-hidden="true">
            <label for="menu-toggle" class="hamburger" aria-label="Toggle menu">&#9776;</label>
            <div class="nav-right">
                <a href="/bookstore/books.php">Books</a>
                <a href="/bookstore/first.php">EBooks</a>
                <a href="/bookstore/giftcard.php">Gift Cards</a>
                <a href="/bookstore/bestseller.php">Best Sellers</a>
                <a href="/bookstore/second.php">Special Offers</a>
                
                <!-- Authentication links -->
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="auth-links">
                        <a href="login.php">Login</a>
                        <a href="register.php">Register</a>
                    </div>
                <?php else: ?>
                    <div class="user-dropdown">
                        <div class="user-icon">
                           
                        <img src="imgs/user2.png" alt="User Icon" style="width: 60px; height: 60px;">
                        </div>
                        <div class="user-dropdown-content">
                            
                            <a href="orders.php">My Orders</a>
                            <a href="logout.php">Logout</a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <a href="/bookstore/wishlist.php">
                    <img src="imgs/heart (2).png" alt="Wishlist" style="width:60px;">
                    <?php 
                        // Display wishlist count if available
                        if (!empty($wishlist_items)): 
                        ?>
                        <span class="wishlist-count"><?php echo count($wishlist_items); ?></span>
                        <?php endif; ?>
                </a>
                <a href="/bookstore/cart.php">
                    <img src="imgs/cart.png" alt="Shopping Cart">
                    <?php if ($cart_count > 0): ?>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </nav>
       
    </header>
    <div class="cart-container">
        <h1>Your Shopping Cart</h1>
        
        <div id="cart-empty-message" class="cart-empty" <?php echo (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) ? 'style="display:none;"' : ''; ?>>
            <p>Your cart is empty</p>
            <a href="bestseller.php" class="continue-shopping">Continue Shopping</a>
        </div>
        
        <div id="cart-items" class="cart-items">
            <?php
            if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
                foreach($_SESSION['cart'] as $index => $item) {
                    // Check if this is a gift card
                    if(isset($item['type']) && $item['type'] === 'giftcard') {
                        // Compact gift card display for cart page
                        ?>
                        <div class="cart-item" data-id="<?php echo htmlspecialchars($item['id']); ?>" data-index="<?php echo $index; ?>">
                            <img src="imgs/giftcars.png" alt="Gift Card" class="cart-item-image">
                            <div class="cart-item-details">
                                <h3>Gift Card ($<?php echo htmlspecialchars($item['value']); ?>)</h3>
                                <P>TO :<?php echo htmlspecialchars($item['recipient_email']); ?></P>
                            </div>
                            
                            <button class="remove-item" data-index="<?php echo $index; ?>">Remove</button>
                        </div>
                        <?php
                    }
                    else {
                        ?>
                        <div class="cart-item gift-card-item" data-id="<?php echo htmlspecialchars($item['id']); ?>" data-index="<?php echo $index; ?>">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="cart-item-image">
                            <div class="cart-item-details">
                                <h3 class="cart-item-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                                <p class="cart-item-author">Author: <?php echo htmlspecialchars($item['author']); ?></p>
                                <p class="cart-item-price">$<?php echo htmlspecialchars($item['price']); ?></p>
                            </div>
                            <div class="cart-item-quantity">
                                <button class="decrease-qty">-</button>
                                <span class="quantity-button"><?php echo $item['quantity']; ?></span>
                                <button class="increase-qty">+</button>
                            </div>
                            <button class="remove-item" data-index="<?php echo $index; ?>">Remove</button>
                        </div>
                        <?php
                    }
                }
            }
            ?>
        </div>
        
        <div id="cart-summary" class="cart-summary" <?php echo (count($_SESSION['cart']) === 0) ? 'style="display:none;"' : ''; ?>>
            <div class="summary-row">
                <span>Subtotal:</span>
                <span id="subtotal">$<?php echo number_format($subtotal, 2); ?></span>
            </div>
            <div class="summary-row">
                <span>Tax (10%):</span>
                <span id="tax">$<?php echo number_format($tax, 2); ?></span>
            </div>
            <div class="summary-row total">
                <span>Total:</span>
                <span id="total">$<?php echo number_format($total, 2); ?></span>
            </div>
            <button id="checkout-btn" class="checkout-btn" <?php echo ($subtotal <= 0) ? 'disabled' : ''; ?>>Proceed to Checkout</button>
            <button id="clear-cart-btn" class="clear-cart-btn" <?php echo ($subtotal <= 0) ? 'disabled' : ''; ?>>Clear Cart</button>
        </div>
    </div>

    <footer class="footer">
        <!-- Footer content here -->
    </footer>

    <script>
    $(document).ready(function() {
        // Add functionality for the checkout button
        $('#checkout-btn').click(function() {
            window.location.href = 'checkout.php';
        });
    
        // Add to cart functionality
        $('.add-to-cart-btn').click(function() {
            var bookId = $(this).data('id');
            var bookTitle = $(this).data('title');
            var bookAuthor = $(this).data('author');
            var bookPrice = $(this).data('price');
            var bookImage = $(this).data('image');
            var csrfToken = $('#csrf_token').val();
            
            $.ajax({
                url: 'add_to_cart.php',
                type: 'POST',
                data: {
                    book_id: bookId,
                    title: bookTitle,
                    author: bookAuthor,
                    price: bookPrice,
                    image: bookImage,
                    csrf_token: csrfToken
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#cart-count').text(response.cart_count);
                        alert('Book added to cart!');
                    } else if (response.error) {
                        alert('Error: ' + response.error);
                    }
                },
                error: function() {
                    alert('Error adding to cart. Please try again.');
                }
            });
        });

        // Increase quantity - only for books
        $('.increase-qty').click(function() {
            var cartItem = $(this).closest('.cart-item');
            var bookId = cartItem.data('id');
            var index = cartItem.data('index');
            updateQuantity(bookId, 1, index);
        });

        // Decrease quantity - only for books
        $('.decrease-qty').click(function() {
            var cartItem = $(this).closest('.cart-item');
            var bookId = cartItem.data('id');
            var index = cartItem.data('index');
            updateQuantity(bookId, -1, index);
        });

        // Remove item - works for both books and gift cards
        $('.remove-item').click(function() {
            var cartItem = $(this).closest('.cart-item');
            var itemId = cartItem.data('id');
            var index = $(this).data('index');
            removeItem(itemId, index);
        });

        // Clear cart
        $('#clear-cart-btn').click(function() {
            var csrfToken = $('#csrf_token').val();
            
            $.ajax({
                url: 'clear_cart.php',
                type: 'POST',
                data: {
                    csrf_token: csrfToken
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else if (response.error) {
                        alert('Error: ' + response.error);
                    }
                },
                error: function() {
                    alert('Error clearing cart. Please try again.');
                }
            });
        });

        // Function to update quantity
        function updateQuantity(itemId, change, index) {
            var csrfToken = $('#csrf_token').val();
            
            $.ajax({
                url: 'update_cart.php',
                type: 'POST',
                data: {
                    item_id: itemId,
                    change: change,
                    index: index,
                    csrf_token: csrfToken
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else if (response.error) {
                        alert('Error: ' + response.error);
                    }
                },
                error: function() {
                    alert('Error updating quantity. Please try again.');
                }
            });
        }

        // Function to remove item
        function removeItem(itemId, index) {
            var csrfToken = $('#csrf_token').val();
            
            $.ajax({
                url: 'remove_from_cart.php',
                type: 'POST',
                data: {
                    item_id: itemId,
                    index: index,
                    csrf_token: csrfToken
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else if (response.error) {
                        alert('Error: ' + response.error);
                    }
                },
                error: function() {
                    alert('Error removing item. Please try again.');
                }
            });
        }
        
        // Update cart display when empty
        if ($('.cart-item').length === 0) {
            $('#cart-empty-message').show();
            $('#cart-summary').hide();
        } else {
            $('#cart-empty-message').hide();
            $('#cart-summary').show();
        }
    });
    </script>
</body>
</html>