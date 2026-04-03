<?php
session_start();
if (!isset($_SESSION['volunteer'])) {
    header("Location: ../login.php");
    exit;
}

include("../db/connection.php");

$volunteer_id = $_SESSION['volunteer'];
$stmt = $pdo->prepare("SELECT * FROM volunteers WHERE volunteer_id=?");
$stmt->execute([$volunteer_id]);
$user = $stmt->fetch();

// Get counts for volunteer
$events_stmt = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE volunteer_id = ?");
$events_stmt->execute([$volunteer_id]);
$events_count = $events_stmt->fetchColumn();

$certificates_stmt = $pdo->prepare("SELECT COUNT(*) FROM certificates WHERE volunteer_id = ?");
$certificates_stmt->execute([$volunteer_id]);
$certificates_count = $certificates_stmt->fetchColumn();

// Get hours progress
$total_hours = $user['total_hours'];
$progress_120 = min(100, ($total_hours / 120) * 100);
$progress_240 = min(100, ($total_hours / 240) * 100);

// Check certificate status
$has_120_stmt = $pdo->prepare("SELECT COUNT(*) FROM certificates WHERE volunteer_id = ? AND (certificate_code LIKE 'CERT-120-%' OR certificate_type = '120_hours')");
$has_120_stmt->execute([$volunteer_id]);
$has_120_cert = $has_120_stmt->fetchColumn();

$has_240_stmt = $pdo->prepare("SELECT COUNT(*) FROM certificates WHERE volunteer_id = ? AND (certificate_code LIKE 'CERT-240-%' OR certificate_type = '240_hours')");
$has_240_stmt->execute([$volunteer_id]);
$has_240_cert = $has_240_stmt->fetchColumn();

// Get event participation stats
$event_stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT r.event_id) as total_events,
        SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as attended_events,
        SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent_events
    FROM event_registrations r
    LEFT JOIN attendance a ON r.event_id = a.event_id AND r.volunteer_id = a.volunteer_id
    WHERE r.volunteer_id = ?
");
$event_stats_stmt->execute([$volunteer_id]);
$event_stats = $event_stats_stmt->fetch();

// Get upcoming events count
$upcoming_events_stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT r.event_id) 
    FROM event_registrations r
    JOIN events e ON r.event_id = e.event_id
    WHERE r.volunteer_id = ? AND e.event_date >= CURDATE()
");
$upcoming_events_stmt->execute([$volunteer_id]);
$upcoming_events_count = $upcoming_events_stmt->fetchColumn();

