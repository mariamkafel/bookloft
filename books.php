<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'wishlisthelper.php';
require_once 'bookhelper.php';
require_once 'carthelper.php';
require_once 'ajaxhelper.php';

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
// Get user ID if logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Get wishlist items if user is logged in
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
    <title>The Book Loft | Books</title>
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/nav.css">
    <link rel="stylesheet" href="css/bookstyle.css">
    <style>
    /* Override styles to ensure proper display */
    .book {
        background-color: #ffffff;
        color: #333;
        width: 250px;
        margin: 15px;
        height: 500px;
        display: flex;
        flex-direction: column;
    }
    
    .book-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 20px;
    }
    
    .books {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .book-grid {
        background-color: #f9f9f9;
        padding: 20px;
    }
    
    .book-cover {
        height: 300px;
        flex: 0 0 300px;
        position: relative;
        overflow: hidden;
    }
    
    .book-cover img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        background-color: #f8f8f8;
    }
    
    .book-info {
        padding: 20px;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        background-color: #ffffff;
        color: #333;
    }
    
    .book-info h3 {
        color: #30214d;
        margin-top: 0;
        margin-bottom: 5px;
    }
    
    .book-info p {
        color: #666;
        margin: 0;
    }
    
    .btn-details {
        width: 100%;
        background-color: #30214d;
        color: white;
        border: none;
        padding: 10px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        margin-top: auto;
    }
    
    
    /* Additional responsive styles for books.php */
    @media (max-width: 768px) {
        .controls {
            flex-direction: column;
            align-items: center;
            gap: 10px;
            margin-top: 100px;
            padding: 0 15px;
        }
        
        .controls input,
        .controls select {
            width: 100%;
            max-width: 300px;
        }
        
        .book-grid {
            padding: 20px 10px;
        }
        
        .book-grid h2 {
            font-size: 24px;
            margin-bottom: 20px;
        }
    }
    
    @media (max-width: 480px) {
        .controls {
            margin-top: 80px;
        }
        
        .book-grid h2 {
            font-size: 20px;
        }
    }
  @media (max-width: 576px) {
    #books.book-container {
      display: grid;
      grid-template-columns: 1fr !important;
      gap: 15px;
    }
    
    .book {
      width: 100%;
      max-width: 300px;
      margin: 10px auto;
    }
    
    .book-cover {
      height: 280px;
      flex: 0 0 280px;
    }
    
    .book-info {
      padding: 15px;
    }
  }
