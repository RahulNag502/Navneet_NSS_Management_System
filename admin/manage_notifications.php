<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit;
}

include("../db/connection.php");
require_once "../includes/EmailSender.php"; // Include your EmailSender class
$emailSender = new EmailSender();

$success = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $message = $_POST['message'];
    $target = $_POST['target'];

    // Insert notification into database
    $stmt = $pdo->prepare("INSERT INTO notifications (title, message, target) VALUES (?, ?, ?)");
    $stmt->execute([$title, $message, $target]);
    $success = "Notification added successfully!";

    // Fetch recipients based on target
    if ($target === 'all') {
        $recipients = $pdo->query("SELECT name, email FROM volunteers WHERE email_notifications = 1
                                   UNION SELECT username AS name, username AS email FROM admins")->fetchAll();
    } elseif ($target === 'volunteer') {
        $recipients = $pdo->query("SELECT name, email FROM volunteers WHERE email_notifications = 1")->fetchAll();
    } elseif ($target === 'admin') {
        $recipients = $pdo->query("SELECT username AS name, username AS email FROM admins")->fetchAll();
    } else {
        $recipients = [];
    }

    // Send email to each recipient
    foreach ($recipients as $r) {
        $emailSender->sendEmail(
            $r['email'],
            "📢 New NSS Notification: $title",
            "<h3>Hello {$r['name']},</h3><p>$message</p>"
        );
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
    $stmt->execute([$id]);
    $success = "Notification deleted successfully!";
}

// Fetch all notifications
$notifications = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .nav-menu { 
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            padding: 18px 25px;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .nav-menu a { 
            color: white;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-size: 0.95em;
            font-weight: 500;
            white-space: nowrap;
            border: 2px solid transparent;
        }
        .nav-menu a:hover { 
            background: #ffc107;
            color: #2c3e50;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 193, 7, 0.4);
            border-color: #ffb300;
        }
        @media (max-width: 768px) {
            .nav-menu { padding: 12px 15px; gap: 6px; }
            .nav-menu a { padding: 8px 12px; font-size: 0.85em; }
        }
    </style>
</head>
<body class="container my-5">
    <div class="nav-menu">
        <a href="dashboard.php">🏠 Home</a>
        <a href="add_event.php">➕ Add Event</a>
        <a href="view_events.php">📅 Manage Events</a>
        <a href="attendance.php">📝 Attendance</a>
        <a href="view_registrations.php">👥 Registrations</a>
        <a href="view_volunteers.php">📋 Volunteers</a>
        <a href="issue_certificates.php">🎓 Certificates</a>
        <a href="gallery_upload.php">� Upload Gallery</a>
        <a href="gallery_view.php">🖼️ View Gallery</a>
        <a href="manage_notifications.php">📢 Notifications</a>        <a href="view_feedback.php">💬 Feedback</a>        <a href="../logout.php">🚪 Logout</a>
    </div>

    <h2>📢 Manage Notifications</h2>
    
    <?php if (!empty($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
    
    <form method="POST" class="card p-4 mb-4">
        <h4>Add New Notification</h4>
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Message</label>
            <textarea name="message" class="form-control" rows="3" required></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Target Audience</label>
            <select name="target" class="form-select" required>
                <option value="all">All Users</option>
                <option value="volunteer">Volunteers Only</option>
                <option value="admin">Admins Only</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success">Add Notification</button>
    </form>

    <h3>Existing Notifications</h3>
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Title</th>
                <th>Message</th>
                <th>Target</th>
                <th>Created At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($notifications as $n): ?>
            <tr>
                <td><?= htmlspecialchars($n['title']) ?></td>
                <td><?= htmlspecialchars($n['message']) ?></td>
                <td><span class="badge bg-secondary"><?= ucfirst($n['target']) ?></span></td>
                <td><?= $n['created_at'] ?></td>
                <td>
                    <a href="?delete=<?= $n['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this notification?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</body>
</html>