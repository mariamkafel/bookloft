<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'carthelper.php';


// Check if user is logged in - standardize to use user_id for login check
$user_logged_in = isset($_SESSION['user_id']);
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Check if item_type is provided
if (isset($_POST['item_type']) && $_POST['item_type'] === 'giftcard') {
    // GIFT CARD HANDLING
    
    // Validate required gift card fields
    if (!isset($_POST['recipient_email']) || !isset($_POST['sender_email']) || !isset($_POST['value'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing gift card information'
        ]);
        exit;
    }
    
    // Sanitize and validate inputs
    $recipient_email = filter_var($_POST['recipient_email'], FILTER_SANITIZE_EMAIL);
    $sender_email = filter_var($_POST['sender_email'], FILTER_SANITIZE_EMAIL);
    $value = (int)$_POST['value'];
    $message = isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '';
    
    if (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL) || 
        !filter_var($sender_email, FILTER_VALIDATE_EMAIL) || 
        !in_array($value, [10, 15, 20, 25, 50, 75, 100, 250, 500, 1000])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid gift card information'
        ]);
        exit;
    }
    
    // Create gift card item with unique ID
    $giftcard_id = 'giftcard_' . time() . '_' . mt_rand(1000, 9999);
    
    // Create gift card item
    $giftcard = [
        'id' => $giftcard_id,
        'type' => 'giftcard',
        'recipient_email' => $recipient_email,
        'sender_email' => $sender_email,
        'value' => $value,
        'message' => $message,
        'price' => $value,
        'quantity' => 1
    ];
    
    // Add to cart (and database if logged in)
    $response = add_to_cart($giftcard, 1, $user_logged_in ? $conn : null);
    
    // Return response
    echo json_encode($response);
    exit;
} 
// BOOK HANDLING (original code)
else if (isset($_POST['book_id'])) {
    $book_id = intval($_POST['book_id']);

    // Fetch book information from database
    try {
        if (isset($conn)) {
            // MySQLi connection
            $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
            $stmt->bind_param("i", $book_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $book = $result->fetch_assoc();
        } else {
            throw new Exception("No database connection available");
        }

        if (!$book) {
            echo json_encode([
                'success' => false,
                'message' => 'Book not found'
            ]);
            exit;
        }

        $title = $book['title'];
        $author = $book['author'];
        $price = $book['price'];
        $image = isset($book['image_link']) ? $book['image_link'] : (isset($book['image']) ? $book['image'] : '');
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
        exit;
    }

    // Create book item
    $book_item = [
        'id' => $book_id,
        'type' => 'book',
        'title' => $title,
        'author' => $author,
        'price' => $price,
        'image' => $image,
        'quantity' => 1
    ];
    
    // Add to cart (and database if logged in)
    $response = add_to_cart($book_item, 1, $user_logged_in ? $conn : null);
    
    // Get updated cart count
    $cart_count = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $cart_count += isset($item['quantity']) ? $item['quantity'] : 1;
        }
    }
    
    // Add cart count to response
    $response['cart_count'] = $cart_count;
    
    // Return response
    echo json_encode($response);
    exit;
} else {
    // Invalid request
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request. Missing book_id or item_type.'
    ]);
    exit;
}
?>