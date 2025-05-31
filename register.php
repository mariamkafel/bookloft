<?php
session_start();
// Generate CSRF token if it doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create an Account - The Book Loft</title>
    <style>
        /* Modern CSS Reset */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f5ff;
            color: #333;
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            width: 100%;
            max-width: 500px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px #30214d;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        
        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background:#30214d;
        }
        
        h2 {
            color:#30214d;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
            font-weight: 600;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #842029;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color:rgb(109, 78, 150);
            box-shadow: 0 0 0 3px rgba(109, 82, 144, 0.2);
        }
        
        .form-text {
            display: block;
            margin-top: 6px;
            font-size: 13px;
            color: #6c757d;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            background:#30214d;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn:hover {
            background: linear-gradient(to right, #5a4277, #6d5290);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(109, 82, 144, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        p {
            margin-top: 24px;
            text-align: center;
            color: #6c757d;
            font-size: 15px;
        }
        
        a {
            color:#30214d;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        a:hover {
            color: #8a6db1;
            text-decoration: underline;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            font-size: 24px;
            color:#30214d;
            letter-spacing: 1px;
        }
        
        /* Password strength indicator */
        .password-strength {
            height: 5px;
            margin-top: 8px;
            border-radius: 3px;
            background-color: #eee;
            overflow: hidden;
        }
        
        .password-strength-meter {
            height: 100%;
            width: 0;
            transition: width 0.3s ease;
        }
        
        .weak {
            width: 33%;
            background-color: #ff4d4d;
        }
        
        .medium {
            width: 66%;
            background-color: #ffd633;
        }
        
        .strong {
            width: 100%;
            background-color: #47d147;
        }
        
        /* Responsive adjustments */
        @media (max-width: 576px) {
            .container {
                padding: 30px 20px;
            }
            
            h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>THE BOOK LOFT</h1>
        </div>
        
        <h2>Create an Account</h2>
        
        <?php
        // Display error messages if any
        if (isset($_SESSION['errors']) && is_array($_SESSION['errors'])) {
            foreach ($_SESSION['errors'] as $error) {
                echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
            }
            unset($_SESSION['errors']);
        }
        ?>
        
        <form action="process_registration.php" method="POST" onsubmit="return validateRegistrationForm()">
            <!-- CSRF Protection -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required onkeyup="checkPasswordStrength()">
                <div class="password-strength">
                    <div class="password-strength-meter" id="password-strength-meter"></div>
                </div>
                <small class="form-text">Password must be at least 8 characters long.</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn">Create Account</button>
        </form>
        
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>

    <script>
    function validateRegistrationForm() {
        var username = document.getElementById('username').value;
        var email = document.getElementById('email').value;
        var password = document.getElementById('password').value;
        var confirmPassword = document.getElementById('confirm_password').value;
        
        if (username.trim() === '') {
            alert('Please enter a username');
            return false;
        }
        
        if (username.trim().length < 3) {
            alert('Username must be at least 3 characters long');
            return false;
        }
        
        if (email.trim() === '') {
            alert('Please enter an email');
            return false;
        }
        
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('Please enter a valid email address');
            return false;
        }
        
        if (password.trim() === '') {
            alert('Please enter a password');
            return false;
        }
        
        if (password.length < 8) {
            alert('Password must be at least 8 characters long');
            return false;
        }
        
        if (password !== confirmPassword) {
            alert('Passwords do not match');
            return false;
        }
        
        return true;
    }
    
    function checkPasswordStrength() {
        var password = document.getElementById('password').value;
        var meter = document.getElementById('password-strength-meter');
        
        // Remove all classes
        meter.className = 'password-strength-meter';
        
        // Check password strength
        if (password.length === 0) {
            meter.style.width = '0';
        } else if (password.length < 8) {
            meter.classList.add('weak');
        } else if (password.length < 12) {
            meter.classList.add('medium');
        } else {
            meter.classList.add('strong');
        }
    }
    </script>
</body>
</html>