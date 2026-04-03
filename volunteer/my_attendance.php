<?php
session_start();
if (!isset($_SESSION['volunteer'])) {
    header("Location: ../login.php");
    exit;
}
include("../db/connection.php");

$volunteer_id = $_SESSION['volunteer'];

// Fetch attendance records
$stmt = $pdo->prepare("
    SELECT e.title, e.event_date, a.status, a.marked_at
    FROM attendance a
    JOIN events e ON a.event_id = e.event_id
    WHERE a.volunteer_id = ?
    ORDER BY e.event_date DESC
");
$stmt->execute([$volunteer_id]);
$records = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Attendance</title>
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

    <h2>✅ My Attendance</h2>
    
    <?php if (count($records) > 0): ?>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Event</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Marked At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['title']) ?></td>
                        <td><?= $r['event_date'] ?></td>
                        <td>
                            <?php if ($r['status'] == 'Present'): ?>
                                <span class="badge bg-success">Present</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Absent</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $r['marked_at'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">No attendance records found.</div>
    <?php endif; ?>
    
    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</body>
</html>