<?php
session_start();
if (!isset($_SESSION['volunteer'])) {
    header("Location: ../login.php");
    exit;
}
include("../db/connection.php");

$volunteer_id = $_SESSION['volunteer'];
$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_id = $_POST['event_id'];
    $rating = $_POST['rating'];
    $comments = $_POST['comments'];

    // Verify event has passed
    $event_check = $pdo->prepare("SELECT event_date FROM events WHERE event_id = ?");
    $event_check->execute([$event_id]);
    $event_data = $event_check->fetch();
    
    if (!$event_data) {
        $msg = "<div class='alert alert-danger'>❌ Invalid event selected.</div>";
    } elseif (strtotime($event_data['event_date']) > strtotime(date('Y-m-d'))) {
        $msg = "<div class='alert alert-danger'>❌ Cannot submit feedback for upcoming events. The event must have already occurred.</div>";
    } else {
        // Check if feedback already exists
        $check = $pdo->prepare("SELECT id FROM feedback WHERE event_id = ? AND volunteer_id = ?");
        $check->execute([$event_id, $volunteer_id]);
        
        if ($check->fetch()) {
            $msg = "<div class='alert alert-warning'>You have already submitted feedback for this event.</div>";
        } else {
            $stmt = $pdo->prepare("INSERT INTO feedback (event_id, volunteer_id, rating, comments) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$event_id, $volunteer_id, $rating, $comments])) {
                $msg = "<div class='alert alert-success'>Feedback submitted successfully!</div>";
            } else {
                $msg = "<div class='alert alert-danger'>Failed to submit feedback. Please try again.</div>";
            }
        }
    }
}

// Fetch events registered by volunteer that they haven't given feedback for (only past events)
$stmt = $pdo->prepare("
    SELECT e.event_id, e.title, e.event_date
    FROM event_registrations r
    JOIN events e ON r.event_id = e.event_id
    WHERE r.volunteer_id = ?
    AND e.event_date <= CURDATE()
    AND e.event_id NOT IN (SELECT event_id FROM feedback WHERE volunteer_id = ?)
    ORDER BY e.event_date DESC
");
$stmt->execute([$volunteer_id, $volunteer_id]);
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Submit Feedback</title>
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
        .star-rating { font-size: 1.5em; color: #ffc107; }
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

    <h2>💬 Submit Feedback</h2>

    <?= $msg ?>

    <?php if (count($events) > 0): ?>
        <form method="post" class="card p-4">
            <div class="mb-3">
                <label class="form-label">Select Event</label>
                <select name="event_id" class="form-control" required>
                    <option value="">-- Choose Event --</option>
                    <?php foreach ($events as $e): ?>
                        <option value="<?= $e['event_id'] ?>"><?= htmlspecialchars($e['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Rating</label>
                <select name="rating" class="form-control" required>
                    <option value="">-- Select Rating --</option>
                    <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                    <option value="4">⭐⭐⭐⭐ Very Good</option>
                    <option value="3">⭐⭐⭐ Good</option>
                    <option value="2">⭐⭐ Fair</option>
                    <option value="1">⭐ Poor</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Comments</label>
                <textarea name="comments" class="form-control" rows="4" placeholder="Share your experience..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Submit Feedback</button>
        </form>
    <?php else: ?>
        <div class="alert alert-info">No events available for feedback at the moment.</div>
    <?php endif; ?>
    
    <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
</body>
</html>