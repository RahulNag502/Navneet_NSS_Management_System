<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit;
}

include("../db/connection.php");
require_once("../includes/EmailSender.php");

$msg = "";
$error = "";

// Handle Single Registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'single_register') {
        $volunteer_id = $_POST['volunteer_id'] ?? '';
        $event_id = $_POST['event_id'] ?? '';
        
        if (empty($volunteer_id) || empty($event_id)) {
            $error = "❌ Please select both volunteer and event";
        } else {
            // Check if event is in the future
            $event_check = $pdo->prepare("SELECT event_date FROM events WHERE event_id = ? AND event_date >= CURDATE()");
            $event_check->execute([$event_id]);
            
            if (!$event_check->fetch()) {
                $error = "❌ Cannot register for past events. Please select an upcoming event.";
            } else {
                // Check if already registered
                $check = $pdo->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND volunteer_id = ?");
                $check->execute([$event_id, $volunteer_id]);
                
                if ($check->fetch()) {
                    $error = "❌ This volunteer is already registered for this event";
                } else {
                // Register volunteer
                $stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, volunteer_id) VALUES (?, ?)");
                if ($stmt->execute([$event_id, $volunteer_id])) {
                    // Send email notification
                    $vol_data = $pdo->prepare("SELECT name, email FROM volunteers WHERE volunteer_id = ?")->execute([$volunteer_id]);
                    $volunteer = $pdo->prepare("SELECT name, email FROM volunteers WHERE volunteer_id = ?")->execute([$volunteer_id]);
                    
                    $volunteer = $pdo->prepare("SELECT name, email FROM volunteers WHERE volunteer_id = ?");
                    $volunteer->execute([$volunteer_id]);
                    $vol = $volunteer->fetch();
                    
                    $event = $pdo->prepare("SELECT title, event_date, location FROM events WHERE event_id = ?")->execute([$event_id]);
                    $event = $pdo->prepare("SELECT title, event_date, location FROM events WHERE event_id = ?");
                    $event->execute([$event_id]);
                    $evt = $event->fetch();
                    
                    if ($vol && $evt) {
                        $emailSender = new EmailSender();
                        $emailSender->sendEventRegistrationEmail(
                            $vol['name'],
                            $vol['email'],
                            $evt['title'],
                            $evt['event_date'],
                            $evt['location']
                        );
                    }
                    
                    $msg = "✅ Volunteer registered successfully for the event!";
                } else {
                    $error = "❌ Error registering volunteer";
                }
            }
            }
        }
    }
    
    // Handle Bulk Registration
    elseif ($action == 'bulk_register') {
        $event_id = $_POST['bulk_event_id'] ?? '';
        $volunteer_ids = $_POST['bulk_volunteers'] ?? [];
        
        if (empty($event_id) || empty($volunteer_ids)) {
            $error = "❌ Please select both event and at least one volunteer";
        } else {
            // Check if event is in the future
            $event_check = $pdo->prepare("SELECT event_date FROM events WHERE event_id = ? AND event_date >= CURDATE()");
            $event_check->execute([$event_id]);
            
            if (!$event_check->fetch()) {
                $error = "❌ Cannot register for past events. Please select an upcoming event.";
            } else {
                $registered = 0;
                $failed = 0;
                $skipped = 0;
                
                $emailSender = new EmailSender();
                
                foreach ($volunteer_ids as $volunteer_id) {
                    // Check if already registered
                    $check = $pdo->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND volunteer_id = ?");
                    $check->execute([$event_id, $volunteer_id]);
                    
                    if ($check->fetch()) {
                        $skipped++;
                    continue;
                }
                
                // Register volunteer
                $stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, volunteer_id) VALUES (?, ?)");
                if ($stmt->execute([$event_id, $volunteer_id])) {
                    $registered++;
                    
                    // Send email
                    $volunteer = $pdo->prepare("SELECT name, email FROM volunteers WHERE volunteer_id = ?");
                    $volunteer->execute([$volunteer_id]);
                    $vol = $volunteer->fetch();
                    
                    $event = $pdo->prepare("SELECT title, event_date, location FROM events WHERE event_id = ?");
                    $event->execute([$event_id]);
                    $evt = $event->fetch();
                    
                    if ($vol && $evt) {
                        $emailSender->sendEventRegistrationEmail(
                            $vol['name'],
                            $vol['email'],
                            $evt['title'],
                            $evt['event_date'],
                            $evt['location']
                        );
                    }
                } else {
                    $failed++;
                }
            }
            
            $msg = "✅ Bulk registration complete - Registered: $registered, Already enrolled: $skipped, Failed: $failed";
            }
        }
    }
}

