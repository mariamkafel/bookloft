</main>
    
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
            <div class="footer-links">
                <div class="footer-section">
                    <h3>Shop</h3>
                    <ul>
                        <li><a href="/books.php">Books</a></li>
                        <li><a href="/ebooks.php">eBooks</a></li>
                        <li><a href="/special_offers.php">Special Offers</a></li>
                        <li><a href="/bestsellers.php">Best Sellers</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Account</h3>
                    <ul>
                        <?php if (is_logged_in()): ?>
                            <li><a href="/profile.php">My Profile</a></li>
                            <li><a href="/orders.php">My Orders</a></li>
                            <li><a href="/wishlist.php">My Wishlist</a></li>
                        <?php else: ?>
                            <li><a href="/login.php">Login</a></li>
                            <li><a href="/register.php">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>About Us</h3>
                    <ul>
                        <li><a href="/about.php">Our Story</a></li>
                        <li><a href="/contact.php">Contact Us</a></li>
                        <li><a href="/privacy.php">Privacy Policy</a></li>
                        <li><a href="/terms.php">Terms & Conditions</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> The Book Loft. All rights reserved.</p>
                <div class="social-icons">
                    <a href="#">
                        <img src="/imgs/whatsapp.png" alt="WhatsApp">
                    </a>
                    <a href="#">
                        <img src="/imgs/instagram.png" alt="Instagram">
                    </a>
                </div>
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

    <!-- Common JavaScript -->
    <script>
    $(document).ready(function() {
        // Close flash messages
        setTimeout(function() {
            $('.flash-message').fadeOut('slow');
        }, 3000);
        
        // Newsletter form submission with AJAX
        $('#newsletter-form').submit(function(e) {
            e.preventDefault();
            var email = $(this).find('input[name="email"]').val();
            
            $.ajax({
                url: 'process_newsletter.php',
                type: 'POST',
                data: {
                    email: email
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
    });
    </script>
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>