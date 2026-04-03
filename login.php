<?php
session_start();
include("./db/connection.php");
$msg = "";

// Check for logout message
if (isset($_GET['message']) && $_GET['message'] == 'logout') {
    $logout_user = $_GET['user'] ?? '';
    $msg = "<div class='alert alert-info'>
        <i class='fas fa-check-circle'></i> 
        " . htmlspecialchars($logout_user) . "
    </div>";
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $username = trim($_POST['username']);
    $password = md5($_POST['password']);
    $role = $_POST['role'];

    // Clear any previous messages
    $msg = "";

    // Log login attempt
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    if ($role == "admin") {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username=? AND password=?");
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['admin'] = $user['username'];
            
            try {
                // Check if action column exists
                $check_column = $pdo->prepare("SHOW COLUMNS FROM login_activity LIKE 'action'");
                $check_column->execute();
                $column_exists = $check_column->fetch();
                
                if ($column_exists) {
                    // Log login activity with action column
                    $log_stmt = $pdo->prepare("INSERT INTO login_activity (user_id, user_type, login_time, ip_address, action) VALUES (?, 'admin', NOW(), ?, 'login')");
                    $log_stmt->execute([$user['username'], $ip_address]);
                } else {
                    // Log login activity without action column
                    $log_stmt = $pdo->prepare("INSERT INTO login_activity (user_id, user_type, login_time, ip_address) VALUES (?, 'admin', NOW(), ?)");
                    $log_stmt->execute([$user['username'], $ip_address]);
                }
            } catch (Exception $e) {
                // Silently continue even if logging fails
                error_log("Login activity logging failed: " . $e->getMessage());
            }
            
            header("Location: admin/dashboard.php");
            exit;
        } else {
            $msg = "<div class='alert alert-danger'>
                <i class='fas fa-exclamation-triangle'></i> 
                Invalid Admin Credentials
            </div>";
        }
    } else {
        $stmt = $pdo->prepare("SELECT * FROM volunteers WHERE volunteer_id=? AND password=?");
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch();
        if ($user) {
            // Check if volunteer is deactivated
            $status = $user['status'] ?? 'active';
            if ($status === 'inactive') {
                $msg = "<div class='alert alert-danger'>
                    <i class='fas fa-ban'></i> 
                    Your account has been deactivated. Please contact the administrator to reactivate it.
                </div>";
            } else {
                $_SESSION['volunteer'] = $user['volunteer_id'];
                
                try {
                    // Check if action column exists
                    $check_column = $pdo->prepare("SHOW COLUMNS FROM login_activity LIKE 'action'");
                    $check_column->execute();
                    $column_exists = $check_column->fetch();
                    
                    if ($column_exists) {
                        // Log login activity with action column
                        $log_stmt = $pdo->prepare("INSERT INTO login_activity (user_id, user_type, login_time, ip_address, action) VALUES (?, 'volunteer', NOW(), ?, 'login')");
                        $log_stmt->execute([$user['volunteer_id'], $ip_address]);
                    } else {
                        // Log login activity without action column
                        $log_stmt = $pdo->prepare("INSERT INTO login_activity (user_id, user_type, login_time, ip_address) VALUES (?, 'volunteer', NOW(), ?)");
                        $log_stmt->execute([$user['volunteer_id'], $ip_address]);
                    }
                } catch (Exception $e) {
                    // Silently continue even if logging fails
                    error_log("Login activity logging failed: " . $e->getMessage());
                }
                
                header("Location: volunteer/dashboard.php");
                exit;
            }
        } else {
            $msg = "<div class='alert alert-danger'>
                <i class='fas fa-exclamation-triangle'></i> 
                Invalid Volunteer ID or Password
            </div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Navneet College of Arts ,Science & Commerce.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
        }
        .login-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .nav-menu {
            background: #343a40;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .nav-menu a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
            padding: 8px 15px;
            border-radius: 4px;
        }
        .nav-menu a:hover {
            background: #495057;
            color: #ffc107;
        }
        .alert i {
            margin-right: 8px;
        }
        .college-logo {
            height: 60px;
            width: auto;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <div class="brand-container">
                <a class="navbar-brand" href="index.php">
                    <img src="./assets/images/nss_logo.png" alt="NSS Logo" height="50" class="me-2">
                    Navneet College of Arts ,Science & Commerce.
                </a>
                <img src="./assets/images/college_logo.png" alt="College Logo" class="college-logo">
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto" style="font-size: 1.1rem;">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                   <!-- <li class="nav-item"><a class="nav-link" href="#gallery">Gallery</a></li>  -->
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="login-container">
            <div class="card login-card">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">
                        <i class="fas fa-lock text-primary"></i> Login
                    </h2>
                    <?= $msg; ?>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-user me-1"></i> Login As
                            </label>
                            <select name="role" class="form-select" required onchange="updatePlaceholder()">
                                <option value="volunteer">Volunteer</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label" id="usernameLabel">
                                <i class="fas fa-id-card me-1"></i> Volunteer ID
                            </label>
                            <input type="text" name="username" class="form-control" placeholder="Enter your Volunteer ID" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-key me-1"></i> Password
                            </label>
                            <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-2">
                            <i class="fas fa-sign-in-alt me-2"></i> Login
                        </button>
                        <!-- Add this in the login form after the submit button -->
<div class="text-center mt-3">
    <p><a href="forgot_password.php" class="text-decoration-none">Forgot your password?</a></p>
</div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p>Don't have an account? 
                            <a href="register.php" class="text-decoration-none">
                                <i class="fas fa-user-plus me-1"></i> Register as Volunteer
                            </a>
                        </p>
                        <p>
                            <a href="index.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-1"></i> Back to Home
                            </a>
                        </p>
                    </div>
                </div>
            </div>
            

    <script>
        function updatePlaceholder() {
            const role = document.querySelector('select[name="role"]').value;
            const input = document.querySelector('input[name="username"]');
            const label = document.getElementById('usernameLabel');
            
            if (role === 'admin') {
                label.innerHTML = '<i class="fas fa-user me-1"></i> Username';
                input.placeholder = 'Enter your username';
            } else {
                label.innerHTML = '<i class="fas fa-id-card me-1"></i> Volunteer ID';
                input.placeholder = 'Enter your Volunteer ID';
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', updatePlaceholder);
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>