<!-- includes/session.php -->
<?php
// Start session if it hasn't been started
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    
    $lifetime = 3600; // Session lifetime in seconds (1 hour)
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => '/',
        'secure' => true,  // Only transmit cookie over HTTPS
        'httponly' => true, // Cookie inaccessible to JavaScript
        'samesite' => 'Lax' // Prevents CSRF attacks
    ]);
    
    session_start();
    
    // Regenerate session ID periodically to prevent fixation attacks
    if (!isset($_SESSION['last_regeneration'])) {
        regenerate_session_id();
    } else {
        $interval = 60 * 30; // Regenerate every 30 minutes
        if (time() - $_SESSION['last_regeneration'] >= $interval) {
            regenerate_session_id();
        }
    }
}

// Function to regenerate session ID
function regenerate_session_id() {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
}

// Require user to be logged in for protected pages
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['login_error'] = "You must be logged in to access that page";
        header("Location: login.php");
        exit();
    }
}
?>