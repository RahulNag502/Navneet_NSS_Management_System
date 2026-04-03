<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit;
}
include("../db/connection.php");

// Fetch all feedback with volunteer and event information
$stmt = $pdo->prepare("
    SELECT 
        f.id,
        f.rating,
        f.comments,
        f.submitted_at,
        v.name as volunteer_name,
        v.email as volunteer_email,
        e.title as event_title,
        e.event_date,
        e.event_type
    FROM feedback f
    JOIN volunteers v ON f.volunteer_id = v.volunteer_id
    JOIN events e ON f.event_id = e.event_id
    ORDER BY f.submitted_at DESC
");
$stmt->execute();
$feedbacks = $stmt->fetchAll();

// Get statistics
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_feedback,
        AVG(rating) as avg_rating,
        COUNT(CASE WHEN rating = 5 THEN 1 END) as excellent,
        COUNT(CASE WHEN rating = 4 THEN 1 END) as very_good,
        COUNT(CASE WHEN rating = 3 THEN 1 END) as good,
        COUNT(CASE WHEN rating = 2 THEN 1 END) as fair,
        COUNT(CASE WHEN rating = 1 THEN 1 END) as poor
    FROM feedback
");
$stats_stmt->execute();
$stats = $stats_stmt->fetch();
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Feedback - NSS Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
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

        .stats-card { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .rating-badge { 
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
            min-width: 80px;
            text-align: center;
        }
        .rating-5 { background: #28a745; color: white; }
        .rating-4 { background: #17a2b8; color: white; }
        .rating-3 { background: #ffc107; color: white; }
        .rating-2 { background: #fd7e14; color: white; }
        .rating-1 { background: #dc3545; color: white; }
        .feedback-card {
            border-left: 4px solid #667eea;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .feedback-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateX(5px);
        }
        .event-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .event-badge.blood_camp { background: #ffe5e5; color: #dc3545; }
        .event-badge.tree_plantation { background: #e5f3e5; color: #28a745; }
        .event-badge.cleanliness_drive { background: #e5f0ff; color: #17a2b8; }
        .event-badge.awareness { background: #fff3e5; color: #fd7e14; }
        .event-badge.medical_camp { background: #e5e5ff; color: #667eea; }
        .event-badge.educational { background: #f0e5ff; color: #764ba2; }
        .event-badge.cultural { background: #ffe5f0; color: #e74c3c; }
        .event-badge.sports { background: #e5fff0; color: #27ae60; }
        .stars {
            color: #ffc107;
            font-size: 1.1em;
            letter-spacing: 2px;
        }
        .filter-section {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
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
        <a href="gallery_upload.php">📸 Upload Gallery</a>
        <a href="gallery_view.php">🖼️ View Gallery</a>
        <a href="manage_notifications.php">📢 Notifications</a>
        <a href="view_feedback.php">💬 Feedback</a>
        <a href="../logout.php">🚪 Logout</a>
    </div>

    <h2>💬 Volunteer Feedback</h2>
    <p class="lead text-muted mb-4">View and manage feedback from volunteers for events</p>

    <!-- Statistics Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="stats-card">
                <div class="row text-center">
                    <div class="col-md-2">
                        <h4><?= $stats['total_feedback'] ?? 0 ?></h4>
                        <p class="mb-0">Total Feedback</p>
                    </div>
                    <div class="col-md-2">
                        <h4><?= number_format($stats['avg_rating'] ?? 0, 1) ?>/5</h4>
                        <p class="mb-0">Average Rating</p>
                    </div>
                    <div class="col-md-2">
                        <h4><?= $stats['excellent'] ?? 0 ?></h4>
                        <p class="mb-0">⭐⭐⭐⭐⭐ Excellent</p>
                    </div>
                    <div class="col-md-2">
                        <h4><?= $stats['very_good'] ?? 0 ?></h4>
                        <p class="mb-0">⭐⭐⭐⭐ Very Good</p>
                    </div>
                    <div class="col-md-2">
                        <h4><?= $stats['good'] ?? 0 ?></h4>
                        <p class="mb-0">⭐⭐⭐ Good</p>
                    </div>
                    <div class="col-md-2">
                        <h4><?= $stats['fair'] + $stats['poor'] ?? 0 ?></h4>
                        <p class="mb-0">Fair/Poor</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback List -->
    <div class="filter-section">
        <h5 class="mb-3">📋 All Feedback</h5>
        
        <?php if (count($feedbacks) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Event</th>
                            <th>Type</th>
                            <th>Volunteer</th>
                            <th>Rating</th>
                            <th>Comments</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedbacks as $fb): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars(substr($fb['event_title'], 0, 30)) ?></strong><br>
                                <small class="text-muted"><?= date('M j, Y', strtotime($fb['event_date'])) ?></small>
                            </td>
                            <td>
                                <span class="event-badge <?= $fb['event_type'] ?>">
                                    <?php 
                                        $event_types = [
                                            'blood_camp' => '🩸 Blood',
                                            'tree_plantation' => '🌳 Tree',
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
                                        echo $event_types[$fb['event_type']] ?? '📋 Other';
                                    ?>
                                </span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($fb['volunteer_name']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($fb['volunteer_email']) ?></small>
                            </td>
                            <td>
                                <span class="rating-badge rating-<?= $fb['rating'] ?>">
                                    <?php echo str_repeat('⭐', $fb['rating']); ?> (<?= $fb['rating'] ?>/5)
                                </span>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($fb['comments']) ?>">
                                    <?= htmlspecialchars(substr($fb['comments'], 0, 50)) ?>
                                    <?php if (strlen($fb['comments']) > 50): ?>...<?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <small><?= date('M j, Y', strtotime($fb['submitted_at'])) ?></small>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#feedbackModal<?= $fb['id'] ?>">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>

                        <!-- Feedback Detail Modal -->
                        <div class="modal fade" id="feedbackModal<?= $fb['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header bg-info text-white">
                                        <h5 class="modal-title">💬 Feedback Details</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <h6 class="text-muted">Event</h6>
                                                <p><strong><?= htmlspecialchars($fb['event_title']) ?></strong></p>
                                                <h6 class="text-muted">Date</h6>
                                                <p><?= date('F j, Y', strtotime($fb['event_date'])) ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <h6 class="text-muted">Volunteer</h6>
                                                <p><strong><?= htmlspecialchars($fb['volunteer_name']) ?></strong></p>
                                                <h6 class="text-muted">Email</h6>
                                                <p><?= htmlspecialchars($fb['volunteer_email']) ?></p>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="mb-3">
                                            <h6 class="text-muted">Rating</h6>
                                            <p>
                                                <span class="rating-badge rating-<?= $fb['rating'] ?>">
                                                    <?php echo str_repeat('⭐', $fb['rating']); ?> (<?= $fb['rating'] ?>/5)
                                                </span>
                                            </p>
                                        </div>
                                        <div class="mb-3">
                                            <h6 class="text-muted">Comments</h6>
                                            <p class="bg-light p-3 rounded">
                                                <?= htmlspecialchars($fb['comments']) ?>
                                            </p>
                                        </div>
                                        <div class="mb-3">
                                            <h6 class="text-muted">Submitted On</h6>
                                            <p><?= date('F j, Y \a\t g:i A', strtotime($fb['submitted_at'])) ?></p>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No feedback has been submitted yet.
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
