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

// Get user ID if logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Clear cart (and database if logged in)
$response = clear_cart($user_id ? $conn : null);

// Return response
echo json_encode($response);
?>