</style>
</head>
<body>
    <!-- Add CSRF token and other data for JavaScript -->
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
    <section class="controls">
        <input type="text" id="search" placeholder="Search by title or author...">
        
        <select id="genre-filter">
            <option value="">All Genres</option>
            <?php
            $genres = get_unique_values($conn, 'genre');
            foreach ($genres as $genre) {
                echo "<option value='$genre'>$genre</option>";
            }
            ?>
        </select>
        
        <select id="language-filter">
            <option value="">All Languages</option>
            <?php
            $languages = get_unique_values($conn, 'language');
            foreach ($languages as $language) {
                echo "<option value='$language'>$language</option>";
            }
            ?>
        </select>
        
        <select id="sort-books">
            <option value="" disabled selected>Sort</option>
            <option value="title">Sort by Title</option>
            <option value="price">Sort by Price (Low to High)</option>
        </select>
    </section>
    
    <section class="book-grid books-grid">
        <h2>Featured Books</h2>
        <div id="books" class="book-container">
            <!-- Books will be loaded here dynamically -->
        </div>
    </section>
    
    <div id="modal" class="modal">
        <div class="modal-content">
            <span id="close-modal" class="close">&times;</span>
            <div id="modal-body"></div>
        </div>
    </div>
    
    <footer>
        <p>&copy; 2025 Bookshop. Supporting independent bookstores.</p>
    </footer>

    <!-- Include JavaScript file -->
    <script src="js/books.js"></script>
    <script>
  // Add this script to ensure 1 book per row on mobile
  document.addEventListener('DOMContentLoaded', function() {
    function adjustBookLayout() {
      if (window.innerWidth <= 576) {
        const booksContainer = document.getElementById('books');
        if (booksContainer) {
          booksContainer.style.gridTemplateColumns = '1fr';
        }
      }
    }
    
    // Run on page load
    adjustBookLayout();
    
    // Run on window resize
    window.addEventListener('resize', adjustBookLayout);
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
    
    // Function to create book elements with the new styling
    function createBookElement(book) {
        const isInWishlist = checkIfInWishlist(book.id);
        
        const bookElement = document.createElement('div');
        bookElement.className = 'book fade-in-up';
        bookElement.dataset.id = book.id;
        
        bookElement.innerHTML = `
            <div class="book-cover">
                <img src="${book.image_link || 'placeholder.jpg'}" alt="${book.title}">
                <div class="book-overlay">
                    <button class="btn-cart add-to-cart" data-id="${book.id}">
                        <img src="imgs/cart.png" alt="Add to Cart">
                    </button>
                    ${user_id ? `
                    <button class="toggle-wishlist" data-id="${book.id}">
                        ${isInWishlist ? "❤️" : "🤍"}
                    </button>
                    ` : ''}
                </div>
            </div>
            <div class="book-info">
                <div class="book-title-container">
                    <h3 class="book-title">${book.title}</h3>
                </div>
                <div class="book-author-container">
                    <p class="book-author">${book.author}</p>
                </div>
                <div class="book-details-container">
                    <div class="book-details">
                        <span class="book-year">${book.year || ''}</span>
                        <span class="book-price">$${parseFloat(book.price).toFixed(2)}</span>
                    </div>
                </div>
                <div class="btn-details-container">
                    <a href="book-details.php?id=${book.id}" class="btn-details">Show Details</a>
                </div>
            </div>
        `;
        
        return bookElement;
    }
    
    // Check if a book is in the wishlist
    function checkIfInWishlist(bookId) {
        const wishlistItems = JSON.parse(document.getElementById('wishlist_items').value || "[]");
        return wishlistItems.some(item => item.book_id == bookId);
    }
    
    // Override the default renderBooks function
    window.renderBooks = function(books) {
        const booksContainer = document.getElementById('books');
        booksContainer.innerHTML = '';
        
        if (books.length === 0) {
            booksContainer.innerHTML = '<p class="no-books">No books found matching your criteria.</p>';
            return;
        }
        
        books.forEach(book => {
            const bookElement = createBookElement(book);
            booksContainer.appendChild(bookElement);
        });
        
        // Add event listeners for cart and wishlist buttons
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                addToCart(this.dataset.id);
            });
        });
        
        document.querySelectorAll('.toggle-wishlist').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                toggleWishlist(this, this.dataset.id);
            });
        });
    };
    
    // Add to cart function
    function addToCart(bookId) {
        const book = findBookById(bookId);
        if (!book) return;
        
        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'book_id': bookId,
                'title': book.title,
                'author': book.author,
                'price': book.price,
                'image': book.image_link,
                'csrf_token': document.getElementById('csrf_token').value
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showPopup('Book added to cart!');
                updateCartCount(data.cart_count);
            } else {
                if (data.message === 'login_required') {
                    showPopup('Please log in to add items to your cart');
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 1500);
                } else {
                    showPopup(data.message || 'Error adding to cart');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showPopup('Error adding to cart. Please try again.');
        });
    }
    
    // Toggle wishlist function
    function toggleWishlist(element, bookId) {
        const isInWishlist = element.textContent.trim() === '❤️';
        const action = isInWishlist ? 'remove' : 'add';
        
        // Check if user is logged in
        if (document.getElementById('user_logged_in').value !== 'true') {
            showPopup('Please log in to manage your wishlist');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 1500);
            return;
        }
        
        // Update UI immediately for better UX
        element.textContent = isInWishlist ? '🤍' : '❤️';
        
        fetch('add_to_wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'book_id': bookId,
                'action': action,
                'csrf_token': document.getElementById('csrf_token').value
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showPopup(data.message || (action === 'add' ? 'Added to wishlist' : 'Removed from wishlist'));
                updateWishlistCount(data.wishlist_count);
            } else {
                // Revert UI if failed
                element.textContent = isInWishlist ? '❤️' : '🤍';
                showPopup(data.message || 'Operation failed');
            }
        })
        .catch(error => {
            // Revert UI if error
            element.textContent = isInWishlist ? '❤️' : '🤍';
            console.error('Error:', error);
            showPopup('Network error. Please try again.');
        });
    }
    
    // Helper function to find book by ID
    function findBookById(bookId) {
        const books = window.booksData || [];
        return books.find(book => book.id == bookId);
    }
    
    // Update cart count
    function updateCartCount(count) {
        const cartCountElement = document.querySelector('.cart-count');
        if (cartCountElement) {
            cartCountElement.textContent = count;
        } else {
            const cartLink = document.querySelector('a[href="/bookstore/cart.php"]');
            const countSpan = document.createElement('span');
            countSpan.className = 'cart-count';
            countSpan.textContent = count;
            cartLink.appendChild(countSpan);
        }
    }
    
    // Update wishlist count
    function updateWishlistCount(count) {
        const wishlistCountElement = document.querySelector('.wishlist-count');
        if (wishlistCountElement) {
            wishlistCountElement.textContent = count;
        } else if (count > 0) {
            const wishlistLink = document.querySelector('a[href="/bookstore/wishlist.php"]');
            const countSpan = document.createElement('span');
            countSpan.className = 'wishlist-count';
            countSpan.textContent = count;
            wishlistLink.appendChild(countSpan);
        }
    }
    
    // Store user ID for use in JavaScript
    const user_id = document.getElementById('user_logged_in').value === 'true';
    </script>
</body>
</html>
