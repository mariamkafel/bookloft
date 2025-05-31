<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db_connect.php';

$user_id = $_SESSION['user_id'];

// Count cart items
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += isset($item['quantity']) ? $item['quantity'] : 1;
    }
}

// Fetch orders and items
$orders = [];
try {
    $stmt = $conn->prepare("
        SELECT o.id, o.order_date, o.total_amount, o.status, 
               o.shipping_address, o.payment_method 
        FROM orders o 
        WHERE o.user_id = ? 
        ORDER BY o.order_date DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);

    foreach ($orders as &$order) {
        $order_id = $order['id'];
        $items_stmt = $conn->prepare("
            SELECT 
                oi.book_id, oi.quantity, oi.price, oi.item_type,
                b.title, b.author, b.image_link
            FROM order_items oi
            LEFT JOIN books b ON oi.book_id = b.id
            WHERE oi.order_id = ?
        ");
        $items_stmt->bind_param("i", $order_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        $order['items'] = $items_result->fetch_all(MYSQLI_ASSOC);
        $items_stmt->close();
    }
} catch(Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "We encountered an issue retrieving your orders. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Book Loft - My Orders</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/nav.css">
    <link rel="stylesheet" href="css/orders.css">
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
                    <?php if (isset($_SESSION['wishlist']) && count($_SESSION['wishlist']) > 0): ?>
                        <span class="wishlist-count"><?php echo count($_SESSION['wishlist']); ?></span>
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

    <main>
        <div class="orders-container">
            <h1 class="page-title">My Orders</h1>

            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php elseif (empty($orders)): ?>
                <div class="no-orders">
                    <h2>You haven't placed any orders yet.</h2>
                    <a href="books.php" class="btn-primary">Shop Now</a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <div class="order-id">Order #<?php echo htmlspecialchars($order['id']); ?></div>
                                <div class="order-date">Placed on: <?php echo date('F j, Y', strtotime($order['order_date'])); ?></div>
                            </div>
                            <div>
                                <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span>
                            </div>
                            <div class="order-total">
                                Total: $<?php echo number_format($order['total_amount'], 2); ?>
                            </div>
                        </div>

                        <div class="order-details">
                            <h3>Items in this order:</h3>
                            <div class="order-items">
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="order-item">
                                        <div class="item-image">
                                            <?php if ($item['item_type'] === 'giftcard'): ?>
                                                <img src="imgs/giftcars.png" alt="Gift Card">
                                            <?php elseif (!empty($item['image_link'])): ?>
                                                <img src="<?php echo htmlspecialchars($item['image_link']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                            <?php else: ?>
                                                <img src="imgs/book-placeholder.jpg" alt="Book placeholder">
                                            <?php endif; ?>
                                        </div>
                                        <div class="item-details">
                                            <?php if ($item['item_type'] === 'giftcard'): ?>
                                                <div class="item-title">Gift Card</div>
                                                <div class="item-author">A digital gift card will be sent to your email.</div>
                                            <?php else: ?>
                                                <div class="item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                                <div class="item-author">by <?php echo htmlspecialchars($item['author']); ?></div>
                                            <?php endif; ?>
                                            <div class="item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                                        </div>
                                        <div class="item-price">
                                            $<?php echo number_format($item['price'], 2); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="order-meta">
                            <div class="payment-method"><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></div>
                            <div class="shipping-address"><strong>Shipping Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> The Book Loft. All rights reserved.</p>
            <div class="social-icons">
                <a href="#"><img src="imgs/wats.svg" alt="WhatsApp icon"></a>
                <a href="#"><img src="imgs/inst.svg" alt="Instagram icon"></a>
            </div>
        </div>
    </footer>
</body>
</html>
