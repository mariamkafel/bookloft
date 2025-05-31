<?php

session_start();
require_once 'includes/db_connect.php';
require_once 'wishlisthelper.php';  // Include wishlist helper functions
require_once 'carthelper.php';      // Include cart helper functions

// Generate CSRF token if it doesn't exist
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

/**
* Handle cart actions such as adding and removing items
* For use in first.php
* 
* @return array|false Response array on success, false if no action performed
*/
function handle_cart_action() {
   // Check if form was submitted to add item to cart
   if (isset($_POST['add_to_cart']) && isset($_POST['book_id'])) {
       // Ensure carthelper.php is included
       if (!function_exists('add_to_cart')) {
           require_once 'carthelper.php';
       }
       
       // Create item array from POST data
       $item = [
           'id' => $_POST['book_id'],
           'title' => $_POST['title'] ?? '',
           'author' => $_POST['author'] ?? '',
           'price' => $_POST['price'] ?? '0.00',
           'image' => $_POST['image'] ?? '',
           'quantity' => 1
       ];
       
       // Get user ID if logged in
       $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
       
       // Use cart helper function if available
       if (function_exists('add_to_cart')) {
           global $conn; // Ensure database connection is available
           return add_to_cart($item,$quantity, $user_id ? $conn : null);
       } else {
           // Fallback direct implementation
           if (!isset($_SESSION['cart'])) {
               $_SESSION['cart'] = [];
           }
           
           // Check if item already exists in cart
           $item_exists = false;
           foreach ($_SESSION['cart'] as &$cart_item) {
               if ($cart_item['id'] == $item['id']) {
                   $cart_item['quantity'] += 1;
                   $item_exists = true;
                   break;
               }
           }
           
           // Add new item if it doesn't exist
           if (!$item_exists) {
               $_SESSION['cart'][] = $item;
           }
           
           return ['success' => true];
       }
   }
   
   return false;
}
// Process cart actions
$cart_response = handle_cart_action();
if ($cart_response && $cart_response['success']) {
   // Remember to show notification about cart update
   $show_cart_notification = true;
}

// Function to get books from database
function getBooks($conn, $category) {
   $books = array();

   // Prepare statement to prevent SQL injection
   $stmt = $conn->prepare("SELECT * FROM books WHERE type = 'ebook' AND category = ? ORDER BY id ASC");
   $stmt->bind_param("s", $category);
   $stmt->execute();
   $result = $stmt->get_result();

   // Fetch books as associative array
   while ($row = $result->fetch_assoc()) {
       $books[] = $row;
   }

   $stmt->close();
   return $books;
}

// Get the user's wishlist items
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

$wishlist_items = [];
if ($user_id) {
   $wishlist_items = get_user_wishlist($conn, $user_id);
}

// Get classic and trending books from database
$classicBooks = getBooks($conn, 'classic');
$trendingBooks = getBooks($conn, 'trending');

?>


