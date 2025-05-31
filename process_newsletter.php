<?php
require_once 'includes/db_connect.php';

// Check if email is provided
if (!isset($_POST['email'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Email is required'
    ]);
    exit;
}

$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid email address'
    ]);
    exit;
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT * FROM newsletter_subscribers WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo json_encode([
            'success' => true,
            'message' => 'You are already subscribed to our newsletter'
        ]);
        exit;
    }
    
    // Add email to database
    $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, subscribe_date) VALUES (?, NOW())");
    $stmt->execute([$email]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for subscribing to our newsletter!'
    ]);
} catch (PDOException $e) {
    // Handle database error
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ]);
}
exit;
?>