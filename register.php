<?php
session_start();
include("./db/connection.php");
$msg = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department']);
    $year = $_POST['year'];
    $password = md5($_POST['password']);
    $profile_image = null;

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "<div class='alert alert-danger'>Please enter a valid email address.</div>";
    } else {
        // Check if email already exists
        $check_email = $pdo->prepare("SELECT id FROM volunteers WHERE email = ?");
        $check_email->execute([$email]);
        
        if ($check_email->fetch()) {
            $msg = "<div class='alert alert-danger'>Email already registered. Please use a different email.</div>";
        } else {
            // Handle profile image upload
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = './assets/profile_images/';
                
                // Create directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                $fileExtension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                
                if (in_array($fileExtension, $allowedTypes)) {
                    if ($_FILES['profile_image']['size'] <= 2 * 1024 * 1024) { // 2MB
                        $fileName = 'profile_' . time() . '_' . uniqid() . '.' . $fileExtension;
                        $targetPath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
                            $profile_image = $fileName;
                        }
                    } else {
                        $msg = "<div class='alert alert-warning'>Profile image too large. Maximum size is 2MB.</div>";
                    }
                } else {
                    $msg = "<div class='alert alert-warning'>Invalid image format. Please use JPG, PNG, or GIF.</div>";
                }
            }
            
            // Generate unique Volunteer ID
            $volunteer_id = "V" . strtoupper(bin2hex(random_bytes(3)));

            try {
                $stmt = $pdo->prepare("INSERT INTO volunteers (volunteer_id, name, email, phone, department, year, password, profile_image) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$volunteer_id, $name, $email, $phone, $department, $year, $password, $profile_image]);
                
                // Send welcome email
                require_once "./includes/EmailSender.php";
                $emailSender = new EmailSender();
                $emailResult = $emailSender->sendWelcomeEmail($name, $email, $volunteer_id);
                
                $msg = "<div class='alert alert-success'>
                    <h5>Registration Successful!</h5>
                    <p class='mb-1'><strong>Your Volunteer ID:</strong> <code>$volunteer_id</code></p>
                    <p class='mb-0'><strong>Please remember this ID for login.</strong></p>";
                
                if ($emailResult['success']) {
                    $msg .= "<p class='mt-2'><i class='fas fa-envelope text-success'></i> Welcome email sent to your registered email address.</p>";
                } else {
                    $msg .= "<p class='mt-2 text-warning'><i class='fas fa-exclamation-triangle'></i> Registration successful, but email notification failed.</p>";
                }
                
                $msg .= "</div>";
                
                // Clear form
                $_POST = array();
                
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    // Duplicate volunteer_id, try again
                    $volunteer_id = "V" . strtoupper(bin2hex(random_bytes(3)));
                    $stmt->execute([$volunteer_id, $name, $email, $phone, $department, $year, $password, $profile_image]);
                    
                    // Send welcome email for retry
                    require_once "./includes/EmailSender.php";
                    $emailSender = new EmailSender();
                    $emailResult = $emailSender->sendWelcomeEmail($name, $email, $volunteer_id);
                    
                    $msg = "<div class='alert alert-success'>
                        <h5>Registration Successful!</h5>
                        <p class='mb-1'><strong>Your Volunteer ID:</strong> <code>$volunteer_id</code></p>
                        <p class='mb-0'><strong>Please remember this ID for login.</strong></p>";
                    
                    if ($emailResult['success']) {
                        $msg .= "<p class='mt-2'><i class='fas fa-envelope text-success'></i> Welcome email sent to your registered email address.</p>";
                    } else {
                        $msg .= "<p class='mt-2 text-warning'><i class='fas fa-exclamation-triangle'></i> Registration successful, but email notification failed.</p>";
                    }
                    
                    $msg .= "</div>";
                    
                    $_POST = array();
                } else {
                    $msg = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Navneet College of Arts ,Science & Commerce.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .register-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
        }
        .register-card {
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
        .college-logo {
            height: 60px;
            width: auto;
            border-radius: 5px;
        }
        
    </style>
</head>
<body>
     <!-- Fixed Navigation -->
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
        <div class="register-container">
            <div class="card register-card">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">👤 Volunteer Registration</h2>
                    <?= $msg; ?>
                    
                    <!-- Registration Form -->
                    <form method="post" enctype="multipart/form-data" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Email Address *</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" pattern="[0-9]{10}">
                            <div class="form-text">10-digit phone number (optional)</div>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Department *</label>
                            <select name="department" class="form-select" required>
                                <option value="">Select Department</option>
                                <option value="Computer Science" <?= ($_POST['department'] ?? '') == 'Computer Science' ? 'selected' : '' ?>>Computer Science</option>
                                <option value="Electronics" <?= ($_POST['department'] ?? '') == 'Electronics' ? 'selected' : '' ?>>Electronics</option>
                                <option value="Mechanical" <?= ($_POST['department'] ?? '') == 'Mechanical' ? 'selected' : '' ?>>Mechanical</option>
                                <option value="Civil" <?= ($_POST['department'] ?? '') == 'Civil' ? 'selected' : '' ?>>Civil</option>
                                <option value="Electrical" <?= ($_POST['department'] ?? '') == 'Electrical' ? 'selected' : '' ?>>Electrical</option>
                                <option value="Science" <?= ($_POST['department'] ?? '') == 'Science' ? 'selected' : '' ?>>Science</option>
                                <option value="Arts" <?= ($_POST['department'] ?? '') == 'Arts' ? 'selected' : '' ?>>Arts</option>
                                <option value="Commerce" <?= ($_POST['department'] ?? '') == 'Commerce' ? 'selected' : '' ?>>Commerce</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Year *</label>
                            <select name="year" class="form-select" required>
                                <option value="">Select Year</option>
                                <option value="FY" <?= ($_POST['year'] ?? '') == 'FY' ? 'selected' : '' ?>>First Year (FY)</option>
                                <option value="SY" <?= ($_POST['year'] ?? '') == 'SY' ? 'selected' : '' ?>>Second Year (SY)</option>
                                <option value="TY" <?= ($_POST['year'] ?? '') == 'TY' ? 'selected' : '' ?>>Third Year (TY)</option>
                                <option value="Final" <?= ($_POST['year'] ?? '') == 'Final' ? 'selected' : '' ?>>Final Year</option>
                            </select>
                        </div>
                        
                        <!-- Profile Image Upload -->
                        <div class="col-md-6">
                            <label class="form-label">Profile Picture</label>
                            <input type="file" name="profile_image" class="form-control" accept="image/*">
                            <div class="form-text">Upload a clear face photo (JPG, PNG, GIF, Max: 2MB)</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Password *</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                            <div class="form-text">Minimum 6 characters</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password *</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" required>
                                <label class="form-check-label">
                                    I agree to the <a href="#" class="text-decoration-none">Terms and Conditions</a>
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-success w-100 py-2">Register as Volunteer</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p>Already registered? <a href="login.php" class="text-decoration-none">Login here</a></p>
                        <p><a href="index.php" class="text-decoration-none">← Back to Home</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
 <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <img src="./assets/images/nss_logo.png" alt="NSS Logo" height="40" class="mb-2">
                    <p class="mb-0 small">National Service Scheme</p>
                </div>
                <div class="col-md-4">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Navneet College of Arts ,Science & Commerce.. All Rights Reserved.</p>
                    <p class="mb-0 small">Building responsible citizens through community service</p>
                </div>
                <div class="col-md-4">
                    <img src="./assets/images/college_logo.png" alt="College Logo" height="40" class="mb-2">
                    <p class="mb-0 small">Navneet College of Science & Arts</p>
                </div>
            </div>
        </div>
    </footer>
    <script>
        // Password confirmation validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.querySelector('input[name="password"]');
            const confirmPassword = document.querySelector('input[name="confirm_password"]');
            
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match!');
                confirmPassword.focus();
            }
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>