<?php
session_start();
include("./db/connection.php");

date_default_timezone_set('Asia/Kolkata');

$msg = "";

if (!isset($_GET['token'])) {
    header("Location: forgot_password.php");
    exit;
}

$token = $_GET['token'];

// Validate token
$stmt = $pdo->prepare("SELECT volunteer_id, expires_at FROM password_reset_tokens WHERE token = ?");
$stmt->execute([$token]);
$valid_token = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$valid_token) {
    $msg = "<div class='alert alert-danger'>Invalid reset link.</div>";
} else {
    if (strtotime($valid_token['expires_at']) < time()) {
        $msg = "<div class='alert alert-danger'>Reset link has expired.</div>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == "POST" && empty($msg) && $valid_token) {

    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $msg = "<div class='alert alert-danger'>Passwords do not match.</div>";
    } else {

        // ✅ MD5 because your login uses md5
        $hashed = md5($new_password);

        $update_stmt = $pdo->prepare("UPDATE volunteers SET password = ? WHERE volunteer_id = ?");
        if ($update_stmt->execute([$hashed, $valid_token['volunteer_id']])) {

            // Delete token after use
            $delete_stmt = $pdo->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
            $delete_stmt->execute([$token]);

            $msg = "<div class='alert alert-success'>
                Password updated successfully!
                <a href='login.php'>Login here</a>
            </div>";
        } else {
            $msg = "<div class='alert alert-danger'>Password reset failed.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card p-4 shadow">

                <h3 class="text-center mb-3">Reset Password</h3>

                <?= $msg; ?>

                <?php if (strpos($msg, 'successfully') === false): ?>
                <form method="POST">
                    <div class="mb-3">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>

                    <button class="btn btn-primary w-100">Reset Password</button>
                </form>
                <?php endif; ?>

                <div class="text-center mt-3">
                    <a href="login.php">Back to Login</a>
                </div>

            </div>
        </div>
    </div>
</div>

</body>
</html>
