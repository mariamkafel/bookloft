<?php
session_start();
require_once 'includes/db_connect.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'wishlist_count' => 0
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'login_required';
    echo json_encode($response);
    exit;
}

// Check CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $response['message'] = 'Invalid security token';
    echo json_encode($response);
    exit;
}

// Check if the request is POST and has book_id
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_id'])) {
    $user_id = $_SESSION['user_id'];
    $book_id = intval($_POST['book_id']);
    $action = isset($_POST['action']) ? $_POST['action'] : 'add';

    try {
        if ($action === 'add') {
            // Check if book exists
            $check_book = $conn->prepare("SELECT id FROM books WHERE id = ?");
            $check_book->bind_param("i", $book_id);
            $check_book->execute();
            $book_result = $check_book->get_result();
            
            if ($book_result->num_rows === 0) {
                $response['message'] = 'Book not found';
            } else {
                // Check if already in wishlist
                $check_wishlist = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND book_id = ?");
                $check_wishlist->bind_param("ii", $user_id, $book_id);
                $check_wishlist->execute();
                $wishlist_result = $check_wishlist->get_result();
                
                if ($wishlist_result->num_rows > 0) {
                    $response['message'] = 'Book is already in your wishlist';
                    $response['success'] = true;
                } else {
                    // Add to wishlist
                    $add_to_wishlist = $conn->prepare("INSERT INTO wishlist (user_id, book_id) VALUES (?, ?)");
                    $add_to_wishlist->bind_param("ii", $user_id, $book_id);

                    
                    if ($add_to_wishlist->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Book added to wishlist successfully';
                    } else {
                        $response['message'] = 'Error adding book to wishlist';
                    }
                }
            }
        } elseif ($action === 'remove') {
            // Remove from wishlist
            $remove_from_wishlist = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND book_id = ?");
            $remove_from_wishlist->bind_param("ii", $user_id, $book_id);
            
            if ($remove_from_wishlist->execute()) {
                $response['success'] = true;
                $response['message'] = 'Book removed from wishlist successfully';
            } else {
                $response['message'] = 'Error removing book from wishlist';
            }
        }
        
        // Get updated wishlist count
        $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        $response['wishlist_count'] = $count_row['count'];
        
    } catch (Exception $e) {
        $response['message'] = 'System error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request';
}

echo json_encode($response);
exit;
?>