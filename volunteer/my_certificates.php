<?php
session_start();
if (!isset($_SESSION['volunteer'])) {
    header("Location: ../login.php");
    exit;
}
include("../db/connection.php");

$volunteer_id = $_SESSION['volunteer'];

// Simple query that works with any certificate table structure
$stmt = $pdo->prepare("
    SELECT certificate_code, issued_date 
    FROM certificates 
    WHERE volunteer_id = ?
    ORDER BY issued_date DESC
");
$stmt->execute([$volunteer_id]);
$certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total hours
$hours_stmt = $pdo->prepare("SELECT total_hours FROM volunteers WHERE volunteer_id = ?");
$hours_stmt->execute([$volunteer_id]);
$total_hours = $hours_stmt->fetchColumn();

// Determine certificate types based on certificate codes
$certificates_with_types = [];
foreach ($certificates as $cert) {
    $code = $cert['certificate_code'];
    if (strpos($code, 'CERT-240-') === 0) {
        $cert['certificate_type'] = '240_hours';
        $cert['type_text'] = '240 Hours';
        $cert['badge_color'] = 'bg-success';
    } elseif (strpos($code, 'CERT-120-') === 0) {
        $cert['certificate_type'] = '120_hours';
        $cert['type_text'] = '120 Hours';
        $cert['badge_color'] = 'bg-warning';
    } else {
        $cert['certificate_type'] = 'manual';
        $cert['type_text'] = 'Participation';
        $cert['badge_color'] = 'bg-info';
    }
    $certificates_with_types[] = $cert;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Certificates</title>
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
        .certificate-card { border-left: 4px solid #28a745; }
        .hours-badge { font-size: 1.1em; }
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

    <h2>🏅 My Certificates</h2>
    
    <!-- Hours Summary -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Service Hours Summary</h5>
            <p class="card-text">
                Total Hours Completed: 
                <span class="badge bg-primary hours-badge"><?= $total_hours ?> hours</span>
            </p>
            <?php if ($total_hours < 120): ?>
                <p class="text-warning">
                    <i class="fas fa-info-circle"></i>
                    You need <?= 120 - $total_hours ?> more hours to qualify for the 120-hour certificate.
                </p>
            <?php elseif ($total_hours < 240): ?>
                <p class="text-info">
                    <i class="fas fa-info-circle"></i>
                    You need <?= 240 - $total_hours ?> more hours to qualify for the 240-hour certificate.
                </p>
            <?php else: ?>
                <p class="text-success">
                    <i class="fas fa-check-circle"></i>
                    Congratulations! You have completed all certificate requirements.
                </p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (count($certificates_with_types) > 0): ?>
        <div class="row">
            <?php foreach ($certificates_with_types as $c): ?>
            <div class="col-md-6 mb-3">
                <div class="card certificate-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h5 class="card-title">NSS Certificate</h5>
                            <span class="badge <?= $c['badge_color'] ?>"><?= $c['type_text'] ?></span>
                        </div>
                        <p class="card-text">
                        <strong>Certificate Code:</strong><br>
                        <code class="fs-5"><?= htmlspecialchars($c['certificate_code']) ?></code>
                        </p>

                        <div class="mt-3">
                        <a href="../admin/certificates/<?= $c['certificate_code'] ?>.pdf" 
                        target="_blank" 
                        class="btn btn-sm btn-primary">
                        👁 Preview
                        </a>

                        <a href="../admin/certificates/<?= $c['certificate_code'] ?>.pdf" 
                        download 
                        class="btn btn-sm btn-success">
                        ⬇ Download
                        </a>
                        </div>
                        <p class="card-text">
                            <strong>Issued On:</strong><br>
                            <?= date('F j, Y', strtotime($c['issued_date'])) ?>
                        </p>
                        <p class="card-text">
                            <small class="text-muted">
                                This certificate recognizes your dedication and service to the community through the National Service Scheme.
                            </small>
                        </p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <h5>No Certificates Yet</h5>
            <p class="mb-2">You haven't earned any certificates yet. Certificates are automatically issued when you complete:</p>
            <ul>
                <li><strong>120 hours</strong> of community service for the Basic Certificate</li>
                <li><strong>240 hours</strong> of community service for the Advanced Certificate</li>
            </ul>
            <p class="mb-0">Keep participating in events to earn your certificates!</p>
        </div>
    <?php endif; ?>
    
    <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
</body>
</html>