// Get recent activities
$recent_activities_stmt = $pdo->prepare("
    (SELECT 'event_registration' as type, title, registered_at as date 
     FROM event_registrations r 
     JOIN events e ON r.event_id = e.event_id 
     WHERE r.volunteer_id = ?)
    UNION ALL
    (SELECT 'certificate_issued' as type, certificate_code as title, issued_date as date 
     FROM certificates 
     WHERE volunteer_id = ?)
    ORDER BY date DESC 
    LIMIT 5
");
$recent_activities_stmt->execute([$volunteer_id, $volunteer_id]);
$recent_activities = $recent_activities_stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Volunteer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-stats { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
        .stat-card { flex: 1; min-width: 200px; padding: 20px; background: #f8f9fa; border-radius: 10px; text-align: center; border: 1px solid #dee2e6; }
        .stat-number { font-size: 2em; font-weight: bold; color: #007bff; }
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
        .progress { height: 25px; margin-bottom: 15px; }
        .progress-label { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .cert-badge { font-size: 0.9em; }
        .chart-container { 
            background: white; 
            padding: 20px; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }
        .chart-container h5 { margin-bottom: 15px; }
        .chart-wrapper { position: relative; height: 350px; width: 100%; display: flex; align-items: center; justify-content: center; }
        .activity-item { padding: 10px; border-left: 3px solid #007bff; margin-bottom: 10px; background: #f8f9fa; }
        .dashboard-row { display: flex; gap: 20px; }
        .dashboard-row > div { flex: 1; }
        .card { height: 100%; display: flex; flex-direction: column; }
        .card-body { flex: 1; overflow-y: auto; }
        @media (max-width: 768px) {
            .chart-wrapper { height: 300px; }
            .dashboard-row { flex-direction: column; gap: 15px; }
            .card { height: auto; }
            .card-body { overflow-y: visible; }
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
    </div>

    <h2><img src="../assets/profile_images/<?= $user['profile_image'] ?: 'default_profile.jpg' ?>" 
     class="rounded-circle me-3" 
     width="40" height="40"
     alt="Profile"
     onerror="this.src='../assets/images/default_profile.jpg'">Welcome, <?= htmlspecialchars($user['name']); ?> 👋</h2>
    
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-number"><?= $events_count ?></div>
            <div>Events Registered</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $total_hours ?></div>
            <div>Hours Completed</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $certificates_count ?></div>
            <div>Certificates Earned</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $event_stats['attended_events'] ?? 0 ?></div>
            <div>Events Attended</div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="chart-container">
                <h5>📊 Event Participation</h5>
                <div class="chart-wrapper">
                    <canvas id="participationChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-container">
                <h5>🎯 Certificate Progress</h5>
                <div class="chart-wrapper">
                    <canvas id="progressChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4 dashboard-row">
        <div>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Profile Information</h5>
                    <p><strong>Volunteer ID:</strong> <code><?= htmlspecialchars($user['volunteer_id']); ?></code></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']); ?></p>
                    <p><strong>Department:</strong> <?= htmlspecialchars($user['department']); ?></p>
                    <p><strong>Year:</strong> <?= htmlspecialchars($user['year']); ?></p>
                    <p><strong>Total Hours:</strong> <span class="badge bg-primary"><?= $total_hours ?> hours</span></p>
                </div>
            </div>
        </div>
        <div>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Certificate Progress</h5>
                    
                    <!-- 120 Hours Progress -->
                    <div class="progress-label">
                        <span>120 Hours Certificate</span>
                        <span>
                            <?php if ($has_120_cert): ?>
                                <span class="badge bg-success cert-badge">✓ Earned</span>
                            <?php else: ?>
                                <span><?= $total_hours ?>/120 hours</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-<?= $has_120_cert ? 'success' : 'warning' ?>" 
                             style="width: <?= $progress_120 ?>%">
                            <?= number_format($progress_120, 1) ?>%
                        </div>
                    </div>
                    
                    <!-- 240 Hours Progress -->
                    <div class="progress-label">
                        <span>240 Hours Certificate</span>
                        <span>
                            <?php if ($has_240_cert): ?>
                                <span class="badge bg-success cert-badge">✓ Earned</span>
                            <?php else: ?>
                                <span><?= $total_hours ?>/240 hours</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-<?= $has_240_cert ? 'success' : 'info' ?>" 
                             style="width: <?= $progress_240 ?>%">
                            <?= number_format($progress_240, 1) ?>%
                        </div>
                    </div>
                    
                    <?php if (!$has_120_cert && $total_hours < 120): ?>
                        <p class="text-muted mt-2">
                            <small>You need <?= 120 - $total_hours ?> more hours for 120-hour certificate</small>
                        </p>
                    <?php elseif (!$has_240_cert && $total_hours < 240): ?>
                        <p class="text-muted mt-2">
                            <small>You need <?= 240 - $total_hours ?> more hours for 240-hour certificate</small>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4 dashboard-row">
        <div>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Recent Activity</h5>
                    <?php if (count($recent_activities) > 0): ?>
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <strong>
                                    <?php if ($activity['type'] == 'event_registration'): ?>
                                        📅 Registered for:
                                    <?php else: ?>
                                        🎓 Certificate Issued:
                                    <?php endif; ?>
                                </strong>
                                <?= htmlspecialchars($activity['title']) ?><br>
                                <small class="text-muted"><?= date('M j, Y g:i A', strtotime($activity['date'])) ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No recent activities.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Quick Actions</h5>
                    <div class="d-grid gap-2">
                        <a href="view_events.php" class="btn btn-primary">Browse Events</a>
                        <a href="my_registrations.php" class="btn btn-info">My Registrations</a>
                        <a href="my_certificates.php" class="btn btn-success">My Certificates</a>
                        <a href="upload_photos.php" class="btn btn-warning">Upload Photos</a>
                        <a href="feedback.php" class="btn btn-secondary">Submit Feedback</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Event Participation Chart
        const participationCtx = document.getElementById('participationChart').getContext('2d');
        new Chart(participationCtx, {
            type: 'doughnut',
            data: {
                labels: ['Attended Events', 'Absent Events', 'Upcoming Events'],
                datasets: [{
                    data: [
                        <?= $event_stats['attended_events'] ?? 0 ?>,
                        <?= $event_stats['absent_events'] ?? 0 ?>,
                        <?= $upcoming_events_count ?? 0 ?>
                    ],
                    backgroundColor: ['#28a745', '#dc3545', '#17a2b8']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Progress Chart
        const progressCtx = document.getElementById('progressChart').getContext('2d');
        new Chart(progressCtx, {
            type: 'bar',
            data: {
                labels: ['120 Hours', '240 Hours'],
                datasets: [{
                    label: 'Your Progress',
                    data: [<?= $total_hours ?>, <?= $total_hours ?>],
                    backgroundColor: ['#ffc107', '#17a2b8'],
                    maxBarThickness: 30
                }, {
                    label: 'Required',
                    data: [120, 240],
                    backgroundColor: ['#e9ecef', '#e9ecef'],
                    type: 'line',
                    fill: false,
                    borderColor: '#6c757d',
                    borderDash: [5, 5],
                    pointStyle: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 250
                    }
                }
            }
        });
    </script>
</body>
</html>