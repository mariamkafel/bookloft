<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'carthelper.php';

// Initialize response array
$response = ['success' => false];

// Check for CSRF token first
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $response['error'] = 'Invalid CSRF token';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Check if cart exists
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    // Get user ID if logged in
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // For updating by index (preferred method)
    if (isset($_POST['index']) && isset($_POST['change']) && array_key_exists($_POST['index'], $_SESSION['cart'])) {
        $index = $_POST['index'];
        $change = intval($_POST['change']);
        
        // Update cart quantity using helper function
        if (function_exists('update_cart_quantity')) {
            $response = update_cart_quantity($index, $change, $user_id ? $conn : null);
        } else {
            // Fallback direct implementation
            // Update quantity
            $currentQty = isset($_SESSION['cart'][$index]['quantity']) ? $_SESSION['cart'][$index]['quantity'] : 1;
            $newQty = $currentQty + $change;
            
            // Ensure quantity is at least 1
            if ($newQty < 1) {
                $newQty = 1;
            }
            
            $_SESSION['cart'][$index]['quantity'] = $newQty;
            $response['success'] = true;
        }
    }
    // Fallback to old method by item_id
    else if (isset($_POST['item_id']) && isset($_POST['change'])) {
        $item_id = $_POST['item_id'];
        $change = intval($_POST['change']);
        
        // Find and update the item
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $item_id) {
                // Update quantity
                $currentQty = isset($item['quantity']) ? $item['quantity'] : 1;
                $newQty = $currentQty + $change;
                
                // Ensure quantity is at least 1
                if ($newQty < 1) {
                    $newQty = 1;
                }
                
                $item['quantity'] = $newQty;
                $response['success'] = true;
                break;
            }
        }
    } else {
        $response['error'] = 'Missing required parameters';
    }
}

// Count items in cart
$count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $count += isset($item['quantity']) ? $item['quantity'] : 1;
    }
}

$response['cart_count'] = $count;

// Send response
header('Content-Type: application/json');
echo json_encode($response);
?>