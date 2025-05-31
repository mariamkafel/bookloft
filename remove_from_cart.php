<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'carthelper.php';

// Check for CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid CSRF token'
    ]);
    exit;
}

// Check if item index is provided
if (isset($_POST['index'])) {
    $index = intval($_POST['index']);
    
    // Get user ID if logged in
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Remove item from cart (and database if logged in)
    $response = remove_from_cart($index, $user_id ? $conn : null);
    
    // Return response
    echo json_encode($response);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Missing item index'
    ]);
}
?>