<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit;
}

include("../db/connection.php");

if (!isset($_GET['id'])) {
    header("Location: view_events.php");
    exit;
}

$event_id = $_GET['id'];

// Check if event exists
$stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    header("Location: view_events.php");
    exit;
}

// Delete event
if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    $stmt = $pdo->prepare("DELETE FROM events WHERE event_id = ?");
    if ($stmt->execute([$event_id])) {
        header("Location: view_events.php?message=deleted");
        exit;
    } else {
        header("Location: view_events.php?message=error");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Delete Event</title>
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

    <div class="card">
        <div class="card-header bg-danger text-white">
            <h4 class="mb-0">Confirm Deletion</h4>
        </div>
        <div class="card-body">
            <h5>Are you sure you want to delete this event?</h5>
            
            <div class="alert alert-warning">
                <strong>Event Details:</strong><br>
                <strong>Title:</strong> <?= htmlspecialchars($event['title']) ?><br>
                <strong>Date:</strong> <?= $event['event_date'] ?><br>
                <strong>Location:</strong> <?= htmlspecialchars($event['location']) ?>
            </div>
            
            <p class="text-danger">
                <strong>Warning:</strong> This action cannot be undone. All registrations and attendance records for this event will also be deleted.
            </p>
            
            <div class="mt-4">
                <a href="delete_event.php?id=<?= $event_id ?>&confirm=yes" class="btn btn-danger">Yes, Delete Event</a>
                <a href="view_events.php" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </div>
</body>
</html>