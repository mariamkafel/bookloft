<?php
// Keep all the existing PHP code at the top unchanged
session_start();
require_once 'includes/db_connect.php';

// Check if user is logged in, redirect to login if not
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit;
}

// Check if cart is empty, redirect to cart if it is
if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
    header('Location: cart.php');
    exit;
}

// Get user details
$user_id = $_SESSION['user_id'];
$user_info = [];

try {
    $stmt = $conn->prepare("SELECT name, email, phone, address, city, state, zip FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user_info = $result->fetch_assoc();
    }
} catch(Exception $e) {
    error_log("Error fetching user info: " . $e->getMessage());
    $error_message = "There was an error retrieving your information. Please try again.";
}

// Calculate order total
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $itemPrice = isset($item['price']) ? $item['price'] : 0;
    $itemQuantity = isset($item['quantity']) ? $item['quantity'] : 1;
    $subtotal += $itemPrice * $itemQuantity;
}
$tax = $subtotal * 0.1;
$total = $subtotal + $tax;

// Process order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form inputs
    $errors = [];
    
    // Required fields
    $required_fields = ['name', 'email', 'phone', 'address', 'city', 'state', 'zip', 'payment_method'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    
    // Email validation
    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    // Phone validation - simple check for numeric and reasonable length
    if (!empty($_POST['phone']) && !preg_match('/^\d{10,15}$/', preg_replace('/[^0-9]/', '', $_POST['phone']))) {
        $errors[] = 'Please enter a valid phone number';
    }
    
    // Credit card validation if payment method is credit card
    if ($_POST['payment_method'] === 'credit_card') {
        // Validate card details
        if (empty($_POST['card_number'])) {
            $errors[] = 'Card number is required';
        } elseif (!preg_match('/^\d{15,16}$/', preg_replace('/\s+/', '', $_POST['card_number']))) {
            $errors[] = 'Please enter a valid card number';
        }
        
        if (empty($_POST['exp_month']) || empty($_POST['exp_year'])) {
            $errors[] = 'Expiration date is required';
        } else {
            // Check if expiration date is valid
            $current_month = date('n');
            $current_year = date('Y');
            
            if ($_POST['exp_year'] < $current_year || 
                ($_POST['exp_year'] == $current_year && $_POST['exp_month'] < $current_month)) {
                $errors[] = 'Card has expired';
            }
        }
        
        if (empty($_POST['cvv'])) {
            $errors[] = 'Security code (CVV) is required';
        } elseif (!preg_match('/^\d{3,4}$/', $_POST['cvv'])) {
            $errors[] = 'Please enter a valid security code';
        }
    }
    
    // If there are no errors, process the order
    if (empty($errors)) {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Format shipping address for storage
            $shipping_address = $_POST['address'] . ', ' . $_POST['city'] . ', ' . 
                             $_POST['state'] . ' ' . $_POST['zip'];
            
            // Insert into orders table
           
            $stmt = $conn->prepare("INSERT INTO orders (user_id, order_date, total_amount, status, shipping_address, payment_method) 
                        VALUES (?, NOW(), ?, 'processing', ?, ?)");


            $stmt->bind_param("idss", $user_id, $total, $shipping_address, $_POST['payment_method']);

           
            $stmt->execute();
            
            // Get the new order ID
            $order_id = $conn->insert_id;
            
            // Insert items into order_items table
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, book_id, quantity, price, item_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiids", $order_id, $book_id, $quantity, $price, $item_type); // ← Only 5 types
            
            
            foreach ($_SESSION['cart'] as $item) {
                $book_id = ($item['type'] === 'giftcard') ? NULL : ($item['id'] ?? 0);
                $quantity = $item['quantity'] ?? 1;
                $price = $item['price'] ?? 0;
                $item_type = $item['type'] ?? 'book';
            
                $stmt->bind_param("iiids", $order_id, $book_id, $quantity, $price, $item_type);
            

                $stmt->execute();
            }
            
            
            // Commit transaction
            $conn->commit();
            
            // Clear cart after successful order
            unset($_SESSION['cart']);
            
            // Store success message in session to display after redirect
            $_SESSION['order_success'] = true;
            $_SESSION['order_id'] = $order_id;
            
            // Redirect to main page instead of order confirmation
            header('Location: index.php');
            exit;
            
        } catch(Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            error_log("Order processing error: " . $e->getMessage());
            $error_message = "There was an error processing your order. Please try again.";
        }
    }
}

// Get cart count for header
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += isset($item['quantity']) ? $item['quantity'] : 1;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - The Book Loft</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/nav.css">
    <link rel="stylesheet" href="css/checkout.css">
