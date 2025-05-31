<?php
/**
 * Book helper functions
 * 
 * This file contains functions for fetching and filtering books from the database
 */

/**
 * Get books with optional filtering and sorting
 *
 * @param mysqli $conn Database connection
 * @param string $search Search term for title or author
 * @param string $genre Genre filter
 * @param string $language Language filter
 * @param string $sort Sort option
 * @return array Array of books
 */

/**
 * Get books with special offers (discounts)
 *
 * @param mysqli $conn Database connection
 * @return array Array of books with discounts
 */
function get_special_offer_books($conn) {
    $books = [];
    
    try {
        $sql = "SELECT * FROM books WHERE discount > 0";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $books[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching special offer books: " . $e->getMessage());
    }
    
    return $books;
}

/**
 * Get a specific book by ID
 *
 * @param mysqli $conn Database connection
 * @param int $book_id Book ID
 * @return array|null Book details or null if not found
 */
function get_book_by_id($conn, $book_id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM books WHERE id = ? ");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching book: " . $e->getMessage());
    }
    
    return null;
}

/**
 * Get a specific book by title
 *
 * @param mysqli $conn Database connection
 * @param string $title Book title
 * @return array|null Book details or null if not found
 */
function get_book_by_title($conn, $title) {
    try {
        $title = $conn->real_escape_string($title);
        $stmt = $conn->prepare("SELECT * FROM books WHERE title = ? LIMIT 1");
        $stmt->bind_param("s", $title);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching book: " . $e->getMessage());
    }
    
    return null;
}

/**
 * Get books by type and category
 *
 * @param mysqli $conn Database connection
 * @param string $type Book type (e.g., 'physical' or 'ebook')
 * @param string $category Book category
 * @return array Array of books
 */
function get_books_by_type_category($conn, $type, $category = '') {
    $books = [];
    
    try {
        $sql = "SELECT * FROM books WHERE type = ?";
        
        if (!empty($category)) {
            $sql .= " AND category = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $type, $category);
        } else {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $type);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
        
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching books by type: " . $e->getMessage());
    }
    
    return $books;
}

/**
 * Get bestseller books by type
 *
 * @param mysqli $conn Database connection
 * @param string $type Book type (e.g., 'physical' or 'ebook')
 * @return array Array of bestseller books
 */
function get_bestseller_books($conn, $type = 'physical') {
    $books = [];
    
    try {
        // Try with is_bestseller column if it exists
        $stmt = $conn->prepare("SELECT * FROM books WHERE is_bestseller = 1 AND type = ?");
        $stmt->bind_param("s", $type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // If no results or query fails, fall back to alternative (all books of that type)
        if ($result === false || $result->num_rows === 0) {
            $stmt->close();
            $stmt = $conn->prepare("SELECT * FROM books WHERE type = ?");
            $stmt->bind_param("s", $type);
            $stmt->execute();
            $result = $stmt->get_result();
        }
        
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
        
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching bestseller books: " . $e->getMessage());
    }
    
    return $books;
}

/**
 * Get unique values for a column to use in filters
 *
 * @param mysqli $conn Database connection
 * @param string $column Column name
 * @return array Array of unique values
 */
function get_unique_values($conn, $column) {
    $values = [];
    
    try {
        $column = $conn->real_escape_string($column);
        $sql = "SELECT DISTINCT $column FROM books WHERE $column IS NOT NULL AND $column != '' ORDER BY $column ASC";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $values[] = $row[$column];
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching unique values: " . $e->getMessage());
    }
    
    return $values;
}
function filter_books_by_language($conn, $language) {
    $sql = "SELECT * FROM books WHERE language = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $language);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $books = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
    }
    
    return $books;
}