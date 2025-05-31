<?php
// Start session to manage user wishlists
session_start();
require_once 'includes/db_connect.php';

// Check if user is logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Response array
$response = [
    'success' => false,
    'items' => []
];

if ($user_id) {
    try {
        // Get all book IDs in the user's wishlist
        $stmt = $conn->prepare("SELECT book_id FROM wishlist WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $wishlist_items = [];
        while ($row = $result->fetch_assoc()) {
            $wishlist_items[] = (int)$row['book_id'];
        }
        
        $response['success'] = true;
        $response['items'] = $wishlist_items;
        $response['count'] = count($wishlist_items);
        
        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = 'System error: ' . $e->getMessage();
        error_log('Wishlist error: ' . $e->getMessage());
    }
} else {
    $response['message'] = 'User not logged in';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>