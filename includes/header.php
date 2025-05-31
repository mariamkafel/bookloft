<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get cart count
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
    <meta name="description" content="The Book Loft - Your one-stop shop for books, ebooks, and gift cards">
    <title><?php echo isset($page_title) ? $page_title . ' - The Book Loft' : 'The Book Loft'; ?></title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Additional styles for user authentication dropdown */
        .user-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .user-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 4px;
        }
        
        .user-dropdown:hover .user-dropdown-content {
            display: block;
        }
        
        .user-dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            text-align: left;
        }
        
        .user-dropdown-content a:hover {
            background-color: #f1f1f1;
        }
        
        .user-icon {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .user-icon img {
            width: 30px;
            height: 30px;
            margin-right: 5px;
        }
        
        .auth-links {
            display: flex;
            align-items: center;
        }
        
        .auth-links a {
            margin-left: 15px;
            text-decoration: none;
            font-weight: bold;
        }
        
        .username-display {
            margin-left: 5px;
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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
                    <?php 
                    // Display wishlist count if available
                    if (isset($_SESSION['wishlist']) && count($_SESSION['wishlist']) > 0): 
                    ?>
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