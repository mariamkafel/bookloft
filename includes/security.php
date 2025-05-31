<!-- includes/security.php -->
<?php
// Function to sanitize user input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Generate CSRF token
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }
    return true;
}

// Check for brute force attempts
function check_login_attempts($username) {
    global $pdo;
    
    // Get current attempts
    $stmt = $pdo->prepare("SELECT * FROM login_attempts WHERE username = ? AND attempt_time > (NOW() - INTERVAL 15 MINUTE)");
    $stmt->execute([$username]);
    $attempts = $stmt->rowCount();
    
    // If more than 5 attempts in 15 minutes, block
    if ($attempts >= 5) {
        return false;
    }
    
    return true;
}

// Record failed login attempt
function record_failed_attempt($username) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO login_attempts (username, attempt_time) VALUES (?, NOW())");
    $stmt->execute([$username]);
}
?>