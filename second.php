<?php
session_start(); // Add session start at the beginning
// Include helper files
require_once 'bookhelper.php';
require_once 'carthelper.php';
require_once 'wishlisthelper.php';

// Database connection
$servername = "localhost";
$username = "root"; // Replace with your database username
$password = ""; // Replace with your database password
$dbname = "bookloft"; // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get special offer books (books with discounts)
$special_offer_books = get_special_offer_books($conn);

// Count cart items
$cart_count = get_cart_count();

// Get user info and wishlist count
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$wishlist_count = count_wishlist_items($conn, $user_id);
$wishlist_items = get_user_wishlist_simple($conn, $user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Book Loft</title>
    <!-- Add our new navbar CSS first to override other styles -->
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/style2.css">
    <link rel="stylesheet" href="css/nav.css">
    <style>
/* Fix cart icon display issues */
.book-icons img,
.cart-icon {
  width: 30px;
  height: 30px;
  min-width: 30px;
  min-height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8f5ff;
  padding: 5px;
  border-radius: 50%;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
  cursor: pointer;
  transition: transform 0.2s;
  box-sizing: content-box;
}

/* Ensure visibility and proper positioning for book icons */
.book-icons {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  position: absolute;
  top: 10px;
  right: 10px;
  z-index: 10;
  pointer-events: auto;
}

/* Fix for very small screens */
@media (max-width: 320px) {
  .book-icons img,
  .cart-icon {
    width: 24px;
    height: 24px;
    min-width: 24px;
    min-height: 24px;
    padding: 4px;
  }
  

}
    </style>
</head>
<body>
    <!-- Add CSRF token hidden input -->
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


    <div class="first block">
        <video id="video-display" autoplay muted loop>
            <source src="imgs/video.mp4" type="video/mp4">
            <source src="imgs/video.ogg" type="video/ogg">
            Your browser does not support the video tag.
        </video>
    </div>
    
    <div class="write">
        <h2><i>Take advantage of our amazing book and eBook sale, offering unbeatable prices on a wide range of genres. 
            Whether you prefer physical books or digital convenience, now's the time to expand your collection. 
            Don't wait—these deals won't last long!</i></h2>
        <img src="imgs/salelogo.png" alt="salelogo" class="sale-logo" id="saleLogo">   
    </div>
    
    <div class="second-block">
        <?php
        // Display books from database
        if (!empty($special_offer_books)) {
            foreach($special_offer_books as $book) {
                // Calculate discounted price
                $originalPrice = $book["price"];
                $discountPercentage = $book["discount"];
                $discountedPrice = $originalPrice - ($originalPrice * ($discountPercentage / 100));
                
                // Format prices to 2 decimal places
                $originalPrice = number_format($originalPrice, 2);
                $discountedPrice = number_format($discountedPrice, 2);
                
                // Get image path, default if not available
                $imagePath = !empty($book["image_link"]) ? $book["image_link"] : "imgs/default-book.png";
                
                // Check if book is in wishlist
                $inWishlist = is_book_in_wishlist($conn, $user_id, $book["id"]);
                
                echo '<div class="item">
                    <img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($book["title"]) . '">
                    <div class="book-icons">
                        <span class="favorite-icon ' . ($inWishlist ? 'in-wishlist' : '') . '" 
                            data-book-id="' . $book["id"] . '"
                            data-title="' . htmlspecialchars($book["title"], ENT_QUOTES) . '"
                            data-author="' . htmlspecialchars($book["author"], ENT_QUOTES) . '">'
                            . ($inWishlist ? '♥' : '♡') . 
                        '</span>
                        <img src="imgs/cart.png" alt="Cart" class="cart-icon" 
                            data-book-id="' . $book["id"] . '"
                            data-title="' . htmlspecialchars($book["title"], ENT_QUOTES) . '"
                            data-author="' . htmlspecialchars($book["author"], ENT_QUOTES) . '"
                            data-price="' . $discountedPrice . '"
                            data-image="' . htmlspecialchars($imagePath, ENT_QUOTES) . '">
                    </div>
                    <div class="book-info">
                        <p><strong>Title:</strong> ' . htmlspecialchars($book["title"]) . '</p>
                        <p><strong>Author:</strong> ' . htmlspecialchars($book["author"]) . '</p>
                        <p><strong>Year:</strong> ' . htmlspecialchars($book["year"]) . '</p>
                        <p><strong>Price:</strong> <del>' . $originalPrice . '$</del><br>' . $discountedPrice . '$ only</p>
                    </div>
                    <div class="book-actions">
                        <a href="book-details.php?id=' . $book["id"] . '" class="details-btn">Show Details</a>
                    </div>
                </div>';
            }
        } else {
            echo "<p>No special offers available at the moment.</p>";
        }
        ?>
    </div>
   
    
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

    <!-- Popup notification div -->
    <div id="popup" class="popup"></div>

    <script>
        document.querySelector('.write h2').style.animation = 'slideUp 1s ease-out';
        
        const heading = document.querySelector('.write h2');
        
        heading.animate([
            { 
                opacity: 0,
                transform: 'translateY(50px)',
                color: 'transparent'
            },
            {
                opacity: 1,
                transform: 'translateY(0)',
                color: 'inherit'
            }
        ], {
            duration: 1000,
            easing: 'ease-out',
            fill: 'forwards'
        });
        
        heading.addEventListener('mouseenter', () => {
            heading.animate([
                { transform: 'scale(1)' },
                { transform: 'scale(1.05)' },
                { transform: 'scale(1)' }
            ], {
                duration: 500,
                easing: 'ease-in-out'
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const saleLogo = document.getElementById('saleLogo');
            let isAnimating = false;
            
            setTimeout(() => {
                saleLogo.animate([
                    { transform: 'scale(0) rotate(-180deg)', opacity: 0 },
                    { transform: 'scale(1.2) rotate(10deg)', opacity: 1, offset: 0.8 },
                    { transform: 'scale(1) rotate(0deg)', opacity: 1 }
                ], {
                    duration: 1500,
                    easing: 'cubic-bezier(0.175, 0.885, 0.32, 1.275)',
                    fill: 'forwards'
                });
                
                setTimeout(() => {
                    saleLogo.classList.add('glow');
                    
                    setInterval(() => {
                        saleLogo.animate([
                            { transform: 'scale(1)' },
                            { transform: 'scale(1.05)' },
                            { transform: 'scale(1)' }
                        ], {
                            duration: 2000,
                            easing: 'ease-in-out'
                        });
                    }, 3000);
                    
                    setInterval(() => {
                        saleLogo.animate([
                            { transform: 'rotate(0deg)' },
                            { transform: 'rotate(5deg)' },
                            { transform: 'rotate(-5deg)' },
                            { transform: 'rotate(0deg)' }
                        ], {
                            duration: 1000,
                            easing: 'ease-in-out'
                        });
                    }, 7000);
                }, 1500);
            }, 1000);
            
            function createSparkle(x, y) {
                const sparkle = document.createElement('div');
                sparkle.className = 'sparkle';
                document.body.appendChild(sparkle);
                
                const offsetX = (Math.random() - 0.5) * 100;
                const offsetY = (Math.random() - 0.5) * 100;
                
                sparkle.style.left = (x + offsetX) + 'px';
                sparkle.style.top = (y + offsetY) + 'px';
                
                const size = Math.random() * 15 + 5;
                sparkle.style.width = size + 'px';
                sparkle.style.height = size + 'px';
                
                const colors = ['#FFD700', '#FFA500', '#FF4500', '#FF6347', '#FF69B4'];
                sparkle.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                
                sparkle.animate([
                    { transform: 'scale(0)', opacity: 1 },
                    { transform: 'scale(1)', opacity: 0.8, offset: 0.3 },
                    { transform: 'scale(0)', opacity: 0 }
                ], {
                    duration: 1000,
                    easing: 'cubic-bezier(0.175, 0.885, 0.32, 1.275)',
                    fill: 'forwards'
                });
                
                setTimeout(() => {
                    document.body.removeChild(sparkle);
                }, 1000);
            }
            
            saleLogo.addEventListener('mouseover', function() {
                if (!isAnimating) {
                    isAnimating = true;
                    
                    this.animate([
                        { transform: 'rotateY(0deg)' },
                        { transform: 'rotateY(180deg)' },
                        { transform: 'rotateY(360deg)' }
                    ], {
                        duration: 1000,
                        easing: 'ease-in-out'
                    }).onfinish = () => {
                        isAnimating = false;
                    };
                }
            });
            
            saleLogo.addEventListener('click', function(e) {
                this.animate([
                    { transform: 'scale(1)', filter: 'brightness(1)' },
                    { transform: 'scale(1.3)', filter: 'brightness(1.5)', offset: 0.4 },
                    { transform: 'scale(1)', filter: 'brightness(1)' }
                ], {
                    duration: 800,
                    easing: 'cubic-bezier(0.175, 0.885, 0.32, 1.275)'
                });
                
                for (let i = 0; i < 20; i++) {
                    setTimeout(() => {
                        createSparkle(e.pageX, e.pageY);
                    }, i * 50);
                }
                
                document.body.animate([
                    { backgroundColor: 'rgba(255, 215, 0, 0.2)' },
                    { backgroundColor: 'rgba(255, 215, 0, 0)' }
                ], {
                    duration: 500,
                    easing: 'ease-out'
                });
            });
            
            setInterval(() => {
                const rect = saleLogo.getBoundingClientRect();
                const x = rect.left + rect.width/2 + window.scrollX;
                const y = rect.top + rect.height/2 + window.scrollY;
                
                createSparkle(
                    x + (Math.random() - 0.5) * rect.width * 1.5,
                    y + (Math.random() - 0.5) * rect.height * 1.5
                );
            }, 500);
            
            // Popup notification function
            function showPopup(message, type = 'success') {
                const popup = document.getElementById('popup');
                popup.textContent = message;
                popup.className = 'popup ' + type;
                popup.style.display = 'block';
                
                // Animate in
                popup.style.animation = 'slideIn 0.3s forwards';
                
                // Set timeout to animate out and hide
                setTimeout(() => {
                    popup.style.animation = 'slideOut 0.3s forwards';
                    setTimeout(() => {
                        popup.style.display = 'none';
                    }, 300);
                }, 2000);
            }
            
            // Update counters function
            function updateCounters(cartCount, wishlistCount) {
                // Update cart counter
                if (cartCount !== undefined && cartCount !== null) {
                    let cartCountElement = document.querySelector('.cart-count');
                    const cartIconContainer = document.querySelector('a[href="/bookstore/cart.php"]');
                    
                    if (cartCount > 0) {
                        if (cartCountElement) {
                            cartCountElement.textContent = cartCount;
                        } else {
                            cartCountElement = document.createElement('span');
                            cartCountElement.className = 'cart-count';
                            cartCountElement.textContent = cartCount;
                            cartIconContainer.appendChild(cartCountElement);
                        }
                    } else if (cartCountElement) {
                        cartIconContainer.removeChild(cartCountElement);
                    }
                }
                
                // Update wishlist counter
                if (wishlistCount !== undefined && wishlistCount !== null) {
                    let wishlistCountElement = document.querySelector('.wishlist-count');
                    const wishlistIconContainer = document.querySelector('a[href="/bookstore/wishlist.php"]');
                    
                    if (wishlistCount > 0) {
                        if (wishlistCountElement) {
                            wishlistCountElement.textContent = wishlistCount;
                        } else {
                            wishlistCountElement = document.createElement('span');
                            wishlistCountElement.className = 'wishlist-count';
                            wishlistCountElement.textContent = wishlistCount;
                            wishlistIconContainer.appendChild(wishlistCountElement);
                        }
                    } else if (wishlistCountElement) {
                        wishlistIconContainer.removeChild(wishlistCountElement);
                    }
                }
            }
            
            // Add to cart functionality
            document.querySelectorAll('.cart-icon').forEach(icon => {
                icon.addEventListener('click', function() {
                    const bookId = this.getAttribute('data-book-id');
                    const title = this.getAttribute('data-title');
                    const author = this.getAttribute('data-author');
                    const price = this.getAttribute('data-price');
                    const image = this.getAttribute('data-image');
                    const csrfToken = document.getElementById('csrf_token').value;
                    
                    // AJAX request to add_to_cart.php
                    const formData = new FormData();
                    formData.append('book_id', bookId);
                    formData.append('title', title);
                    formData.append('author', author);
                    formData.append('price', price);
                    formData.append('image', image);
                    formData.append('csrf_token', csrfToken);
                    
                    fetch('add_to_cart.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showPopup('Book added to cart!');
                            updateCounters(data.cart_count, null);
                        } else {
                            if (data.message === 'login_required') {
                                showPopup('Please log in to add items to your cart.', 'error');
                                setTimeout(() => {
                                    window.location.href = '/bookstore/login.php';
                                }, 2000);
                            } else {
                                showPopup('Error: ' + data.message, 'error');
                            }
                        }
                    })
                    .catch(error => {
                        showPopup('An error occurred. Please try again.', 'error');
                        console.error('Error:', error);
                    });
                });
            });
            
            // Add to wishlist functionality
            document.querySelectorAll('.favorite-icon').forEach(icon => {
                icon.addEventListener('click', function() {
                    const bookId = this.getAttribute('data-book-id');
                    const csrfToken = document.getElementById('csrf_token').value;
                    const isInWishlist = this.classList.contains('in-wishlist');
                    
                    // AJAX request to add_to_wishlist.php
                    const formData = new FormData();
                    formData.append('book_id', bookId);
                    formData.append('action', isInWishlist ? 'remove' : 'add');
                    formData.append('csrf_token', csrfToken);
                    
                    fetch('add_to_wishlist.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (isInWishlist) {
                                showPopup('Book removed from wishlist!');
                                this.classList.remove('in-wishlist');
                                this.innerHTML = '♡';
                            } else {
                                showPopup('Book added to wishlist!');
                                this.classList.add('in-wishlist');
                                this.innerHTML = '♥';
                            }
                            
                            updateCounters(null, data.wishlist_count);
                        } else {
                            if (data.message === 'login_required') {
                                showPopup('Please log in to manage your wishlist.', 'error');
                                setTimeout(() => {
                                    window.location.href = '/bookstore/login.php';
                                }, 2000);
                            } else {
                                showPopup('Error: ' + data.message, 'error');
                            }
                        }
                    })
                    .catch(error => {
                        showPopup('An error occurred. Please try again.', 'error');
                        console.error('Error:', error);
                    });
                });
            });
        });
    </script>
    <script src="js/books.js"></script>
</body>
</html>
<?php
// Close database connection
$conn->close();
?>