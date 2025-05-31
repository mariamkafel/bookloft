<?php
require_once 'includes/db_connect.php';

// Check if book_id is provided
if (!isset($_GET['book_id'])) {
    echo '<p class="error-message">Book ID is required</p>';
    exit;
}

$book_id = $_GET['book_id'];

try {
    // Get book details from database
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$book) {
        echo '<p class="error-message">Book not found</p>';
        exit;
    }
    
    // Format book details
    ?>
    <div class="book-details">
        <div class="book-image">
            <img src="<?php echo htmlspecialchars($book['image_path']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
        </div>
        <div class="book-info-details">
            <h2><?php echo htmlspecialchars($book['title']); ?></h2>
            <p class="book-author">By <?php echo htmlspecialchars($book['author']); ?></p>
            <div class="book-meta">
                <span>Publication Year: <?php echo htmlspecialchars($book['publication_year']); ?></span>
                <span>Type: <?php echo ucfirst(htmlspecialchars($book['type'])); ?></span>
                <?php if ($book['is_bestseller']): ?>
                    <span class="bestseller-badge">Bestseller</span>
                <?php endif; ?>
            </div>
            <div class="book-price">
                <span>$<?php echo number_format($book['price'], 2); ?></span>
            </div>
            <div class="book-description">
                <h3>Description</h3>
                <p><?php echo htmlspecialchars($book['description'] ?? 'No description available.'); ?></p>
            </div>
            <div class="book-actions">
                <button class="add-to-cart" data-id="<?php echo $book['id']; ?>">Add to Cart</button>
                <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in']): ?>
                    <button class="add-to-wishlist" data-id="<?php echo $book['id']; ?>">Add to Wishlist</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
    $(document).ready(function() {
        $('.add-to-cart').click(function() {
            var bookId = $(this).data('id');
            
            $.ajax({
                url: 'add_to_cart.php',
                type: 'POST',
                data: {
                    book_id: bookId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#cart-count').text(response.cart_count);
                        alert('Book added to cart!');
                        $('#book-modal').hide();
                    } else {
                        if (response.message === 'login_required') {
                            window.location.href = 'login.php';
                        } else {
                            alert(response.message);
                        }
                    }
                },
                error: function() {
                    alert('Error adding to cart. Please try again.');
                }
            });
        });
        
        $('.add-to-wishlist').click(function() {
            var bookId = $(this).data('id');
            
            $.ajax({
                url: 'add_to_wishlist.php',
                type: 'POST',
                data: {
                    book_id: bookId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Book added to wishlist!');
                        $('#book-modal').hide();
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('Error adding to wishlist. Please try again.');
                }
            });
        });
    });
    </script>
    <?php
} catch (PDOException $e) {
    echo '<p class="error-message">An error occurred. Please try again later.</p>';
}
?>