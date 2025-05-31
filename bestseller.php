<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'bookhelper.php';
require_once 'carthelper.php';
require_once 'wishlisthelper.php';
// Get cart count
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += isset($item['quantity']) ? $item['quantity'] : 1;
    }
}
// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
}

// Get user ID if logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get bestseller books using the helper function
$bestSellerBooks = get_bestseller_books($conn, 'physical');
$bestSellerEbooks = get_bestseller_books($conn, 'ebook');

// Get wishlist items if user is logged in
$wishlist_items = [];
if ($user_id) {
    $wishlist_items = get_user_wishlist_simple($conn, $user_id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Best Sellers - Book Loft</title>
    <link rel="stylesheet" href="css/bestseller.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/nav.css">
    <link rel="stylesheet" href="css/bookstyle.css">
    <link rel="stylesheet" href="css/footer.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <!-- Add CSRF token hidden input -->
    <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <input type="hidden" id="user_logged_in" value="<?php echo is_logged_in() ? 'true' : 'false'; ?>">
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
    
    <header class="hero">
        <div class="hero-content fadi-in-up">
            <h1>Discover Our Best Sellers</h1>
            <p>Explore the most popular books loved by our readers</p>
        </div>
    </header>

    <section class="book-section fadi-in-up" id="books">
        <div class="section-header ">
            <h2>Best Selling Books</h2>
            <div class="section-divider"></div>
        </div>
        <div class="book-container">
            <?php if (!empty($bestSellerBooks)): ?>
                <?php foreach ($bestSellerBooks as $book): ?>
                <div class="book fade-in-up" data-id="<?php echo $book['id']; ?>">
                    <div class="book-cover">
                        <img src="<?php echo htmlspecialchars($book['image_link']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                        <div class="book-overlay">
                            <button class="btn-cart add-to-cart" data-id="<?php echo $book['id']; ?>">
                                <img src="imgs/cart.png" alt="Add to Cart">
                            </button>
                            <?php if ($user_id): ?>
                            <button class="btn-wishlist toggle-wishlist" data-id="<?php echo $book['id']; ?>">
                            <?php 
                            $is_in_wishlist = is_book_in_wishlist($conn, $user_id, $book['id']);
                            echo $is_in_wishlist ? "❤️" : "🤍"; 
                            ?>
                        </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="book-info">
                        <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                        <p class="book-author"><?php echo htmlspecialchars($book['author']); ?></p>
                        <div class="book-details">
                            <span class="book-year"><?php echo htmlspecialchars($book['year']); ?></span>
                            <span class="book-price">$<?php echo number_format($book['price'], 2); ?></span>
                        </div>
                        <a href="book-details.php?id=<?php echo $book['id']; ?>" class="btn-details">Show Details</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback static content if no books in database -->
                <div class="book fade-in-up" data-id="book1">
                    <div class="book-cover">
                        <img src="imgs/pride.png" alt="Pride and Prejudice">
                        <div class="book-overlay">
                            <button class="btn-cart"><img src="imgs/cart.png" alt="Add to Cart"></button>
                        </div>
                    </div>
                    <div class="book-info">
                        <h3 class="book-title">Pride and Prejudice</h3>
                        <p class="book-author">Jane Austen</p>
                        <div class="book-details">
                            <span class="book-year">1813</span>
                            <span class="book-price">$10.99</span>
                        </div>
                        <a href="book-details.php?id=book1" class="btn-details">Show Details</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="book-section fadi-in-up" id="ebooks">
        <div class="section-header">
            <h2>Best Selling eBooks</h2>
            <div class="section-divider"></div>
        </div>
        <div class="book-container">
            <?php if (!empty($bestSellerEbooks)): ?>
                <?php foreach ($bestSellerEbooks as $ebook): ?>
                <div class="book fadi-in-up" data-id="<?php echo $ebook['id']; ?>" data-format="eBook">
                    <div class="book-cover">
                        <img src="<?php echo htmlspecialchars($ebook['image_link']); ?>" alt="<?php echo htmlspecialchars($ebook['title']); ?>">
                        <div class="book-overlay">
                            <button class="btn-cart add-to-cart" data-id="<?php echo $ebook['id']; ?>">
                                <img src="imgs/cart.png" alt="Add to Cart">
                            </button>
                            <?php if ($user_id): ?>
                            <button class="btn-wishlist toggle-wishlist" data-id="<?php echo $ebook['id']; ?>">
                                <?php 
                                $is_in_wishlist = is_book_in_wishlist($conn, $user_id, $ebook['id']);
                                echo $is_in_wishlist ? '❤️' : '🤍'; 
                                ?>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="book-info">
                        <h3 class="book-title"><?php echo htmlspecialchars($ebook['title']); ?></h3>
                        <p class="book-author"><?php echo htmlspecialchars($ebook['author']); ?></p>
                        <div class="book-details">
                            <span class="book-year"><?php echo htmlspecialchars($ebook['year']); ?></span>
                            <?php if ($ebook['price'] == 0): ?>
                                <span class="book-price free">Free</span>
                            <?php else: ?>
                                <span class="book-price">$<?php echo number_format($ebook['price'], 2); ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="book-details.php?id=<?php echo $ebook['id']; ?>" class="btn-details">Show Details</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback static content if no ebooks in database -->
                <div class="book fadi-in-up" data-id="ebook1" data-format="eBook">
                    <div class="book-cover">
                        <img src="imgs/court.png" alt="A Court of Mist and Fury">
                        <div class="book-overlay">
                            <button class="btn-cart"><img src="imgs/cart.png" alt="Add to Cart"></button>
                        </div>
                    </div>
                    <div class="book-info">
                        <h3 class="book-title">A Court of Mist and Fury</h3>
                        <p class="book-author">Sarah J. Maas</p>
                        <div class="book-details">
                            <span class="book-year">2020</span>
                            <span class="book-price free">Free</span>
                        </div>
                        <a href="book-details.php?id=ebook1" class="btn-details">Show Details</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="newsletter">
        <div class="newsletter-content">
            <h2>Stay Updated</h2>
            <p>Subscribe to our newsletter for the latest book releases and exclusive offers</p>
            <form id="newsletter-form" action="process_newsletter.php" method="post">
                <input type="email" name="email" placeholder="Your email address" required>
                <button type="submit">Subscribe</button>
            </form>
            <div id="newsletter-message"></div>
        </div>
    </div>
    
    <footer class="footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> The Book Loft. All rights reserved.</p>
            <div class="social-icons">
                <a href="#">
                    <img src="imgs/wats.svg" alt="WhatsApp">
                </a>
                <a href="#">
                    <img src="imgs/inst.svg" alt="Instagram">
                </a>
            </div>
        </div>
    </footer>
    
    <!-- Book Details Modal -->
    <div id="book-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div id="book-details-content"></div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
$(document).ready(function() {
    // Add to cart functionality using AJAX
    $('.add-to-cart').click(function(e) {
        e.preventDefault();
        var bookId = $(this).data('id');
        var bookElement = $(this).closest('.book');
        var title = bookElement.find('.book-title').text();
        var author = bookElement.find('.book-author').text();
        var priceText = bookElement.find('.book-price').text().replace('$', '');
        var price = parseFloat(priceText);
        var image = bookElement.find('img').attr('src');
        
        $.ajax({
            url: 'add_to_cart.php',
            type: 'POST',
            data: {
                book_id: bookId,
                title: title,
                author: author,
                price: price,
                image: image,
                csrf_token: $('#csrf_token').val()
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update cart count
                    const cartCountElement = $('.cart-count');
                    if (cartCountElement.length) {
                        cartCountElement.text(response.cart_count);
                    } else {
                        $('a[href="/bookstore/cart.php"]').append('<span class="cart-count">' + response.cart_count + '</span>');
                    }
                    
                    // Show success message
                    showPopup('Book added to cart!');
                } else {
                    if (response.message === 'login_required') {
                        showPopup('Please log in to add items to your cart');
                        setTimeout(function() {
                            window.location.href = 'login.php';
                        }, 1500);
                    } else {
                        showPopup(response.message);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error("Error details:", xhr.responseText);
                showPopup('Error adding to cart. Please try again.');
            }
        });
    });
    
    // Toggle wishlist functionality
    $('.toggle-wishlist').click(function(e) {
        e.preventDefault();
        var bookId = $(this).data('id');
        var element = $(this);
        var isInWishlist = element.text().trim() === '❤️';
        var action = isInWishlist ? 'remove' : 'add';
        
        // Check if user is logged in
        if ($('#user_logged_in').val() !== 'true') {
            showPopup('Please log in to manage your wishlist');
            setTimeout(function() {
                window.location.href = 'login.php';
            }, 1500);
            return;
        }
        
        // Update UI immediately for better UX
        element.text(isInWishlist ? '🤍' : '❤️');
        
        $.ajax({
            url: 'add_to_wishlist.php',
            type: 'POST',
            data: {
                book_id: bookId,
                action: action,
                csrf_token: $('#csrf_token').val()
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showPopup(response.message);
                    
                    // Update wishlist counter
                    const wishlistCountElement = $('.wishlist-count');
                    if (wishlistCountElement.length) {
                        wishlistCountElement.text(response.wishlist_count);
                    } else if (response.wishlist_count > 0) {
                        $('a[href="/bookstore/wishlist.php"]').append('<span class="wishlist-count cart-count">' + response.wishlist_count + '</span>');
                    }
                } else {
                    // Revert UI if failed
                    element.text(isInWishlist ? '❤️' : '🤍');
                    showPopup(response.message || 'Operation failed');
                }
            },
            error: function() {
                // Revert UI if error
                element.text(isInWishlist ? '❤️' : '🤍');
                showPopup('Network error. Please try again.');
            }
        });
    });
    
    // Close modal
    $('.close-modal').click(function() {
        $('#book-modal').hide();
    });
    
    // Close modal when clicking outside
    $(window).click(function(e) {
        if ($(e.target).is('#book-modal')) {
            $('#book-modal').hide();
        }
    });
    
    // Newsletter form submission with AJAX
    $('#newsletter-form').submit(function(e) {
        e.preventDefault();
        var email = $(this).find('input[name="email"]').val();
        
        $.ajax({
            url: 'process_newsletter.php',
            type: 'POST',
            data: {
                email: email,
                csrf_token: $('#csrf_token').val()
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#newsletter-message').html('<p class="success-message">' + response.message + '</p>');
                    $('#newsletter-form')[0].reset();
                } else {
                    $('#newsletter-message').html('<p class="error-message">' + response.message + '</p>');
                }
            },
            error: function() {
                $('#newsletter-message').html('<p class="error-message">Error processing your request. Please try again.</p>');
            }
        });
    });
    
    // Function to show popup message
    function showPopup(message) {
        let popup = document.createElement("div");
        popup.classList.add("popup");
        popup.textContent = message;
        document.body.appendChild(popup);

        setTimeout(function() {
            popup.remove();
        }, 2000);
    }
});
</script>
    <script src="js/books.js"></script>
</body>
</html>
