<?php
session_start();
require_once 'includes/db_connect.php';

// We'll remove the form submission handler since we're using AJAX
// The JavaScript in giftcard.js will handle the form submission

// Get cart count using database-consistent approach
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

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$wishlist_items = get_user_wishlist($conn, $user_id);


?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Book Loft - Gift Cards</title>
    <link rel="stylesheet" href="css/nav.css">
    <link rel="stylesheet" href="css/giftcard.css">
    <link rel="stylesheet" href="css/footer.css">
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
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </nav>
    
    </header>
    <section class="div1">
        <h1>THE BOOKLOFT GIFTCARDS</h1>
        <p>THEBOOKLOFT.org digital gift cards are the perfect gift for any avid reader. The card value is added to the recipient's THEBOOKLOFT.org account as credit, and never expires. THEBOOKLOFT.org digital gift cards can only be redeemed online on THEBOOKLOFT.org.</p>
        <div class="div2">
          
            <div class="left-column">
                <img src="imgs/giftcars.png" alt="Gift Card">
                <ul>
                    <li>If you purchase a gift card from a specific store or affiliate on THEBOOKLOFT.org, the recipient will be sent to the same store on our site when they click the link to redeem the card, so the store will get credit for any purchases made. However, it is possible for a customer to change to a different shop affiliation if they want to spread their purchases around--the card is like cash, and can be spent how the recipient wants.</li>
                    <li>THEBOOKLOFT.org Gift Cards are only accepted at https://THEBOOKLOFT.org/</li>
                    <li>You can choose a value from $10-$1,000.</li>
                    <li>All gift cards are digital only and must be sent to a valid email address.</li>
                    <li>Gift cards cannot be used for ebook purchases.</li>
                    <li>For a personalized touch, include the recipient's name and a personal message on the gift card form.</li>
                    <li>This will appear in the email they receive with the redemption code and link.</li>
                    <li>If you want the card delivered on a specific day, for example the recipient's birthday, enter the date in our order form and we'll send it to them in the morning of that day.</li>
                    <li>THEBOOKLOFT.org Gift Cards never expire and have no hidden fees.</li>
                    <li>To purchase multiple gift cards please add them one at a time to your cart.</li>
                </ul>
            </div>
        
            <div class="right-column">
                <!-- Confirmation message will be shown by JavaScript -->
                <div id="confirmation-message" style="display: none; margin-top: 20px; color: green; font-weight: bold;"></div>
                
                <!-- Updated form for AJAX submission -->
                <form id="giftcard-form" method="post">
                    <div class="input">
                        <label for="email_to" class="label">TO*</label>
                        <input id="email_to" name="email_to" type="email" placeholder="Recipient email" class="email" required>
                        <label for="email_from" class="label">FROM*</label>
                        <input id="email_from" name="email_from" type="email" placeholder="Your email" class="email" required>
                    </div>
                    <label class="option">Choose an Amount*</label>
                    <div class="price-options">
                        <input id="price10" name="price" type="radio" value="10" required>
                        <label for="price10">$10</label>
                        <input id="price15" name="price" type="radio" value="15">
                        <label for="price15">$15</label>
                        <input id="price20" name="price" type="radio" value="20">
                        <label for="price20">$20</label>
                        <input id="price25" name="price" type="radio" value="25">
                        <label for="price25">$25</label>
                        <input id="price50" name="price" type="radio" value="50">
                        <label for="price50">$50</label>
                        <input id="price75" name="price" type="radio" value="75">
                        <label for="price75">$75</label>
                        <input id="price100" name="price" type="radio" value="100">
                        <label for="price100">$100</label>
                        <input id="price250" name="price" type="radio" value="250">
                        <label for="price250">$250</label>
                        <input id="price500" name="price" type="radio" value="500">
                        <label for="price500">$500</label>
                        <input id="price1000" name="price" type="radio" value="1000">
                        <label for="price1000">$1000</label>
                    </div>
                    <label for="message" class="option">Add a Message</label>
                    <textarea id="message" name="message" placeholder="Add a personal message" class="text" maxlength="250"></textarea>
                    <small id="char-count">0/250 characters</small>
                    <button type="submit" class="button1 btn-cart">Add to cart</button>
                </form>
            </div>
        </div>
    </section>
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
    
    <!-- Include the JavaScript files -->
    <script src="js/giftcard.js"></script>
</body>
</html>