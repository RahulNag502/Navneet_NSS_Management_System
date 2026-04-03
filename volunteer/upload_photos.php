<?php
session_start();
if (!isset($_SESSION['volunteer'])) {
    header("Location: ../login.php");
    exit;
}

include("../db/connection.php");

$volunteer_id = $_SESSION['volunteer'];
$success = "";
$error = "";

// Get only past and current events that volunteer has attended for dropdown
$today = date('Y-m-d');
$events = $pdo->prepare("
    SELECT DISTINCT e.event_id, e.title, e.event_date, e.event_type
    FROM events e 
    JOIN attendance a ON e.event_id = a.event_id 
    WHERE a.volunteer_id = ? AND a.status = 'Present' AND e.event_date <= ?
    ORDER BY e.event_date DESC
");
$events->execute([$volunteer_id, $today]);
$events = $events->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['images'])) {
    $event_id = $_POST['event_id'];
    
    // Validate that selected event date is not in the future
    $check_event = $pdo->prepare("SELECT event_date, title FROM events WHERE event_id = ?");
    $check_event->execute([$event_id]);
    $event_check = $check_event->fetch();
    
    if (!$event_check) {
        $error = "❌ Invalid event selected";
    } elseif (strtotime($event_check['event_date']) > strtotime($today)) {
        $error = "❌ Cannot upload photos for upcoming events. The event " . htmlspecialchars($event_check['title']) . " has not happened yet.";
    } elseif (!$error) {
        $uploadDir = '../assets/uploads/';
        
        // Create uploads directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $uploaded_files = [];
        $has_errors = false;
        
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = time() . '_' . $volunteer_id . '_' . basename($_FILES['images']['name'][$key]);
                $targetPath = $uploadDir . $fileName;
                
                // Validate file type
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                if (!in_array($fileExtension, $allowedTypes)) {
                    $error = "Only JPG, JPEG, PNG, GIF, MP4, AVI, MOV files are allowed.";
                    $has_errors = true;
                    break;
                }
                
                if ($_FILES['images']['size'][$key] > 10 * 1024 * 1024) { // 10MB limit
                    $error = "File size must be less than 10MB.";
                    $has_errors = true;
                    break;
                }
                
                if (move_uploaded_file($tmp_name, $targetPath)) {
                    $uploaded_files[] = $fileName;
                }
            }
        }
        
        if (!$has_errors && count($uploaded_files) > 0) {
            foreach ($uploaded_files as $file) {
                $stmt = $pdo->prepare("INSERT INTO gallery (image_path, uploaded_by, user_type, event_id) VALUES (?, ?, 'volunteer', ?)");
                $stmt->execute([$file, $volunteer_id, $event_id]);
            }
            $success = count($uploaded_files) . " file(s) uploaded successfully! Linked to event.";
        } elseif (!$has_errors) {
            $error = "No files were uploaded or there was an error.";
        }
    }
}

// Get volunteer's uploaded files with event info
$volunteer_files_stmt = $pdo->prepare("
    SELECT g.*, e.title as event_title, e.event_type, e.event_date
    FROM gallery g 
    JOIN events e ON g.event_id = e.event_id
    WHERE g.uploaded_by = ? AND g.user_type = 'volunteer'
    ORDER BY e.event_date DESC, g.uploaded_at DESC
");
$volunteer_files_stmt->execute([$volunteer_id]);
$files = $volunteer_files_stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Photos/Videos - Navneet College of Arts ,Science & Commerce.</title>
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
        .upload-area { 
            border: 2px dashed #007bff; 
            border-radius: 10px; 
            padding: 40px; 
            text-align: center; 
            background: #f8f9fa;
            margin-bottom: 20px;
        }
        .photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
        .photo-card { position: relative; border: 1px solid #dee2e6; border-radius: 8px; padding: 10px; background: white; }
        .photo-card img, .photo-card video { width: 100%; height: 150px; object-fit: cover; border-radius: 6px; }
        .photo-actions { position: absolute; top: 15px; right: 15px; }
        .video-icon { position: absolute; top: 15px; left: 15px; background: rgba(0,0,0,0.7); color: white; padding: 3px 6px; border-radius: 4px; font-size: 0.8em; }
        .event-badge { font-size: 0.7em; margin-top: 5px; }
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

    <h2>📸 Upload Photos & Videos</h2>
    
    <?php if (!empty($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
    <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Upload New Files</h5>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Select Event *</label>
                            <select name="event_id" class="form-select" required>
                                <option value="">-- Choose Event --</option>
                                <?php foreach ($events as $event): ?>
                                    <option value="<?= $event['event_id'] ?>">
                                        <?= htmlspecialchars($event['title']) ?> 
                                        (<?= date('M j, Y', strtotime($event['event_date'])) ?>)
                                        - <?= 
                                            [
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
                                            ][$event['event_type']] ?? '📋 Other'
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">📌 Only past and completed events are available. Photos can only be uploaded for events that have already happened.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="upload-area">
                            <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                            <h5>Drag & Drop or Click to Upload</h5>
                            <p class="text-muted">Upload photos and videos from events you've participated in</p>
                            <input type="file" name="images[]" class="form-control" multiple accept="image/*,video/*" required>
                            <div class="form-text mt-2">
                                Supported formats: JPG, PNG, GIF, MP4, AVI, MOV. Max file size: 10MB each. You can select multiple files.
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Upload Files
                </button>
            </form>
        </div>
    </div>

    <h4 class="mt-5">My Uploaded Files</h4>
    <?php if (count($files) > 0): ?>
        <div class="photo-grid">
            <?php foreach ($files as $file): 
                $isVideo = in_array(pathinfo($file['image_path'], PATHINFO_EXTENSION), ['mp4', 'avi', 'mov']);
            ?>
            <div class="photo-card">
                <div class="position-relative">
                    <?php if ($isVideo): ?>
                        <video style="height: 150px; width: 100%;">
                            <source src="../assets/uploads/<?= htmlspecialchars($file['image_path']) ?>" type="video/mp4">
                        </video>
                        <div class="video-icon">
                            <i class="fas fa-video"></i> Video
                        </div>
                    <?php else: ?>
                        <img src="../assets/uploads/<?= htmlspecialchars($file['image_path']) ?>" 
                             alt="Uploaded file">
                    <?php endif; ?>
                </div>
                <div class="photo-actions">
                    <a href="../assets/uploads/<?= htmlspecialchars($file['image_path']) ?>" 
                       target="_blank" 
                       class="btn btn-sm btn-info">
                        <i class="fas fa-eye"></i>
                    </a>
                </div>
                <div class="mt-2">
                    <small class="text-primary d-block fw-bold" title="<?= htmlspecialchars($file['event_title']) ?>">
                        <?= strlen($file['event_title']) > 20 ? substr($file['event_title'], 0, 20) . '...' : $file['event_title'] ?>
                    </small>
                    <span class="badge <?= 
                        [
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
                        ][$file['event_type']] ?? 'bg-light text-dark'
                    ?> event-badge">
                        <?= 
                            [
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
                            ][$file['event_type']] ?? '📋 Other'
                        ?>
                    </span>
                    <small class="text-muted d-block mt-1">
                        <?= date('M j, Y', strtotime($file['event_date'])) ?>
                    </small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> You haven't uploaded any files yet.
        </div>
    <?php endif; ?>

    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>