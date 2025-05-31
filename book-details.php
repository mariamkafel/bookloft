<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'bookhelper.php';
require_once 'wishlisthelper.php';
require_once 'carthelper.php';

// Get book ID from URL
$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get user ID if logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Get cart count
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += isset($item['quantity']) ? $item['quantity'] : 1;
    }
}

// Get wishlist items if user is logged in
$wishlist_items = [];
if ($user_id) {
    $wishlist_items = get_user_wishlist($conn, $user_id);
}

// Get book details
$book = get_book_by_id($conn, $book_id);

// Check if book exists
if (!$book) {
    header('Location: books.php');
    exit;
}

// Check if book is in wishlist
$in_wishlist = false;
foreach ($wishlist_items as $item) {
    if ($item['book_id'] == $book_id) {
        $in_wishlist = true;
        break;
    }
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> | The Book Loft</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/nav.css">
    <link rel="stylesheet" href="css/footer.css">
    <style>
        .book-details-container {
            margin-top:100px;
            display: flex;
            max-width: 1200px;
            margin-bottom: 40px;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        
        .book-image-section {
            flex: 0 0 300px;
            margin-right: 30px;
        }
        
        .book-image-section img {
            width: 100%;
            border-radius: 4px;
            box-shadow: 0 0 5px rgba(0,0,0,0.2);
        }
        
        .book-info-section {
            flex: 1;
        }
        
        .book-title {
            font-size: 28px;
            margin-bottom: 10px;
            color: #30214d;
        }
        
        .book-author {
            font-size: 18px;
            color: #30214d;
            margin-bottom: 20px;
        }
        
        .book-metadata {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .metadata-item {
            margin-bottom: 10px;
        }
        
        .metadata-label {
            font-weight: bold;
            color: #30214d;
        }
        
        .book-price {
            font-size: 24px;
            font-weight: bold;
            color: #30214d;
            margin-bottom: 20px;
        }
        
        .book-abstract {
            line-height: 1.6;
            margin-bottom: 30px;
            color: #333;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn-cart, .btn-wishlist {
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-cart {
            background-color: #30214d;
            color: white;
        }
        
        .btn-cart:hover {
            background-color:#6d5290;
        }
        
        
       
        
        .btn-wishlist.in-wishlist {
            background-color:rgb(218, 221, 223);
            color: white;
            border: none;
        }
        
        
    </style>
</head>
<body>
    <!-- Add CSRF token for JavaScript -->
    <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <input type="hidden" id="user_logged_in" value="<?php echo $user_id ? 'true' : 'false'; ?>">
    <input type="hidden" id="book_id" value="<?php echo $book_id; ?>">
    <input type="hidden" id="in_wishlist" value="<?php echo $in_wishlist ? 'true' : 'false'; ?>">
    
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
    
    <div class="container">
      
        <div class="book-details-container">
            <div class="book-image-section">
                <img src="<?php echo htmlspecialchars($book['image_link']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
            </div>
            
            <div class="book-info-section">
                <h1 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h1>
                <p class="book-author">by <?php echo htmlspecialchars($book['author']); ?></p>
                
                <div class="book-metadata">
                    <div class="metadata-item">
                        <span class="metadata-label">Language:
                        <?php echo htmlspecialchars($book['language']) ; ?></span> 
                    </div>
                    
                    <div class="metadata-item">
                        <span class="metadata-label">Genre:
                        <?php echo !empty($book['genre']) ? htmlspecialchars($book['genre']) : 'N/A'; ?></span> 
                    </div>
                    
                    <div class="metadata-item">
                        <span class="metadata-label">Year:
                        <?php echo !empty($book['year']) ? htmlspecialchars($book['year']) : 'N/A'; ?></span> 
                    </div>
                    
                    <div class="metadata-item">
                        <span class="metadata-label">Rating:
                        <?php echo !empty($book['rating']) ? htmlspecialchars($book['rating']) . ' ★' : 'N/A'; ?></span> 
                    </div>
                    
                    <?php if (!empty($book['isbn'])): ?>
                    <div class="metadata-item">
                        <span class="metadata-label">ISBN:
                        <?php echo htmlspecialchars($book['isbn']); ?></span> 
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($book['pages'])): ?>
                    <div class="metadata-item">
                        <span class="metadata-label">Pages:
                        <?php echo htmlspecialchars($book['pages']); ?></span> 
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="book-price">
                    $<?php echo number_format((float)$book['price'], 2); ?>
                    <?php if (!empty($book['discount']) && $book['discount'] > 0): ?>
                        <span class="discount-badge"><?php echo $book['discount']; ?>% OFF</span>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($book['abstract'])): ?>
                <div class="book-abstract">
                    <h3>About this book</h3>
                    <p><?php echo nl2br(htmlspecialchars($book['abstract'])); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="action-buttons">
                    <button id="add-to-cart-btn" class="btn-cart">Add to Cart</button>
                    <button id="wishlist-btn" class="btn-wishlist <?php echo $in_wishlist ? 'in-wishlist' : ''; ?>">
                        <?php echo $in_wishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <p>&copy; 2025 Bookshop. Supporting independent bookstores.</p>
    </footer>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.getElementById('csrf_token').value;
    const bookId = document.getElementById('book_id').value;
    const isUserLoggedIn = document.getElementById('user_logged_in').value === 'true';
    const isInWishlist = document.getElementById('in_wishlist').value === 'true';
    
    const addToCartBtn = document.getElementById('add-to-cart-btn');
    const wishlistBtn = document.getElementById('wishlist-btn');
    
    // Add to Cart functionality
    addToCartBtn.addEventListener('click', function() {
        if (!isUserLoggedIn) {
            showPopup('Please log in to add items to your cart');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 1500);
            return;
        }
        
        // Send request to add to cart
        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `book_id=${bookId}&csrf_token=${csrfToken}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showPopup(data.message || 'Book added to cart!');
                
                // Update cart counter
                const cartCountElement = document.querySelector('.cart-count');
                if (cartCountElement) {
                    cartCountElement.textContent = data.cart_count;
                    // Make sure the count is visible if it was previously hidden
                    cartCountElement.style.display = 'inline-block';
                } else {
                    // Create cart count element if it doesn't exist
                    const cartLink = document.querySelector('a[href="/bookstore/cart.php"]');
                    if (cartLink) {
                        const countSpan = document.createElement('span');
                        countSpan.className = 'cart-count';
                        countSpan.textContent = data.cart_count;
                        cartLink.appendChild(countSpan);
                    }
                }
            } else {
                showPopup(data.message || 'Failed to add book to cart');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showPopup('An error occurred. Please try again.');
        });
    });
    
    // Wishlist functionality
    wishlistBtn.addEventListener('click', function() {
        if (!isUserLoggedIn) {
            showPopup('Please log in to manage your wishlist');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 1500);
            return;
        }
        
        const action = wishlistBtn.classList.contains('in-wishlist') ? 'remove' : 'add';
        
        fetch('add_to_wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `book_id=${bookId}&action=${action}&csrf_token=${csrfToken}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (action === 'add') {
                    wishlistBtn.classList.add('in-wishlist');
                    wishlistBtn.textContent = 'Remove from Wishlist';
                } else {
                    wishlistBtn.classList.remove('in-wishlist');
                    wishlistBtn.textContent = 'Add to Wishlist';
                }
                
                showPopup(data.message);
                
                // Update wishlist counter
                const wishlistCountElement = document.querySelector('.wishlist-count');
                if (wishlistCountElement) {
                    wishlistCountElement.textContent = data.wishlist_count;
                }
            } else {
                showPopup(data.message || 'Operation failed');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showPopup('An error occurred. Please try again.');
        });
    });
    
    // Function to show popup message
    function showPopup(message) {
        const popup = document.createElement('div');
        popup.classList.add('popup');
        popup.textContent = message;
        document.body.appendChild(popup);
        
        setTimeout(() => {
            popup.remove();
        }, 2500);
    }
});
    </script>
</body>
</html>