<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/nav.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
        }
        
       
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info span {
            margin-right: 20px;
        }
        
        .logout-btn {
            background-color: white;
            color: #4a90e2;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .logout-btn:hover {
            background-color: #f0f0f0;
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .dashboard-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .dashboard-card h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .user-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .detail-item {
            margin-bottom: 15px;
        }
        
        .detail-item h3 {
            font-size: 16px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .detail-item p {
            font-size: 18px;
            color: #333;
        }
        
        @media (max-width: 768px) {
            .user-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php
    session_start();
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        // Redirect to login page if not logged in
        header("Location: login.php");
        exit;
    }
    ?>
    
    <div class="navbar">
        <h1>My Dashboard</h1>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="dashboard-card">
            <h2>User Information</h2>
            <div class="user-details">
                <div class="detail-item">
                    <h3>Username</h3>
                    <p><?php echo htmlspecialchars($_SESSION['username'] ?? 'Not available'); ?></p>
                </div>
                <div class="detail-item">
                    <h3>Email</h3>
                    <p><?php echo htmlspecialchars($_SESSION['email'] ?? 'Not available'); ?></p>
                </div>
                <div class="detail-item">
                    <h3>Account Created</h3>
                    <p><?php echo isset($_SESSION['created_at']) ? date('F j, Y', strtotime($_SESSION['created_at'])) : 'Not available'; ?></p>
                </div>
                <div class="detail-item">
                    <h3>Last Login</h3>
                    <p><?php echo isset($_SESSION['last_login']) ? date('F j, Y, g:i a', strtotime($_SESSION['last_login'])) : 'Not available'; ?></p>
                </div>
            </div>
        </div>
        
        <div class="dashboard-card">
            <h2>Account Actions</h2>
            <div style="display: flex; gap: 15px;">
                <a href="edit_profile.php" style="text-decoration: none;">
                    <button style="background-color: #4a90e2; color: white; border: none; border-radius: 5px; padding: 10px 15px; cursor: pointer;">
                        Edit Profile
                    </button>
                </a>
                <a href="change_password.php" style="text-decoration: none;">
                    <button style="background-color: #6c757d; color: white; border: none; border-radius: 5px; padding: 10px 15px; cursor: pointer;">
                        Change Password
                    </button>
                </a>
            </div>
        </div>
    </div>
</body>
</html>