$registrations = $pdo->query("
    SELECT r.id, v.name, v.volunteer_id, e.title, e.event_date, e.location, e.event_id
    FROM event_registrations r
    JOIN volunteers v ON r.volunteer_id = v.volunteer_id
    JOIN events e ON r.event_id = e.event_id
    ORDER BY e.event_date DESC, v.name ASC
")->fetchAll();

// Group registrations by event
$registrations_by_event = [];
foreach ($registrations as $reg) {
    $event_id = $reg['event_id'];
    if (!isset($registrations_by_event[$event_id])) {
        $registrations_by_event[$event_id] = [
            'title' => $reg['title'],
            'event_date' => $reg['event_date'],
            'location' => $reg['location'],
            'volunteers' => []
        ];
    }
    $registrations_by_event[$event_id]['volunteers'][] = [
        'id' => $reg['id'],
        'name' => $reg['name'],
        'volunteer_id' => $reg['volunteer_id']
    ];
}

// Get events and volunteers for forms
$events = $pdo->query("SELECT event_id, title, event_date FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC")->fetchAll();
$volunteers = $pdo->query("SELECT volunteer_id, name, email FROM volunteers WHERE status = 'active' ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Event Registrations</title>
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
        .registration-form { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .tab-button { cursor: pointer; padding: 10px 20px; margin-right: 5px; background: #e9ecef; border: none; border-radius: 4px 4px 0 0; }
        .tab-button.active { background: #007bff; color: white; }
        .volunteer-list { max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px; padding: 10px; }
        .form-section { border-bottom: 2px solid #dee2e6; margin-bottom: 20px; padding-bottom: 20px; }
        
        /* Card View Styles */
        .event-card {
            background: white;
            border-left: 5px solid #007bff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }
        .event-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        .event-card-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
        }
        .event-card-header h5 {
            margin: 0 0 8px 0;
            font-size: 1.3em;
            font-weight: 600;
        }
        .event-details {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 8px;
            font-size: 0.95em;
            margin-top: 10px;
            opacity: 0.95;
        }
        .event-details strong {
            color: #f0f0f0;
        }
        .event-card-body {
            padding: 20px;
        }
        .volunteer-badge {
            display: inline-block;
            background: #e9ecef;
            padding: 10px 15px;
            margin: 8px 8px 8px 0;
            border-radius: 20px;
            border-left: 3px solid #007bff;
            font-size: 0.95em;
            transition: all 0.2s;
        }
        .volunteer-badge:hover {
            background: #dee2e6;
            transform: translateX(5px);
        }
        .volunteer-count {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            margin-left: 10px;
        }
        .no-registrations {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .no-registrations i {
            font-size: 3em;
            margin-bottom: 15px;
            opacity: 0.5;
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

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📋 Event Registrations Management</h2>
    </div>

    <!-- Messages -->
    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Registration Forms Section -->
    <div class="registration-form">
        <h4 class="mb-4">➕ Admin Registration Panel</h4>
        
        <!-- Tab Buttons -->
        <div class="mb-4">
            <button class="tab-button active" onclick="switchTab('single')">👤 Single Registration</button>
            <button class="tab-button" onclick="switchTab('bulk')">👥 Bulk Registration</button>
        </div>

        <!-- Single Registration Form -->
        <div id="single" class="tab-content active">
            <div class="form-section">
                <h5>Register Single Volunteer</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="single_register">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><strong>👤 Select Volunteer</strong></label>
                            <select name="volunteer_id" class="form-control" id="single_volunteer" required>
                                <option value="">-- Choose Volunteer --</option>
                                <?php foreach ($volunteers as $v): ?>
                                    <option value="<?= htmlspecialchars($v['volunteer_id']) ?>">
                                        <?= htmlspecialchars($v['name']) ?> (<?= htmlspecialchars($v['volunteer_id']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><strong>📅 Select Event</strong></label>
                            <select name="event_id" class="form-control" id="single_event" required>
                                <option value="">-- Choose Event --</option>
                                <?php foreach ($events as $e): ?>
                                    <option value="<?= htmlspecialchars($e['event_id']) ?>">
                                        <?= htmlspecialchars($e['title']) ?> (<?= $e['event_date'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-check-circle"></i> Register Volunteer for Event
                    </button>
                </form>
            </div>
        </div>

        <!-- Bulk Registration Form -->
        <div id="bulk" class="tab-content">
            <div class="form-section">
                <h5>Register Multiple Volunteers</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="bulk_register">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><strong>📅 Select Event</strong></label>
                            <select name="bulk_event_id" class="form-control" id="bulk_event" required>
                                <option value="">-- Choose Event --</option>
                                <?php foreach ($events as $e): ?>
                                    <option value="<?= htmlspecialchars($e['event_id']) ?>">
                                        <?= htmlspecialchars($e['title']) ?> (<?= $e['event_date'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><strong>🔍 Search Volunteers</strong></label>
                            <input type="text" id="volunteerSearch" class="form-control" placeholder="Search by name or ID...">
                        </div>
                    </div>

                    <label class="form-label"><strong>👥 Select Volunteers to Register</strong> (Ctrl/Cmd + Click for multiple)</label>
                    <div class="volunteer-list">
                        <?php foreach ($volunteers as $v): ?>
                            <div class="form-check volunteer-item" style="padding: 8px 0;">
                                <input class="form-check-input volunteer-checkbox" type="checkbox" name="bulk_volunteers[]" 
                                       value="<?= htmlspecialchars($v['volunteer_id']) ?>" 
                                       id="volunteer_<?= htmlspecialchars($v['volunteer_id']) ?>"
                                       data-name="<?= htmlspecialchars($v['name']) ?>"
                                       data-id="<?= htmlspecialchars($v['volunteer_id']) ?>">
                                <label class="form-check-label" for="volunteer_<?= htmlspecialchars($v['volunteer_id']) ?>">
                                    <?= htmlspecialchars($v['name']) ?> <small class="text-muted">(<?= htmlspecialchars($v['volunteer_id']) ?>)</small>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-3 mb-3">
                        <small class="text-muted">
                            <strong>Selected:</strong> <span id="selectedCount">0</span> / <?= count($volunteers) ?> volunteers
                        </small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAll()">✓ Select All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">✗ Deselect All</button>
                        <button type="submit" class="btn btn-success btn-lg ms-auto">
                            <i class="fas fa-users-check"></i> Register All Selected
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Existing Registrations Section -->
    <h4 class="mt-5 mb-4">📊 Event Registrations By Event</h4>
    
    <?php if (count($registrations_by_event) > 0): ?>
        <div class="registrations-container">
            <?php foreach ($registrations_by_event as $event_id => $event_data): ?>
                <div class="event-card">
                    <div class="event-card-header">
                        <h5><?= htmlspecialchars($event_data['title']); ?></h5>
                        <div class="event-details">
                            <strong>📅 Date:</strong> <span><?= date('M d, Y', strtotime($event_data['event_date'])); ?></span>
                            <strong>📍 Location:</strong> <span><?= htmlspecialchars($event_data['location']); ?></span>
                        </div>
                    </div>
                    
                    <div class="event-card-body">
                        <div class="mb-3">
                            <h6 style="color: #495057; margin-bottom: 15px;">
                                👥 Registered Volunteers 
                                <span class="volunteer-count"><?= count($event_data['volunteers']); ?></span>
                            </h6>
                        </div>
                        
                        <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                            <?php foreach ($event_data['volunteers'] as $volunteer): ?>
                                <div class="volunteer-badge">
                                    <strong><?= htmlspecialchars($volunteer['name']); ?></strong>
                                    <small class="text-muted" style="display: block; margin-top: 3px;">
                                        ID: <?= htmlspecialchars($volunteer['volunteer_id']); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center py-4">
            <div class="no-registrations">
                <i class="fas fa-inbox"></i>
                <p style="font-size: 1.1em; margin: 10px 0;">📭 No registrations found yet</p>
                <small>Start by registering volunteers for events using the Admin Registration Panel above.</small>
            </div>
        </div>
    <?php endif; ?>

    <a href="dashboard.php" class="btn btn-secondary mt-4">← Back to Dashboard</a>

    <!-- JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        // Search volunteers in bulk registration
        document.getElementById('volunteerSearch').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.volunteer-item').forEach(item => {
                const checkbox = item.querySelector('input[type="checkbox"]');
                const name = checkbox.dataset.name.toLowerCase();
                const id = checkbox.dataset.id.toLowerCase();
                
                if (name.includes(searchTerm) || id.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Update selected count
        document.querySelectorAll('.volunteer-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedCount);
        });

        function updateSelectedCount() {
            const count = document.querySelectorAll('.volunteer-checkbox:checked').length;
            document.getElementById('selectedCount').textContent = count;
        }

        function selectAll() {
            document.querySelectorAll('.volunteer-checkbox').forEach(checkbox => {
                const item = checkbox.closest('.volunteer-item');
                if (item.style.display !== 'none') {
                    checkbox.checked = true;
                }
            });
            updateSelectedCount();
        }

        function deselectAll() {
            document.querySelectorAll('.volunteer-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            updateSelectedCount();
        }
    </script>
</body>
</html>