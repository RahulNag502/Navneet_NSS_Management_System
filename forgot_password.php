<?php
session_start();
include("./db/connection.php");
require_once __DIR__ . '/includes/EmailSender.php';

date_default_timezone_set('Asia/Kolkata'); // FIXED timezone

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $email = trim($_POST['email']);

    // Check if email exists
    $stmt = $pdo->prepare("SELECT volunteer_id, name FROM volunteers WHERE email = ?");
    $stmt->execute([$email]);
    $volunteer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($volunteer) {

        // Generate token
        $token = bin2hex(random_bytes(50));
        $expires = date("Y-m-d H:i:s", time() + (60 * 60)); // valid 1 hour

        // Delete old tokens
        $delete_stmt = $pdo->prepare("DELETE FROM password_reset_tokens WHERE volunteer_id = ?");
        $delete_stmt->execute([$volunteer['volunteer_id']]);

        // Insert new token
        $insert_stmt = $pdo->prepare(
            "INSERT INTO password_reset_tokens (volunteer_id, token, expires_at)
             VALUES (?, ?, ?)"
        );

        if ($insert_stmt->execute([$volunteer['volunteer_id'], $token, $expires])) {

            $emailSender = new EmailSender();
            $emailResult = $emailSender->sendPasswordResetEmail(
                $volunteer['name'], $email, $token
            );

            if ($emailResult['success']) {
                $msg = "<div class='alert alert-success'>
                    <i class='fas fa-check-circle'></i> 
                    Password reset link sent to your email! Please check your inbox.
                </div>";
            } else {
                $msg = "<div class='alert alert-warning'>
                    <i class='fas fa-exclamation-triangle'></i> 
                    Account found but email sending failed. Please contact administrator.
                </div>";
            }
        }

    } else {
        $msg = "<div class='alert alert-danger'>
            <i class='fas fa-times-circle'></i> 
            No account found with that email address.
        </div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - Navneet College of Arts ,Science & Commerce.</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="./assets/images/nss_logo.png" height="40" class="me-2">
            Navneet College of Arts ,Science & Commerce.
        </a>
    </div>
</nav>

<div class="container">
    <div class="login-container mt-5">
        <div class="card login-card shadow">
            <div class="card-body p-4">
                <h3 class="text-center mb-4">
                    <i class="fas fa-key text-primary"></i> Forgot Password
                </h3>

                <?= $msg; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-envelope me-1"></i> Email Address
                        </label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-paper-plane me-2"></i> Send Reset Link
                    </button>
                </form>

                <div class="text-center mt-3">
                    <a href="login.php">← Back to Login</a>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
