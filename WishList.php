<?php
session_start();
require_once 'includes/db_connect.php';

// Generate CSRF token if it doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if user is logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Redirect to login if not logged in
if (!$user_id) {
    $_SESSION['login_error'] = "Please log in to view your wishlist";
    header("Location: login.php");
    exit();
}

// Function to get wishlist items for the current user
function getWishlistItems($conn, $user_id) {
    if (!$user_id) return [];
    
    $wishlist = [];
    $sql = "SELECT w.id, w.book_id, b.title, b.author, b.price, b.rating, b.year, b.abstract, b.image_link 
        FROM wishlist w
        JOIN books b ON w.book_id = b.id
        WHERE w.user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $wishlist[] = $row;
    }
    
    $stmt->close();
    return $wishlist;
}

// Check if we need to remove an item
if (isset($_POST['action']) && $_POST['action'] === 'remove' && isset($_POST['item_id'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['wishlist_error'] = "Invalid security token";
        header("Location: WishList.php");
        exit();
    }
    
    $item_id = (int)$_POST['item_id'];
    
    // Delete the wishlist item
    $sql = "DELETE FROM wishlist WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $item_id, $user_id);
    $stmt->execute();
    
    $_SESSION['wishlist_success'] = "Item removed from wishlist";
    
    // Redirect to prevent form resubmission
    header("Location: WishList.php");
    exit();
}
// Get cart count
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += isset($item['quantity']) ? $item['quantity'] : 1;
    }
}

// Get the user's wishlist items
$wishlist_items = getWishlistItems($conn, $user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bookshop | Wishlist</title>
    
    <link rel="stylesheet" href="css/styles.css" />
    <link rel="stylesheet" href="css/main.css"/>
    <link rel="stylesheet" href="css/nav.css">
    <link rel="stylesheet" href="css/wishlist.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
    <section class="wishlist">
        <h2>Your Wishlist</h2>
        <div class="section-divider"></div>
        
        <?php if (isset($_SESSION['wishlist_success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['wishlist_success']); ?></div>
            <?php unset($_SESSION['wishlist_success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['wishlist_error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['wishlist_error']); ?></div>
            <?php unset($_SESSION['wishlist_error']); ?>
        <?php endif; ?>
        
        <div id="items">
            <?php if (empty($wishlist_items)): ?>
                <p id="empty-message">Your wishlist is empty.</p>
            <?php else: ?>
                <?php foreach ($wishlist_items as $book): ?>
                    <div class="wishlist-item">
                        <div class="wishlist-img" onclick="showDetails(<?php echo htmlspecialchars(json_encode($book)); ?>)">
                            <img src="<?php echo htmlspecialchars($book['image_link']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                        </div>
                        <div class="wishlist-text" onclick="showDetails(<?php echo htmlspecialchars(json_encode($book)); ?>)">
                            <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                            <p><strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                            <div class="book-details">
                                <span class="book-year"><?php echo htmlspecialchars($book['year']); ?></span>
                                <span class="book-price">$<?php echo number_format($book['price'], 2); ?></span>
                            </div>
                        </div>
                        <!-- Form outside of the clickable area -->
                        <form method="post" action="WishList.php" onsubmit="event.stopPropagation();">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="item_id" value="<?php echo $book['id']; ?>">
                            <button type="submit" class="remove-btn">Remove</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <div class="modal-img">
                <img id="modal-image" src="/placeholder.svg" alt="" />
            </div>
            <div class="modal-text">
                <h2 id="modal-title"></h2>
                <p><strong>Author:</strong> <span id="modal-author"></span></p>
                <p><strong>Price:</strong> $<span id="modal-price"></span></p>
                <p><strong>Rating:</strong> <span id="modal-rating"></span> ★</p>
                <p><strong>Year:</strong> <span id="modal-year"></span></p>
                <p><strong>Abstract:</strong> <span id="modal-abstract"></span></p>
                <div class="modal-actions">
                    <form method="post" action="cart.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="book_id" id="modal-book-id" value="">
                        <button type="submit" class="add-to-cart-btn">Add to Cart</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
       <div class="footer-content">
           <p>&copy; <?php echo date("Y"); ?> The Book Loft. All rights reserved.</p>
           <div class="social-icons">
               <a href="#"><img src="imgs/whats.svg" alt="whatsapp"></a>
               <a href="#"><img src="imgs/inst.svg" alt="Instagram"></a>
           </div>
       </div>
   </footer>

    <script>
        function showDetails(book) {
            document.getElementById("modal-image").src = book.image_link;
            document.getElementById("modal-title").textContent = book.title;
            document.getElementById("modal-author").textContent = book.author;
            document.getElementById("modal-price").textContent = parseFloat(book.price).toFixed(2);
            document.getElementById("modal-rating").textContent = book.rating;
            document.getElementById("modal-year").textContent = book.year;
            document.getElementById("modal-abstract").textContent = book.abstract;
            document.getElementById("modal-book-id").value = book.book_id;

            document.getElementById("modal").style.display = "flex";
        }

        function closeModal() {
            document.getElementById("modal").style.display = "none";
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            let modal = document.getElementById("modal");
            if (event.target == modal) {
                closeModal();
            }
        }

        // Close modal when pressing Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
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
    </script>
    
    <!-- Add this to your book listing pages to enable "Add to Wishlist" functionality -->
    <script>
        function addToWishlist(bookId) {
            // Check if user is logged in
            <?php if (!$user_id): ?>
            window.location.href = 'login.php';
            return;
            <?php endif; ?>
            
            fetch('addtowishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'book_id=' + bookId + '&action=add&csrf_token=<?php echo $_SESSION['csrf_token']; ?>'
            })
            .then(response => response.json())
            .then(data => {
                showPopup(data.message);
                if (data.success && data.wishlist_count) {
                    // Update wishlist count in the navigation
                    const countElement = document.getElementById('count-wishlist');
                    if (countElement) {
                        countElement.textContent = data.wishlist_count;
                    } else if (data.wishlist_count > 0) {
                        // Create count element if it doesn't exist
                        const wishlistLink = document.querySelector('a[href="WishList.php"]');
                        const countSpan = document.createElement('span');
                        countSpan.id = 'count-wishlist';
                        countSpan.className = 'counter';
                        countSpan.textContent = data.wishlist_count;
                        wishlistLink.appendChild(countSpan);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showPopup('An error occurred. Please try again.');
            });
        }
    </script>
     <script src="js/books.js"></script>
</body>
</html>
