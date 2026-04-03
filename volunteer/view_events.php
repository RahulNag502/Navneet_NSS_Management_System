<?php
session_start();
if (!isset($_SESSION['volunteer'])) {
    header("Location: ../login.php");
    exit;
}
include("../db/connection.php");

$volunteer_id = $_SESSION['volunteer'];

// Get events that volunteer hasn't registered for and are in future or today
$events = $pdo->prepare("
    SELECT e.* 
    FROM events e 
    WHERE DATE(e.event_date) >= CURDATE()
    AND NOT EXISTS (
        SELECT 1 FROM event_registrations r 
        WHERE r.event_id = e.event_id AND r.volunteer_id = ?
    )
    ORDER BY e.event_date ASC
");
$events->execute([$volunteer_id]);
$events = $events->fetchAll();

// Event type display function
function getEventTypeDisplay($type) {
    $types = [
        'blood_camp' => ['icon' => '🩸', 'name' => 'Blood Donation', 'color' => 'danger'],
        'tree_plantation' => ['icon' => '🌳', 'name' => 'Tree Plantation', 'color' => 'success'],
        'cleanliness_drive' => ['icon' => '🧹', 'name' => 'Cleanliness Drive', 'color' => 'info'],
        'awareness' => ['icon' => '📢', 'name' => 'Awareness Program', 'color' => 'warning'],
        'medical_camp' => ['icon' => '🏥', 'name' => 'Medical Camp', 'color' => 'primary'],
        'educational' => ['icon' => '📚', 'name' => 'Educational Activity', 'color' => 'secondary'],
        'cultural' => ['icon' => '🎭', 'name' => 'Cultural Event', 'color' => 'purple'],
        'sports' => ['icon' => '⚽', 'name' => 'Sports Activity', 'color' => 'success'],
        'college_event' => ['icon' => '🏫', 'name' => 'College Event', 'color' => 'dark'],
        'regular' => ['icon' => '🔄', 'name' => 'Regular Activity', 'color' => 'secondary'],
        'special_camp' => ['icon' => '🏕️', 'name' => 'Special Camp', 'color' => 'warning'],
        'other' => ['icon' => '📋', 'name' => 'Other', 'color' => 'light']
    ];
    return $types[$type] ?? ['icon' => '📋', 'name' => 'Other', 'color' => 'light'];
}

// Get registered events count for stats
$registered_count = $pdo->prepare("
    SELECT COUNT(*) FROM event_registrations WHERE volunteer_id = ?
");
$registered_count->execute([$volunteer_id]);
$registered_count = $registered_count->fetchColumn();

// Get upcoming events count
$upcoming_count = $pdo->query("
    SELECT COUNT(*) FROM events WHERE event_date >= CURDATE()
")->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Available Events - Navneet College of Arts ,Science & Commerce.</title>
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
        .hours-badge { font-size: 0.9em; }
        .event-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .event-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .bg-purple { background-color: #6f42c1 !important; }
        .stats-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
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

    <h2>📅 Available Events</h2>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card stats-card">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= count($events) ?></h3>
                    <p class="mb-0">Available Events</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $registered_count ?></h3>
                    <p class="mb-0">Registered Events</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $upcoming_count ?></h3>
                    <p class="mb-0">Total Upcoming Events</p>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (count($events) > 0): ?>
        <div class="row">
            <?php foreach ($events as $e): 
                $type_info = getEventTypeDisplay($e['event_type']);
            ?>
            <div class="col-md-6 mb-4">
                <div class="card event-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="badge bg-<?= $type_info['color'] ?>">
                            <?= $type_info['icon'] ?> <?= $type_info['name'] ?>
                        </span>
                        <span class="badge bg-primary hours-badge"><?= $e['event_hours'] ?> hours</span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($e['title']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($e['description']) ?></p>
                        <div class="event-details">
                            <p class="mb-1">
                                <i class="fas fa-calendar text-primary"></i>
                                <strong>Date:</strong> <?= date('l, F j, Y', strtotime($e['event_date'])) ?>
                            </p>
                            <p class="mb-1">
                                <i class="fas fa-map-marker-alt text-danger"></i>
                                <strong>Location:</strong> <?= htmlspecialchars($e['location']) ?>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-clock text-success"></i>
                                <strong>Hours:</strong> <span class="badge bg-success"><?= $e['event_hours'] ?> service hours</span>
                            </p>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="d-grid">
                            <a href="register_event.php?id=<?= $e['event_id'] ?>" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Register for Event
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            <div class="py-4">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <h4>No Available Events</h4>
                <p class="mb-3">There are no events available for registration at the moment.</p>
                <div class="row">
                    <div class="col-md-6">
                        <div class="alert alert-warning">
                            <h6>Possible Reasons:</h6>
                            <ul class="text-start small">
                                <li>You've already registered for all upcoming events</li>
                                <li>No new events have been scheduled yet</li>
                                <li>Events might be in the past</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-success">
                            <h6>What to do:</h6>
                            <ul class="text-start small">
                                <li>Check your <a href="my_registrations.php" class="alert-link">registered events</a></li>
                                <li>Wait for new events to be announced</li>
                                <li>Contact NSS coordinator for more information</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <a href="my_registrations.php" class="btn btn-primary me-2">
                    <i class="fas fa-list me-1"></i>View My Registrations
                </a>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-home me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Debug Information (Remove in production) 
    <div class="mt-4">
        <details>
            <summary class="text-muted small">Debug Info</summary>
            <div class="alert alert-warning small">
                <strong>Volunteer ID:</strong> <?= $volunteer_id ?><br>
                <strong>Available Events Count:</strong> <?= count($events) ?><br>
                <strong>Query:</strong> SELECT events WHERE event_date >= CURDATE() AND NOT EXISTS (registered events for this volunteer)
            </div>
        </details>
    </div>
    -->

    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>