<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit;
}

include("../db/connection.php");

$success = "";
$error = "";

// Get event details
if (!isset($_GET['id'])) {
    header("Location: view_events.php");
    exit;
}

$event_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    header("Location: view_events.php");
    exit;
}

// Update event
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $event_date = $_POST['event_date'];
    $location = $_POST['location'];
    $event_hours = $_POST['event_hours'];
    $event_type = $_POST['event_type'];

    $stmt = $pdo->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, location = ?, event_hours = ?, event_type = ? WHERE event_id = ?");
    if ($stmt->execute([$title, $description, $event_date, $location, $event_hours, $event_type, $event_id])) {
        $success = "Event updated successfully!";
        // Refresh event data
        $stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ?");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch();
    } else {
        $error = "Error updating event!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Event</title>
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

    <h2>Edit Event</h2>
    
    <?php if (!empty($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
    <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

    <form method="post" class="card p-4">
        <div class="mb-3">
            <label class="form-label">Event Title</label>
            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($event['title']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4" required><?= htmlspecialchars($event['description']) ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Event Date</label>
            <input type="date" name="event_date" class="form-control" value="<?= $event['event_date'] ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Event Location</label>
            <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($event['location']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Event Type</label>
            <select name="event_type" class="form-select" required>
                <option value="blood_camp" <?= $event['event_type'] == 'blood_camp' ? 'selected' : '' ?>>🩸 Blood Donation Camp</option>
                <option value="tree_plantation" <?= $event['event_type'] == 'tree_plantation' ? 'selected' : '' ?>>🌳 Tree Plantation</option>
                <option value="cleanliness_drive" <?= $event['event_type'] == 'cleanliness_drive' ? 'selected' : '' ?>>🧹 Cleanliness Drive</option>
                <option value="awareness" <?= $event['event_type'] == 'awareness' ? 'selected' : '' ?>>📢 Awareness Program</option>
                <option value="medical_camp" <?= $event['event_type'] == 'medical_camp' ? 'selected' : '' ?>>🏥 Medical Camp</option>
                <option value="educational" <?= $event['event_type'] == 'educational' ? 'selected' : '' ?>>📚 Educational Activity</option>
                <option value="cultural" <?= $event['event_type'] == 'cultural' ? 'selected' : '' ?>>🎭 Cultural Event</option>
                <option value="sports" <?= $event['event_type'] == 'sports' ? 'selected' : '' ?>>⚽ Sports Activity</option>
                <option value="college_event" <?= $event['event_type'] == 'college_event' ? 'selected' : '' ?>>🏫 College Event</option>
                <option value="regular" <?= $event['event_type'] == 'regular' ? 'selected' : '' ?>>🔄 Regular Activity</option>
                <option value="special_camp" <?= $event['event_type'] == 'special_camp' ? 'selected' : '' ?>>🏕️ Special Camp</option>
                <option value="other" <?= $event['event_type'] == 'other' ? 'selected' : '' ?>>📋 Other</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Event Hours</label>
            <input type="number" name="event_hours" class="form-control" min="1" max="24" value="<?= $event['event_hours'] ?>" required>
            <div class="form-text">Number of hours volunteers will earn for participating in this event</div>
        </div>
        <button type="submit" class="btn btn-primary">Update Event</button>
        <a href="view_events.php" class="btn btn-secondary">Cancel</a>
    </form>
</body>
</html>