<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>eBook of Book Loft</title>
   <link rel="stylesheet" href="css/main.css">
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="css/nav.css">
   <link rel="stylesheet" href="css/footer.css">
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
           max-width: 100%;
           overflow-x: visible;
       }
       
       .book-info {
           background-color: #ffffff;
           color: #333;
           padding: 20px;
           flex: 1;
           display: flex;
           flex-direction: column;
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
       
  @media (max-width: 768px) {
    .image-content {
      flex-direction: column;
      height: auto;
      min-height: 100vh;
      position: relative;
      padding-top: 80px;
    }
    
    .content {
      width: 100%;
      position: relative;
      right: auto;
      top: auto;
      transform: none;
      padding: 20px;
      order: 1;
      text-align: center;
      z-index: 2;
    }
    
    .img {
      width: 100%;
      height: 300px;
      position: relative;
      order: 2;
      margin-top: 20px;
    }
    
    .img.second {
      display: none;
    }
    
    .book-container {
      grid-template-columns: 1fr !important;
    }
    
    .book {
      width: 100%;
      max-width: 300px;
      margin: 10px auto;
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
   <div class="image-content">
       <div class="content">
           <h1>Browse & Select E-Books</h1>
           <br><br>
           <p>Discover the finest e-books from your favorite authors. Dive into a vast collection spanning every genre and indulge in the joy of limitless reading!</p>
       </div>
       <img class="img" src="imgs/ebook1noback.png" alt="Image 1">
       <img class="img second" src="imgs/ebook2noback.png" alt="Image 2">
   </div>

   <div class="spacer"></div> 

   <section class="book-section-first">
       <div class="border-text">
           <h2>Explore Our E-Book Collection</h2>
       </div>
      
       <p class="highlight-text"><b><i>Classic Books</i></b></p>
       <div class="book-container">
           <?php
           // Display classic books from database
           foreach($classicBooks as $book) {
               // Check if book is in wishlist
               $isInWishlist = false;
               foreach ($wishlist_items as $wishlist_item) {
                   if ($wishlist_item['book_id'] == $book['id']) {
                       $isInWishlist = true;
                       break;
                   }
               }
               ?>
               <div class="book">
                   <div class="book-cover">
                       <img src="<?php echo $book['image_link']; ?>" alt="<?php echo $book['title']; ?>">
                       <div class="book-overlay">
                           <button class="btn-cart add-to-cart" data-id="<?php echo $book['id']; ?>">
                               <img src="imgs/cart.png" alt="Add to Cart">
                           </button>
                           <?php if ($user_id): ?>
                           <button class="toggle-wishlist" data-id="<?php echo $book['id']; ?>">
                               <?php echo $isInWishlist ? "❤️" : "🤍"; ?>
                           </button>
                           <?php endif; ?>
                       </div>
                   </div>
                   <div class="book-info">
                       <div class="book-title-container">
                           <h3 class="book-title"><?php echo $book['title']; ?></h3>
                       </div>
                       <div class="book-author-container">
                           <p class="book-author"><?php echo $book['author']; ?></p>
                       </div>
                       <div class="book-details-container">
                           <div class="book-details">
                               <span class="book-year"><?php echo $book['year']; ?></span>
                               <?php if($book['price'] == '0.00'): ?>
                                   <span class="book-price free">Free</span>
                               <?php else: ?>
                                   <span class="book-price">$<?php echo $book['price']; ?></span>
                               <?php endif; ?>
                           </div>
                       </div>
                       <div class="btn-details-container">
                           <a href="book-details.php?id=<?php echo $book['id']; ?>" class="btn-details">Show Details</a>
                       </div>
                   </div>
               </div>
               <?php
           }
           ?>
       </div>
   </section>
   
   <section class="book-section-second">
       <p class="highlight-text"><b><i>Trending Books</i></b></p>
       <div class="book-container">
           <?php
           // Display trending books from database
           foreach($trendingBooks as $book) {
               // Check if book is in wishlist
               $isInWishlist = false;
               foreach ($wishlist_items as $wishlist_item) {
                   if ($wishlist_item['book_id'] == $book['id']) {
                       $isInWishlist = true;
                       break;
                   }
               }
               ?>
               <div class="book">
                   <div class="book-cover">
                       <img src="<?php echo $book['image_link']; ?>" alt="<?php echo $book['title']; ?>">
                       <div class="book-overlay">
                           <button class="btn-cart add-to-cart" data-id="<?php echo $book['id']; ?>">
                               <img src="imgs/cart.png" alt="Add to Cart">
                           </button>
                           <?php if ($user_id): ?>
                           <button class="toggle-wishlist" data-id="<?php echo $book['id']; ?>">
                               <?php echo $isInWishlist ? "❤️" : "🤍"; ?>
                           </button>
                           <?php endif; ?>
                       </div>
                   </div>
                   <div class="book-info">
                       <div class="book-title-container">
                           <h3 class="book-title"><?php echo $book['title']; ?></h3>
                       </div>
                       <div class="book-author-container">
                           <p class="book-author"><?php echo $book['author']; ?></p>
                       </div>
                       <div class="book-details-container">
                           <div class="book-details">
                               <span class="book-year"><?php echo $book['year']; ?></span>
                               <?php if($book['price'] == '0.00'): ?>
                                   <span class="book-price free">Free</span>
                               <?php else: ?>
                                   <span class="book-price">$<?php echo $book['price']; ?></span>
                               <?php endif; ?>
                           </div>
                       </div>
                       <div class="btn-details-container">
                           <a href="book-details.php?id=<?php echo $book['id']; ?>" class="btn-details">Show Details</a>
                       </div>
                   </div>
               </div>
               <?php
           }
           ?>
       </div>
   </section>

   <footer class="footer">
       <div class="footer-content">
           <p>&copy; <?php echo date("Y"); ?> The Book Loft. All rights reserved.</p>
           <div class="social-icons">
               <a href="#"><img src="imgs/whats.svg" alt="whatsapp"></a>
               <a href="#"><img src="imgs/inst.svg" alt="Instagram"></a>
           </div>
       </div>
   </footer>
   
   <?php
   // Add script to show cart notification
   if(isset($_POST['add_to_cart'])) {
       echo '<script>
       document.addEventListener("DOMContentLoaded", function() {
           showPopup("Book added to your cart!");
       });
       </script>';
   }
   ?>
   
   <script>
   // Function to show popup message
   function showPopup(message) {
       let popup = document.createElement("div");
       popup.classList.add("popup");
       popup.textContent = message;
       document.body.appendChild(popup);

       setTimeout(() => {
           popup.remove();
       }, 2000); // Remove after 2 seconds
   }
   
   // Add event listeners to cart and wishlist buttons
   document.addEventListener('DOMContentLoaded', function() {
       // Add to cart buttons
       document.querySelectorAll('.add-to-cart').forEach(button => {
           button.addEventListener('click', function(e) {
               e.preventDefault();
               const bookId = this.getAttribute('data-id');
               const bookElement = this.closest('.book');
               const title = bookElement.querySelector('.book-title').textContent;
               const author = bookElement.querySelector('.book-author').textContent;
               const priceText = bookElement.querySelector('.book-price').textContent.replace('$', '');
               const price = parseFloat(priceText);
               const image = bookElement.querySelector('img').getAttribute('src');
               
               const formData = new FormData();
               formData.append('book_id', bookId);
               formData.append('title', title);
               formData.append('author', author);
               formData.append('price', price);
               formData.append('image', image);
               formData.append('csrf_token', document.getElementById('csrf_token').value);
               
               fetch('add_to_cart.php', {
                   method: 'POST',
                   body: formData
               })
               .then(response => response.json())
               .then(data => {
                   if (data.success) {
                       showPopup('Book added to cart!');
                       
                       // Update cart count
                       const cartCountElement = document.querySelector('.cart-count');
                       if (cartCountElement) {
                           cartCountElement.textContent = data.cart_count;
                       } else {
                           const cartLink = document.querySelector('a[href="/bookstore/cart.php"]');
                           const countSpan = document.createElement('span');
                           countSpan.className = 'cart-count';
                           countSpan.textContent = data.cart_count;
                           cartLink.appendChild(countSpan);
                       }
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
           });
       });
       
       // Toggle wishlist buttons
       document.querySelectorAll('.toggle-wishlist').forEach(button => {
           button.addEventListener('click', function(e) {
               e.preventDefault();
               const bookId = this.getAttribute('data-id');
               const isInWishlist = this.textContent.trim() === '❤️';
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
               this.textContent = isInWishlist ? '🤍' : '❤️';
               
               // Get CSRF token
               const csrfToken = document.getElementById('csrf_token').value;
               
               // Send to server
               const formData = new FormData();
               formData.append('book_id', bookId);
               formData.append('action', action);
               formData.append('csrf_token', csrfToken);
               
               fetch('add_to_wishlist.php', {
                   method: 'POST',
                   body: formData
               })
               .then(response => response.json())
               .then(data => {
                   if (data.success) {
                       showPopup(action === 'add' ? 
                           'Book added to your wishlist!' : 
                           'Book removed from your wishlist!');
                           
                       // Update wishlist counter if available
                       if (data.wishlist_count !== undefined) {
                           const wishlistCountElement = document.querySelector('.wishlist-count');
                           if (wishlistCountElement) {
                               wishlistCountElement.textContent = data.wishlist_count;
                           } else if (data.wishlist_count > 0) {
                               const wishlistLink = document.querySelector('a[href="/bookstore/wishlist.php"]');
                               const countSpan = document.createElement('span');
                               countSpan.className = 'wishlist-count';
                               countSpan.textContent = data.wishlist_count;
                               wishlistLink.appendChild(countSpan);
                           }
                       }
                   } else {
                       // Revert visual state if error
                       this.textContent = isInWishlist ? '❤️' : '🤍';
                       
                       showPopup('Error: ' + (data.message || 'Unknown error'));
                       
                       if (data.message === 'login_required') {
                           setTimeout(() => {
                               window.location.href = 'login.php';
                           }, 1500);
                       }
                   }
               })
               .catch(error => {
                   console.error('Error updating wishlist:', error);
                   
                   // Revert visual state if error
                   this.textContent = isInWishlist ? '❤️' : '🤍';
                   
                   showPopup('Failed to update wishlist. Please try again.');
               });
           });
       });
   });
   </script>
   
   <!-- Include books.js for additional functionality -->
   <script src="js/books.js"></script>
</body>
</html>
