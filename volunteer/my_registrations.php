<?php
session_start();
if (!isset($_SESSION['volunteer'])) {
    header("Location: ../login.php");
    exit;
}
include("../db/connection.php");

$volunteer_id = $_SESSION['volunteer'];
$message = "";

// Cancel registration
if (isset($_GET['cancel_id'])) {
    $cancel_id = $_GET['cancel_id'];
    
    // Check if event hasn't started yet before allowing cancellation
    $event_check = $pdo->prepare("
        SELECT e.event_date FROM event_registrations r
        JOIN events e ON r.event_id = e.event_id
        WHERE r.id = ? AND r.volunteer_id = ? AND e.event_date >= CURDATE()
    ");
    $event_check->execute([$cancel_id, $volunteer_id]);
    
    if ($event_check->fetch()) {
        // Only allow cancellation for future events
        $stmt = $pdo->prepare("DELETE FROM event_registrations WHERE id = ? AND volunteer_id = ?");
        if ($stmt->execute([$cancel_id, $volunteer_id])) {
            $message = "<div class='alert alert-success'>Registration cancelled successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Failed to cancel registration.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>❌ Cannot cancel registration for past events.</div>";
    }
}

// Fetch registered events
$stmt = $pdo->prepare("
    SELECT r.id, e.event_id, e.title, e.event_date, e.location, a.status
    FROM event_registrations r
    JOIN events e ON r.event_id = e.event_id
    LEFT JOIN attendance a ON r.event_id = a.event_id AND r.volunteer_id = a.volunteer_id
    WHERE r.volunteer_id = ? AND e.event_date >= CURDATE()
    ORDER BY e.event_date ASC
");
$stmt->execute([$volunteer_id]);
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Registrations</title>
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
        <a href="view_events.php">📅 Available Events</a>
        <a href="my_registrations.php">📝 My Registrations</a>
        <a href="my_attendance.php">✅ My Attendance</a>
        <a href="my_certificates.php">🎓 My Certificates</a>
        <a href="view_gallery.php">🖼️ Gallery</a>
        <a href="upload_photos.php">📸 Upload Photos</a>
        <a href="feedback.php">💬 Feedback</a>
        <a href="notifications.php">📢 Notifications</a>
        <a href="profile.php">👤 My Profile</a>
        <a href="../logout.php">🚪 Logout</a>
    </div>

    <h2>📝 My Registrations</h2>
    
    <?= $message ?>
    
    <?php if (count($events) > 0): ?>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Event</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $e): ?>
                <tr>
                    <td><?= htmlspecialchars($e['title']) ?></td>
                    <td><?= $e['event_date'] ?></td>
                    <td><?= htmlspecialchars($e['location']) ?></td>
                    <td>
                        <?php if ($e['status'] !== 'Present'): ?>
                            <a href="?cancel_id=<?= $e['id'] ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Are you sure you want to cancel this registration?')">
                                Cancel Registration
                            </a>
                        <?php else: ?>
                            <span class="badge bg-success">✓ Marked Present</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">No event registrations found.</div>
    <?php endif; ?>
    
    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</body>
</html>