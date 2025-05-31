<?php
// Function to get user's wishlist items
function get_user_wishlist($conn, $user_id) {
    $items = [];
    
    if (!$user_id) {
        return $items;
    }
    
    try {
        $stmt = $conn->prepare("SELECT w.id, w.book_id, w.added_date, b.title, b.author, b.price, b.image_link 
                               FROM wishlist w 
                               JOIN books b ON w.book_id = b.id 
                               WHERE w.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        
        $stmt->close();
    } catch (Exception $e) {
        error_log('Error getting wishlist: ' . $e->getMessage());
    }
    
    return $items;
}
?>