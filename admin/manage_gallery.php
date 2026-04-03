<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit;
}

include("../db/connection.php");

// Handle feature/unfeature actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];
    
    if ($action == 'feature') {
        $stmt = $pdo->prepare("UPDATE gallery SET featured = 1 WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Image featured successfully!";
    } elseif ($action == 'unfeature') {
        $stmt = $pdo->prepare("UPDATE gallery SET featured = 0 WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Image removed from featured!";
    }
}

// Get all gallery images
$gallery_stmt = $pdo->prepare("
    SELECT g.*, 
           CASE 
               WHEN g.user_type = 'volunteer' THEN v.name 
               ELSE g.uploaded_by 
           END as display_name
    FROM gallery g
    LEFT JOIN volunteers v ON g.uploaded_by = v.volunteer_id AND g.user_type = 'volunteer'
    ORDER BY g.featured DESC, g.uploaded_at DESC
");
$gallery_stmt->execute();
$gallery_images = $gallery_stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Gallery</title>
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
        .gallery-img { height: 150px; object-fit: cover; width: 100%; }
        .featured-badge { background: #ffc107; color: #000; }
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
        <a href="gallery_upload.php">🖼️ Upload Photos</a>
        <a href="manage_gallery.php">⭐ Manage Gallery</a>
        <a href="manage_notifications.php">📢 Notifications</a>        <a href="view_feedback.php">💬 Feedback</a>        <a href="../logout.php">🚪 Logout</a>
    </div>

    <h2>⭐ Manage Gallery Features</h2>
    
    <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
    
    <div class="alert alert-info">
        <strong>Featured Images</strong> will appear on the homepage gallery. Select the best photos to showcase your NSS activities.
    </div>

    <div class="row">
        <?php foreach ($gallery_images as $image): 
            $image_path = $image['user_type'] == 'volunteer' ? 
                "../assets/volunteer_uploads/" . $image['image_path'] : 
                "../assets/uploads/" . $image['image_path'];
        ?>
        <div class="col-md-4 mb-4">
            <div class="card">
                <img src="<?= $image_path ?>" class="gallery-img card-img-top" alt="Gallery image">
                <div class="card-body">
                    <h6 class="card-title">
                        <?php if ($image['featured']): ?>
                            <span class="badge featured-badge">⭐ Featured</span>
                        <?php endif; ?>
                    </h6>
                    <p class="card-text">
                        <small>
                            <strong>By:</strong> <?= htmlspecialchars($image['display_name'] ?? 'Unknown') ?><br>
                            <strong>Type:</strong> <?= $image['user_type'] == 'volunteer' ? 'Volunteer' : 'Official' ?><br>
                            <strong>Date:</strong> <?= date('M j, Y', strtotime($image['uploaded_at'])) ?>
                        </small>
                    </p>
                    <div class="d-grid gap-2">
                        <?php if ($image['featured']): ?>
                            <a href="?action=unfeature&id=<?= $image['id'] ?>" class="btn btn-warning btn-sm">
                                ❌ Remove from Featured
                            </a>
                        <?php else: ?>
                            <a href="?action=feature&id=<?= $image['id'] ?>" class="btn btn-success btn-sm">
                                ⭐ Feature on Homepage
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</body>
</html>