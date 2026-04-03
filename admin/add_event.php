<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit;
}

include("../db/connection.php");
require_once("../includes/EmailSender.php");

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $title       = $_POST['title'];
    $description = $_POST['description'];
    $event_date  = $_POST['event_date'];
    $location    = $_POST['location'];
    $event_hours = $_POST['event_hours'];
    $event_type  = $_POST['event_type'];

    // ✅ Validate that event date is not in the past
    $today = date('Y-m-d');
    if (strtotime($event_date) < strtotime($today)) {
        $error = "❌ Event date cannot be in the past. Please select today or a future date.";
    } else {
        // ✅ Insert Event
        $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, location, event_hours, event_type)
                               VALUES (?, ?, ?, ?, ?, ?)");

    if ($stmt->execute([$title, $description, $event_date, $location, $event_hours, $event_type])) {

        $success = "✅ Event created successfully & notifications sent!";

        // ✅ Prepare notification message
        $notifTitle   = "New NSS Event: $title";
        $notifMessage = "A new event \"$title\" has been added on $event_date at $location.";

        // ✅ Fetch all volunteers
        $volunteers = $pdo->query("SELECT volunteer_id, email, name FROM volunteers")->fetchAll();

        $mailer = new EmailSender();

        // ✅ Save WEB notification (for volunteers)
        $notifyStmt = $pdo->prepare("INSERT INTO notifications (title, message, target) VALUES (?, ?, ?)");
        $notifyStmt->execute([$notifTitle, $notifMessage, 'volunteer']);

        // ✅ Send EMAIL notifications
        foreach ($volunteers as $v) {
            if (!empty($v['email'])) {
                $mailer->sendNewEventNotificationEmail(
                    $v['name'],
                    $v['email'],
                    $title,
                    $event_date,
                    $location
                );
            }
        }

    } else {
        $error = "❌ Error creating event!";
    }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Event</title>
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
    <a href="manage_notifications.php">📢 Notifications</a>    <a href="view_feedback.php">💬 Feedback</a>    <a href="../logout.php">🚪 Logout</a>
</div>

<h2>Add New Event</h2>

<?php if (!empty($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
<?php if (!empty($error))   echo "<div class='alert alert-danger'>$error</div>"; ?>

<form method="post" class="card p-4">
    
    <div class="mb-3">
        <label class="form-label">Event Title *</label>
        <input type="text" name="title" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Description *</label>
        <textarea name="description" class="form-control" rows="4" required></textarea>
    </div>

    <div class="mb-3">
        <label class="form-label">Event Date *</label>
        <input type="date" name="event_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required onchange="validateEventDate(this)">
        <div class="form-text text-danger" id="dateError" style="display:none;">Event date cannot be in the past!</div>
    </div>

    <div class="mb-3">
        <label class="form-label">Event Location *</label>
        <input type="text" name="location" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Event Type *</label>
        <select name="event_type" class="form-select" required>
            <option value="blood_camp">🩸 Blood Donation Camp</option>
            <option value="tree_plantation">🌳 Tree Plantation</option>
            <option value="cleanliness_drive">🧹 Cleanliness Drive</option>
            <option value="awareness">📢 Awareness Program</option>
            <option value="medical_camp">🏥 Medical Camp</option>
            <option value="educational">📚 Educational Activity</option>
            <option value="cultural">🎭 Cultural Event</option>
            <option value="sports">⚽ Sports Activity</option>
            <option value="college_event">🏫 College Event</option>
            <option value="regular">🔄 Regular Activity</option>
            <option value="special_camp">🏕️ Special Camp</option>
            <option value="other">📋 Other</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Event Hours *</label>
        <input type="number" name="event_hours" class="form-control" min="1" max="24" value="8" required>
        <div class="form-text">Number of hours volunteers will earn for this event</div>
    </div>

    <button type="submit" class="btn btn-primary">Add Event</button>
    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>

</form>

<script>
function validateEventDate(dateInput) {
    const selectedDate = new Date(dateInput.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const errorDiv = document.getElementById('dateError');
    
    if (selectedDate < today) {
        errorDiv.style.display = 'block';
        dateInput.setCustomValidity('Event date cannot be in the past!');
    } else {
        errorDiv.style.display = 'none';
        dateInput.setCustomValidity('');
    }
}

// Validate on form submission
document.querySelector('form').addEventListener('submit', function(e) {
    const dateInput = document.querySelector('input[name="event_date"]');
    validateEventDate(dateInput);
});
</script>

</body>
</html>
