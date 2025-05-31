<?php
session_start();
require_once 'includes/db_connect.php';

// Check if order ID exists in session
if (!isset($_SESSION['order_id'])) {
    header('Location: index.php');
    exit;
}

$order_id = $_SESSION['order_id'];
$order_details = null;
$order_items = [];

// Get order details
try {
    $stmt = $conn->prepare("SELECT o.*, u.name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order_details = $result->fetch_assoc();
        
        // Get order items
        $stmt = $conn->prepare("SELECT oi.*, b.title, b.author FROM order_items oi LEFT JOIN books b ON oi.book_id = b.id WHERE oi.order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $items_result = $stmt->get_result();
        
        while ($item = $items_result->fetch_assoc()) {
            $order_items[] = $item;
        }
    } else {
        // Order not found
        header('Location: index.php');
        exit;
    }
} catch(Exception $e) {
    error_log("Error getting order details: " . $e->getMessage());
    $error_message = "There was an error retrieving your order information.";
}

// Clear the order ID from session
unset($_SESSION['order_id']);

// Get cart count for header (should be 0 after successful order)
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += isset($item['quantity']) ? $item['quantity'] : 1;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - The Book Loft</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/nav.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }
        
        .confirmation-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .confirmation-header h1 {
            color: #4CAF50;
            margin-bottom: 10px;
        }
        
        .order-info {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .order-info h2 {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .order-section {
            margin-bottom: 20px;
        }
        
        .order-section h3 {
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .order-item {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .item-details {
            flex-grow: 1;
        }
        
        .item-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .item-author {
            font-style: italic;
            color: #666;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .item-qty-price {
            display: flex;
            justify-content: space-between;
            color: #666;
            font-size: 14px;
        }
        
        .order-totals {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .grand-total {
            font-weight: bold;
            font-size: 18px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        
        .confirmation-footer {
            text-align: center;
            margin-top: 30px;
        }
        
        .continue-shopping {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        
        .continue-shopping:hover {
            background-color: #45a049;
        }
        
        @media (max-width: 768px) {
            .order-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
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
                            <img src="imgs/user-icon.png" alt="User" onerror="this.src='imgs/default-user.png'">
                            <span class="username-display"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </div>
                        <div class="user-dropdown-content">
                            <a href="profile.php">My Profile</a>
                            <a href="orders.php">My Orders</a>
                            <a href="logout.php">Logout</a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <a href="/bookstore/wishlist.php">
                    <img src="imgs/heart (2).png" alt="Wishlist" style="width:60px;">
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
    
    <main>
        <div class="confirmation-container">
            <?php if(isset($error_message)): ?>
                <div class="error-message">
                    <?php echo $error_message; ?>
                </div>
            <?php else: ?>
                <div class="confirmation-header">
                    <h1>Order Confirmed!</h1>
                    <p>Thank you for your order. A confirmation email has been sent to <?php echo htmlspecialchars($order_details['email']); ?></p>
                    <p><strong>Order #:</strong> <?php echo htmlspecialchars($order_id); ?></p>
                </div>
                
                <div class="order-info">
                    <h2>Order Details</h2>
                    <div class="order-details">
                        <div class="order-section">
                            <h3>Shipping Information</h3>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($order_details['name']); ?></p>
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($order_details['shipping_address']); ?></p>
                        </div>
                        
                        <div class="order-section">
                            <h3>Order Summary</h3>
                            <p><strong>Order Date:</strong> <?php echo date('F j, Y', strtotime($order_details['order_date'])); ?></p>
                            <p><strong>Payment Method:</strong> <?php echo ucfirst(htmlspecialchars($order_details['payment_method'])); ?></p>
                            <p><strong>Status:</strong> <?php echo ucfirst(htmlspecialchars($order_details['status'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="order-section">
                        <h3>Items Ordered</h3>
                        <?php foreach ($order_items as $item): ?>
                            <div class="order-item">
                                <div class="item-details">
                                    <div class="item-title"><?php echo htmlspecialchars($item['title'] ?? 'Gift Card'); ?></div>
                                    <?php if (isset($item['author']) && !empty($item['author'])): ?>
                                        <div class="item-author">by <?php echo htmlspecialchars($item['author']); ?></div>
                                    <?php endif; ?>
                                    <div class="item-qty-price">
                                        <span>Qty: <?php echo $item['quantity']; ?></span>
                                        <span>$<?php echo number_format($item['price'], 2); ?> each</span>
                                    </div>
                                </div>
                                <div>
                                    <p class="item-subtotal">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="order-totals">
                            <div class="total-row">
                                <span>Subtotal:</span>
                                <span>$<?php echo number_format($order_details['total_amount'] / 1.1, 2); ?></span>
                            </div>
                            <div class="total-row">
                                <span>Tax (10%):</span>
                                <span>$<?php echo number_format($order_details['total_amount'] - ($order_details['total_amount'] / 1.1), 2); ?></span>
                            </div>
                            <div class="total-row grand-total">
                                <span>Total:</span>
                                <span>$<?php echo number_format($order_details['total_amount'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="confirmation-footer">
                    <p>Estimated delivery: 3-5 business days</p>
                    <a href="index.php" class="continue-shopping">Continue Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <footer class="footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> The Book Loft. All rights reserved.</p>
            <div class="social-icons">
                <a href="#" aria-label="WhatsApp">
                    <img src="imgs/wats.svg" alt="WhatsApp icon">
                </a>
                <a href="#" aria-label="Instagram">
                    <img src="imgs/inst.svg" alt="Instagram icon">
                </a>
            </div>
        </div>
    </footer>
</body>
</html>