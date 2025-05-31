<?php
// Start a session to maintain user data
session_start();

// Include database connection
require_once 'includes/db_connect.php';
require_once 'wishlisthelper.php';

// Get user ID if logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Process the request based on the action parameter
if (isset($_GET['action'])) {
    $response = array();
    
    switch ($_GET['action']) {
        case 'getFeaturedBooks':
            // Fetch featured books from database
            $query = "SELECT b.id, b.title, b.author, b.price, b.image_link 
                     FROM books b 
                     WHERE b.featured = 1 
                     ORDER BY b.title
                     LIMIT 8";
            
            $result = mysqli_query($conn, $query);
            
            if (!$result) {
                $response = array('error' => mysqli_error($conn));
            } else {
                $books = array();
                
                while ($row = mysqli_fetch_assoc($result)) {
                    // Check if book is in user's wishlist
                    $row['in_wishlist'] = false;
                    
                    if ($user_id) {
                        $wishlist_query = "SELECT * FROM wishlist WHERE user_id = ? AND book_id = ?";
                        $stmt = mysqli_prepare($conn, $wishlist_query);
                        mysqli_stmt_bind_param($stmt, "ii", $user_id, $row['id']);
                        mysqli_stmt_execute($stmt);
                        $wishlist_result = mysqli_stmt_get_result($stmt);
                        
                        if (mysqli_num_rows($wishlist_result) > 0) {
                            $row['in_wishlist'] = true;
                        }
                    }
                    
                    $books[] = $row;
                }
                
                $response = $books;
            }
            break;
            
        case 'getBookDetails':
            // Ensure book ID is provided
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                $response = array('error' => 'Invalid book ID');
                break;
            }
            
            $book_id = (int)$_GET['id'];
            
            // Fetch book details
            $query = "SELECT b.*, g.name as genre 
                     FROM books b 
                     LEFT JOIN genres g ON b.genre_id = g.id 
                     WHERE b.id = ?";
            
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $book_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (!$result || mysqli_num_rows($result) === 0) {
                $response = array('error' => 'Book not found');
            } else {
                $book = mysqli_fetch_assoc($result);
                
                // Check if book is in user's wishlist
                $book['in_wishlist'] = false;
                
                if ($user_id) {
                    $wishlist_query = "SELECT * FROM wishlist WHERE user_id = ? AND book_id = ?";
                    $stmt = mysqli_prepare($conn, $wishlist_query);
                    mysqli_stmt_bind_param($stmt, "ii", $user_id, $book_id);
                    mysqli_stmt_execute($stmt);
                    $wishlist_result = mysqli_stmt_get_result($stmt);
                    
                    if (mysqli_num_rows($wishlist_result) > 0) {
                        $book['in_wishlist'] = true;
                    }
                }
                
                $response = $book;
            }
            break;
            
        default:
            $response = array('error' => 'Invalid action');
    }
    
    // Return response as JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

?>