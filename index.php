<?php
// Start a session to manage user data
session_start();

require_once 'includes/db_connect.php';
require_once 'wishlisthelper.php';  // Include wishlist helper functions
require_once 'carthelper.php';
require_once 'bookhelper.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}  
// Get cart count
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += isset($item['quantity']) ? $item['quantity'] : 1;
    }
}
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

$wishlist_items = [];
if ($user_id) {
    $wishlist_items = get_user_wishlist($conn, $user_id);
}
// Handle AJAX requests
if (isset($_GET['action'])) {
    $response = process_ajax_requests($conn, $_GET, $user_id, $wishlist_items);
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="The Book Loft - Your one-stop shop for books, ebooks, and gift cards">
    <title>The Book Loft - Home</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/nav.css">
    <style>
      
    </style>
</head>
<body>
    <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <input type="hidden" id="user_logged_in" value="<?php echo $user_id ? 'true' : 'false'; ?>">
    <input type="hidden" id="wishlist_items" value="<?php echo htmlspecialchars(json_encode($wishlist_items)); ?>">
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
                    <div class="cart-count"><?php echo $cart_count; ?></div>
                    <?php endif; ?>
                </a>
            </div>
        </nav>
        
    </header>
    
    <main>
    <div class="div1">
            <h1 class="title fade-in-up">The journey of a lifetime starts with the turning of a page.</h1>
        </div>
        <section class="div2">
            <div>
                <img src="imgs/logo3.png" class="fade-left" alt="Book Loft Logo">
            </div>
            <div class="fade-in-up">
                <h1>Welcome to The Book Loft!</h1>
                <p>At The Book Loft, we believe in the power of books to inspire, educate, and entertain. Whether you're a fan of physical books or prefer the convenience of eBooks, we have something for everyone. 
                Explore our carefully curated collection across a wide range of genres and find your next favorite read today!</p>
                
                <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="cta-buttons">
                    <a href="login.php" class="btn-primary btn-p">Login</a>
                    <a href="register.php" class="btn-secondary btn-p">Register</a>
                </div>
                <?php elseif (isset($_SESSION['user_id']) && isset($_SESSION['user_name'])): ?>
                    <p class="welcome-back">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
                <?php endif; ?>
            </div>
        </section>
        
        <section class="div3">
            <div>
                <img src="imgs/div3.png" class="fade-left" alt="Reading books illustration">
            </div>
            <div class="fade-in-up">
                <h1>
                    Read Books.<br> Support Local <br>Bookstores.
                </h1>
                <h2>
                    Finally, you can get both physical books and eBooks from your local bookstore!
                </h2>
                <p>
                    Browse and buy books at The Book Loft, and enjoy a seamless reading experience with our 
                    iPhone or Android apps. Every purchase you make helps support our store and other independent bookstores, 
                    ensuring the survival and growth of local literary communities.
                </p>
            </div>
        </section>

        <section class="div4">
            <div style="padding-bottom: 45px; line-height:30px" class="fade-in-up">
                <h2 style="font-size:55px; margin-bottom:20px;">Discover Our Collection</h2>
                <h3>Books for Every Taste</h3>
                <p>Dive into captivating worlds with our fiction collection.<br> 
                From bestsellers to timeless classics, we have a novel for every reader.<br></p>
            </div>
            <div class="div5 fade-in-up">
                <div>
                    <img src="imgs/non-fiction.jpg" alt="Non-fiction books">
                    <p>Dive into captivating worlds with our fiction collection. 
                        From bestsellers to timeless classics, we have a novel for every reader.</p>
                </div>
                <div>
                    <img src="imgs/books.jpg" alt="Fiction books">
                    <p>
                        Expand your knowledge and explore new ideas with our non-fiction books, 
                        ranging from history and biographies to self-help and mindfulness.
                    </p>
                </div>
                <div>
                    <img src="imgs/children.avif" alt="Children's books">
                    <p>
                        Nurture young minds with our wide selection of children's books. 
                        From picture books to middle-grade adventures, we have something for every age.
                    </p>
                </div>
                <div>
                    <img src="imgs/ebook.webp" alt="E-books">
                    <p>Take your reading anywhere with our extensive selection of eBooks. 
                        Accessible on multiple devices, our digital library makes it easy to read on the go.</p>
                </div>
            </div>
        </section>

    <section class="book-grid">
        <h2>Featured Books for this month</h2>
        <div id="featured-books" class="books">
            <!-- Featured books will be loaded here dynamically -->
        </div>
    </section>

    <!-- Add this modal HTML to the bottom of your index.php file, before the closing </body> tag -->
    <div id="featured-book-modal" class="modal">
        <div class="modal-content">
            <span id="close-featured-book-modal" class="close">&times;</span>
            <div id="featured-book-modal-body">
                <!-- Book details will be loaded here dynamically -->
            </div>
        </div>
    </div>

        <section class="div6">
            <h2>
                Why Shop with Us?
            </h2>
            <div>
                <p class="number fade-left">01</p>
                <h3 class="fade-left">Curated Collections</h3>
                <img src="imgs/cc.png" class="fade-in-up" alt="Curated collections icon">
                <p class="fade-left par">Expert team handpicks the best books and eBooks across genres, ensuring that you get quality reads every time.</p>
            </div>
            <div>
                <p class="fade-left par">Enjoy quick shipping on all physical books, with free delivery on orders over 100$.</p>
                <img src="imgs/F.png" class="fade-in-up" alt="Free shipping icon">
                <p class="number fade-left">02</p>
                <h3 class="fade-left">Free Shipping</h3>
            </div>
            <div>
                <p class="number fade-left">03</p>
                <h3 class="fade-left">Access to eBooks</h3>
                <img src="imgs/E.png" class="fade-in-up" alt="E-books access icon">
                <p class="fade-left par">Buy your eBooks and start reading instantly! 
                    Enjoy the convenience of downloading directly to your device.</p>
            </div>
        </section>
        
        <section class="newsletter">
            <div class="newsletter-container">
                <h2>Subscribe to Our Newsletter</h2>
                <p>Stay updated with our latest releases and exclusive offers</p>
                
                <?php if (isset($_POST['subscribe_newsletter'])): ?>
                    <?php
                    $email = filter_var($_POST['newsletter_email'], FILTER_SANITIZE_EMAIL);
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        // Here you would save the email to your database
                        $success = true;
                    } else {
                        $error = true;
                    }
                    ?>
                    
                    <?php if (isset($success)): ?>
                        <div class="success-message">
                            Thank you for subscribing to our newsletter!
                        </div>
                    <?php elseif (isset($error)): ?>
                        <div class="error-message">
                            Please enter a valid email address.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <input type="email" name="newsletter_email" placeholder="Your email address" required>
                    <button type="submit" name="subscribe_newsletter">Subscribe</button>
                </form>
            </div>
        </section>
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

    <script src="js/main.js"></script>
    <script src="js/featured_books.js"></script>
    <script src="js/books.js"></script>
   <script>
    
   </script>
</body>
</html>