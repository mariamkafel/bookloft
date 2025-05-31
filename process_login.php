<?php
session_start();
require_once 'includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['login_error'] = "Invalid form submission";
        header("Location: login.php");
        exit();
    }
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = "All fields are required";
        header("Location: login.php");
        exit();
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_name'] = $user['username']; // For welcome message on index page
            $_SESSION['is_logged_in'] = true;
            
            // Handle remember me functionality
            if (isset($_POST['remember']) && $_POST['remember'] == 1) {
                $token = bin2hex(random_bytes(32));
                $expires = time() + (86400 * 30); // 30 days
                
                $expires_formatted = date('Y-m-d H:i:s', $expires);
                
                // Delete any existing tokens for this user
                $stmt = $conn->prepare("DELETE FROM user_tokens WHERE user_id = ?");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                
                // Insert new token
                $stmt = $conn->prepare("INSERT INTO user_tokens (user_id, token, expires) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $user['id'], $token, $expires_formatted);
                $stmt->execute();
                
                // Set secure cookie
                setcookie('remember_token', $token, $expires, '/', '', true, true);
            }
            
            // Redirect to dashboard or home page
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['login_error'] = "Invalid username/email or password";
            header("Location: login.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['login_error'] = "System error, please try again later";
        header("Location: login.php");
        exit();
    }
} else {
    // If not a POST request, redirect to login page
    header("Location: login.php");
    exit();
}