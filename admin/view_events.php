<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit;
}

include("../db/connection.php");

// Get selected filter type
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';

// Query events with optional filtering
if ($filter_type && $filter_type !== 'all') {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE event_type = ? ORDER BY event_date DESC");
    $events = $stmt->execute([$filter_type]) ? $stmt->fetchAll() : [];
} else {
    $events = $pdo->query("SELECT * FROM events ORDER BY event_date DESC")->fetchAll();
}

// Get all event types for filter dropdown
$eventTypes = [
    'blood_camp' => '🩸 Blood Donation Camp',
    'tree_plantation' => '🌳 Tree Plantation',
    'cleanliness_drive' => '🧹 Cleanliness Drive',
    'awareness' => '📢 Awareness Program',
    'medical_camp' => '🏥 Medical Camp',
    'educational' => '📚 Educational Activity',
    'cultural' => '🎭 Cultural Event',
    'sports' => '⚽ Sports Activity',
    'college_event' => '🏫 College Event',
    'regular' => '🔄 Regular Activity',
    'special_camp' => '🏕️ Special Camp',
    'other' => '📋 Other'
];

// Event type display function
function getEventTypeDisplay($type) {
    $types = [
        'blood_camp' => '🩸 Blood',
        'tree_plantation' => '🌳 Tree Plantation',
        'cleanliness_drive' => '🧹 Cleanliness',
        'awareness' => '📢 Awareness',
        'medical_camp' => '🏥 Medical',
        'educational' => '📚 Educational',
        'cultural' => '🎭 Cultural',
        'sports' => '⚽ Sports',
        'college_event' => '🏫 College',
        'regular' => '🔄 Regular',
        'special_camp' => '🏕️ Special Camp',
        'other' => '📋 Other'
    ];
    return $types[$type] ?? '📋 Other';
}

function getEventTypeBadgeColor($type) {
    $colors = [
        'blood_camp' => 'bg-danger',
        'tree_plantation' => 'bg-success',
        'cleanliness_drive' => 'bg-info',
        'awareness' => 'bg-warning',
        'medical_camp' => 'bg-primary',
        'educational' => 'bg-secondary',
        'cultural' => 'bg-purple',
        'sports' => 'bg-success',
        'college_event' => 'bg-dark',
        'regular' => 'bg-secondary',
        'special_camp' => 'bg-warning',
        'other' => 'bg-light text-dark'
    ];
    return $colors[$type] ?? 'bg-light text-dark';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Events</title>
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
        .bg-purple { background-color: #6f42c1 !important; }
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

    <h2>Manage Events</h2>
    
    <div class="card p-3 mb-4">
        <div class="row align-items-end">
            <div class="col-md-8">
                <label class="form-label">Filter by Event Type:</label>
                <form method="GET" class="d-flex gap-2">
                    <select name="type" class="form-select">
                        <option value="all" <?= ($filter_type === '' || $filter_type === 'all') ? 'selected' : ''; ?>>All Event Types</option>
                        <?php foreach ($eventTypes as $key => $label): ?>
                        <option value="<?= $key; ?>" <?= ($filter_type === $key) ? 'selected' : ''; ?>><?= $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="view_events.php" class="btn btn-secondary">Clear</a>
                </form>
            </div>
            <div class="col-md-4 text-end">
                <span class="badge bg-info">Total: <?= count($events); ?> Events</span>
            </div>
        </div>
    </div>
    
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Event ID</th>
                <th>Title</th>
                <th>Type</th>
                <th>Description</th>
                <th>Date</th>
                <th>Location</th>
                <th>Hours</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($events as $e): ?>
            <tr>
                <td><?= $e['event_id']; ?></td>
                <td><?= htmlspecialchars($e['title']); ?></td>
                <td>
                    <span class="badge <?= getEventTypeBadgeColor($e['event_type']) ?>">
                        <?= getEventTypeDisplay($e['event_type']) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($e['description']); ?></td>
                <td><?= $e['event_date']; ?></td>
                <td><?= htmlspecialchars($e['location']); ?></td>
                <td class="text-center">
                    <span class="badge bg-primary"><?= $e['event_hours']; ?> hrs</span>
                </td>
                <td>
                    <?php $isPast = strtotime($e['event_date']) < strtotime('today'); ?>
                    <?php if (!$isPast): ?>
                        <a href="edit_event.php?id=<?= $e['event_id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="delete_event.php?id=<?= $e['event_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this event?')">Delete</a>
                    <?php endif; ?>
                    <a href="attendance.php?event_id=<?= $e['event_id']; ?>" class="btn btn-sm btn-info">Attendance</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</body>
</html>