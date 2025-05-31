<!-- includes/auth_functions.php -->
<?php
require_once 'db_connect.php';
require_once 'security.php';

// Get user by ID
function get_user_by_id($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get user by username or email
function get_user_by_username_or_email($username_or_email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username_or_email, $username_or_email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Check if username exists
function username_exists($username) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->rowCount() > 0;
}

// Check if email exists
function email_exists($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->rowCount() > 0;
}

// Update user profile
function update_user_profile($user_id, $username, $email) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
    return $stmt->execute([$username, $email, $user_id]);
}

// Update user password
function update_user_password($user_id, $new_password) {
    global $pdo;
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    return $stmt->execute([$hashed_password, $user_id]);
}
?>