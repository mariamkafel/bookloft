<?php
/**
 * Ajax helper functions for the books page
 * 
 * This file contains functions for handling AJAX requests from the books page
 */

/**
 * Handle AJAX request to get books with optional filtering and sorting
 *
 * @param mysqli $conn Database connection
 * @param array $params Request parameters
 * @param int|null $user_id Current user ID
 * @param array $wishlist_items User's wishlist items
 * @return array Array of books with wishlist status
 */
function handle_get_books_request($conn, $params, $user_id, $wishlist_items) {
    $search = isset($params['search']) ? $params['search'] : '';
    $genre = isset($params['genre']) ? $params['genre'] : '';
    $language = isset($params['language']) ? $params['language'] : '';
    $sort = isset($params['sort']) ? $params['sort'] : '';
    $type = isset($params['type']) ? $params['type'] : ''; // Optional type filter
    
    $books = get_books($conn, $search, $genre, $language, $sort, $type);
    
    // Add wishlist information to each book if user is logged in
    if ($user_id) {
        $wishlist_books = [];
        if (!empty($wishlist_items)) {
            foreach ($wishlist_items as $item) {
                $wishlist_books[] = (int)$item['book_id'];
            }
        }
        
        foreach ($books as &$book) {
            $book['in_wishlist'] = in_array((int)$book['id'], $wishlist_books);
        }
    }
    
    return $books;
}

/**
 * Get books with optional filtering and sorting
 *
 * @param mysqli $conn Database connection
 * @param string $search Search term for title or author
 * @param string $genre Genre filter
 * @param string $language Language filter
 * @param string $sort Sort option
 * @param string $type Book type filter (physical, ebook, etc.)
 * @return array Array of books
 */
function get_books($conn, $search = '', $genre = '', $language = '', $sort = '', $type = '') {
    $sql = "SELECT * FROM books WHERE 1=1";
    
    // Add type filter if specified
    if ($type) {
        $type = $conn->real_escape_string($type);
        $sql .= " AND type = '$type'";
    }
    
    if ($search) {
        $search = $conn->real_escape_string($search);
        $sql .= " AND (title LIKE '%$search%' OR author LIKE '%$search%')";
    }
    
    if ($genre) {
        $genre = $conn->real_escape_string($genre);
        $sql .= " AND genre = '$genre'";
    }
    
    if ($language) {
        $language = $conn->real_escape_string($language);
        $sql .= " AND language = '$language'";
    }
    
    if ($sort == 'title') {
        $sql .= " ORDER BY title ASC";
    } else if ($sort == 'price') {
        $sql .= " ORDER BY price ASC";
    } else {
        $sql .= " ORDER BY id DESC";
    }
    
    $result = $conn->query($sql);
    $books = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
    }
    
    return $books;
}

/**
 * Handle AJAX request to get a specific book by title
 *
 * @param mysqli $conn Database connection
 * @param string $title Book title
 * @param int|null $user_id Current user ID
 * @param array $wishlist_items User's wishlist items
 * @return array|null Book details or null if not found
 */
function handle_get_book_request($conn, $title, $user_id, $wishlist_items) {
    if (empty($title)) {
        return null;
    }
    
    $title = $conn->real_escape_string($title);
    $sql = "SELECT * FROM books WHERE title = '$title' LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $book = $result->fetch_assoc();
        
        // Check if book is in wishlist
        if ($user_id && !empty($wishlist_items)) {
            $book['in_wishlist'] = false;
            foreach ($wishlist_items as $item) {
                if ((int)$item['book_id'] === (int)$book['id']) {
                    $book['in_wishlist'] = true;
                    break;
                }
            }
        }
        
        return $book;
    }
    
    return null;
}

/**
 * Process AJAX requests for the books page
 *
 * @param mysqli $conn Database connection
 * @param array $get_params GET parameters
 * @param int|null $user_id Current user ID
 * @param array $wishlist_items User's wishlist items
 * @return mixed|null Response data if request was handled, null otherwise
 */
function process_ajax_requests($conn, $get_params, $user_id, $wishlist_items) {
    if (!isset($get_params['action'])) {
        return null;
    }
    
    header('Content-Type: application/json');
    
    switch ($get_params['action']) {
        case 'getBooks':
            return handle_get_books_request($conn, $get_params, $user_id, $wishlist_items);
            
        case 'getGenres':
            return get_unique_values($conn, 'genre');
            
        case 'getLanguages':
            return get_unique_values($conn, 'language');
            
        case 'getBook':
            $title = isset($get_params['title']) ? $get_params['title'] : '';
            return handle_get_book_request($conn, $title, $user_id, $wishlist_items);
            
        default:
            return null;
    }
}