</head>

<body>
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
    
    <main>
        <div class="checkout-container">
            <h1 class="page-title">Checkout</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form action="checkout.php" method="post" id="checkout-form" class="checkout-form">
                <div class="form-section">
                    <h2>Contact Information</h2>
                    <div class="form-row">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user_info['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-row">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_info['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-row">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_info['phone'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Shipping Address</h2>
                    <div class="form-row">
                        <label for="address">Street Address</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user_info['address'] ?? ''); ?>" required>
                    </div>
                    <div class="form-row">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user_info['city'] ?? ''); ?>" required>
                    </div>
                    <div class="form-row">
                        <label for="state">State/Province</label>
                        <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($user_info['state'] ?? ''); ?>" required>
                    </div>
                    <div class="form-row">
                        <label for="zip">ZIP/Postal Code</label>
                        <input type="text" id="zip" name="zip" value="<?php echo htmlspecialchars($user_info['zip'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Payment Method</h2>
                    <div class="payment-methods">
                        <div class="payment-method">
                            <input type="radio" id="credit_card" name="payment_method" value="credit_card" checked>
                            <label for="credit_card">Credit Card</label>
                        </div>
                        <div class="payment-method">
                            <input type="radio" id="paypal" name="payment_method" value="paypal">
                            <label for="paypal">PayPal</label>
                        </div>
                        <div class="payment-method">
                            <input type="radio" id="applepay" name="payment_method" value="applepay">
                            <label for="applepay">Apple Pay</label>
                        </div>
                    </div>
                    
                    <div id="credit-card-fields" class="credit-card-fields">
                        <div class="form-row">
                            <label for="card_number">Card Number</label>
                            <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456">
                        </div>
                        <div class="card-row">
                            <div class="form-row">
                                <label for="exp_month">Expiration Date</label>
                                <div class="expiry-date">
                                    <select id="exp_month" name="exp_month">
                                        <option value="">Month</option>
                                        <?php for($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo sprintf('%02d', $i); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <select id="exp_year" name="exp_year">
                                        <option value="">Year</option>
                                        <?php $year = date('Y'); for($i = $year; $i <= $year + 10; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <label for="cvv">CVV</label>
                                <input type="text" id="cvv" name="cvv" class="cvv-field" placeholder="123">
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="place-order-btn">Place Order</button>
            </form>
            
            <div class="order-summary">
                <h2>Order Summary</h2>
                <div class="cart-items">
                    <?php foreach($_SESSION['cart'] as $item): ?>
                        <div class="cart-item">
                            <?php if(isset($item['type']) && $item['type'] === 'giftcard'): ?>
                                <img src="imgs/giftcars.png" alt="Gift Card">
                                <div class="item-details">
                                    <div class="item-title">Gift Card ($<?php echo htmlspecialchars($item['value']); ?>)</div>
                                    <div class="item-price">$<?php echo htmlspecialchars($item['price']); ?></div>
                                </div>
                            <?php else: ?>
                                <?php if(isset($item['image']) && !empty($item['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                <?php else: ?>
                                    <img src="imgs/book-placeholder.jpg" alt="Book">
                                <?php endif; ?>
                                <div class="item-details">
                                    <div class="item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                    <div class="item-author">by <?php echo htmlspecialchars($item['author']); ?></div>
                                    <div class="item-quantity">Qty: <?php echo $item['quantity']; ?></div>
                                    <div class="item-price">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Tax (10%):</span>
                    <span>$<?php echo number_format($tax, 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <span>$<?php echo number_format($total, 2); ?></span>
                </div>
            </div>
        </div>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Show/hide credit card fields based on payment method selection
        const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
        const creditCardFields = document.getElementById('credit-card-fields');
        
        // Show credit card fields initially if credit card is selected
        if (document.getElementById('credit_card').checked) {
            creditCardFields.style.display = 'block';
        }
        
        paymentMethods.forEach(function(method) {
            method.addEventListener('change', function() {
                if (this.value === 'credit_card') {
                    creditCardFields.style.display = 'block';
                } else {
                    creditCardFields.style.display = 'none';
                }
            });
        });
        
        // Format credit card number with spaces
        const cardNumberInput = document.getElementById('card_number');
        if (cardNumberInput) {
            cardNumberInput.addEventListener('input', function() {
                let value = this.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                let formattedValue = '';
                
                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 4 === 0) {
                        formattedValue += ' ';
                    }
                    formattedValue += value[i];
                }
                
                this.value = formattedValue;
            });
        }
    });
    </script>
</body>
</html>