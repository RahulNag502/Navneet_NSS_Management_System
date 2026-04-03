<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit;
}
include("../db/connection.php");


$success = "";
$error = "";

// Issue certificate manually
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $volunteer_id = $_POST['volunteer_id'];
    $certificate_type = $_POST['certificate_type'];

    // Get volunteer's total hours, name, and email
    $hours_stmt = $pdo->prepare("SELECT total_hours, name, email FROM volunteers WHERE volunteer_id = ?");
    $hours_stmt->execute([$volunteer_id]);
    $volunteer = $hours_stmt->fetch();
    
    if (!$volunteer) {
        $error = "Volunteer not found!";
    } else {
        $total_hours = $volunteer['total_hours'];
        $volunteer_name = $volunteer['name'];
        $volunteer_email = $volunteer['email'];
        
        // Validate hours requirement
        if ($certificate_type == '120_hours' && $total_hours < 120) {
            $error = "Cannot issue 120-hour certificate! $volunteer_name has only $total_hours hours (requires 120 hours).";
        } elseif ($certificate_type == '240_hours' && $total_hours < 240) {
            $error = "Cannot issue 240-hour certificate! $volunteer_name has only $total_hours hours (requires 240 hours).";
        } else {
            // Check if certificate_type column exists
            $check_column = $pdo->prepare("SHOW COLUMNS FROM certificates LIKE 'certificate_type'");
            $check_column->execute();
            $column_exists = $check_column->fetch();

            if ($column_exists) {
                // Check if certificate already exists
                $check = $pdo->prepare("SELECT id FROM certificates WHERE volunteer_id = ? AND certificate_type = ?");
                $check->execute([$volunteer_id, $certificate_type]);
                
                if ($check->fetch()) {
                    $error = "This type of certificate is already issued for $volunteer_name!";
                } else {
                    // Generate unique certificate code
                    $certificate_code = "CERT-" . ($certificate_type == '120_hours' ? '120' : '240') . "-" . strtoupper(uniqid());

                    $stmt = $pdo->prepare("INSERT INTO certificates (volunteer_id, certificate_code, certificate_type) VALUES (?, ?, ?)");
                    if ($stmt->execute([$volunteer_id, $certificate_code, $certificate_type])) {

                       require_once "../admin/includes/generate_certificate.php";
require_once "../includes/EmailSender.php";

$pdf_file = generateCertificate(
    $volunteer_name,
    $certificate_type,
    $certificate_code
);

$emailSender = new EmailSender();

$emailResult = $emailSender->sendCertificateEmail(
    $volunteer_name,
    $volunteer_email,
    $certificate_type,
    $certificate_code,
    $pdf_file
);
                        
                        $success = "Certificate issued successfully!<br>Volunteer: $volunteer_name<br>Certificate Code: $certificate_code<br>Type: " . ($certificate_type == '120_hours' ? '120 Hours' : '240 Hours');
                        
                        if ($emailResult['success']) {
                            $success .= "<br>📧 Notification email sent to volunteer.";
                        } else {
                            $success .= "<br>⚠️ Certificate issued but email notification failed.";
                        }
                    } else {
                        $error = "Failed to issue certificate!";
                    }
                }
            } else {
                // Fallback without certificate_type column
                $certificate_code = "CERT-" . ($certificate_type == '120_hours' ? '120' : '240') . "-" . strtoupper(uniqid());
                
                // Check if similar certificate already exists (by code pattern)
                $check = $pdo->prepare("SELECT id FROM certificates WHERE volunteer_id = ? AND certificate_code LIKE ?");
                $pattern = "CERT-" . ($certificate_type == '120_hours' ? '120' : '240') . "-%";
                $check->execute([$volunteer_id, $pattern]);
                
                if ($check->fetch()) {
                    $error = "A " . ($certificate_type == '120_hours' ? '120-hour' : '240-hour') . " certificate is already issued for $volunteer_name!";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO certificates (volunteer_id, certificate_code) VALUES (?, ?)");
                    if ($stmt->execute([$volunteer_id, $certificate_code])) {
                        // Send email notification
                        require_once "../includes/EmailSender.php";
                        $emailSender = new EmailSender();
                        $emailResult = $emailSender->sendCertificateEmail(
                            $volunteer_name,
                            $volunteer_email,
                            $certificate_type,
                            $certificate_code
                        );
                        
                        $success = "Certificate issued successfully!<br>Volunteer: $volunteer_name<br>Certificate Code: $certificate_code<br>Type: " . ($certificate_type == '120_hours' ? '120 Hours' : '240 Hours');
                        
                        if ($emailResult['success']) {
                            $success .= "<br>📧 Notification email sent to volunteer.";
                        } else {
                            $success .= "<br>⚠️ Certificate issued but email notification failed.";
                        }
                    } else {
                        $error = "Failed to issue certificate!";
                    }
                }
            }
        }
    }
}

// Get volunteers with their hours
$volunteers = $pdo->query("
    SELECT v.*
    FROM volunteers v 
    ORDER BY v.total_hours DESC, v.name
")->fetchAll();

// Get certificate counts and types for each volunteer
$volunteer_certificates = [];
foreach ($volunteers as $v) {
    $certs_stmt = $pdo->prepare("SELECT certificate_code FROM certificates WHERE volunteer_id = ?");
    $certs_stmt->execute([$v['volunteer_id']]);
    $certificates = $certs_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $has_120_cert = false;
    $has_240_cert = false;
    
    foreach ($certificates as $cert) {
        if (strpos($cert['certificate_code'], 'CERT-120-') === 0) {
            $has_120_cert = true;
        } elseif (strpos($cert['certificate_code'], 'CERT-240-') === 0) {
            $has_240_cert = true;
        }
    }
    
    $volunteer_certificates[$v['volunteer_id']] = [
        'count' => count($certificates),
        'has_120_cert' => $has_120_cert,
        'has_240_cert' => $has_240_cert
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Issue Certificates</title>
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
        .cert-badge { font-size: 0.8em; }
        .eligibility-warning { background: #fff3cd; border-left: 4px solid #ffc107; }
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

    <h2>🏅 Issue Certificates</h2>
    
    <?php if (!empty($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
    <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
    
    <!-- Manual Certificate Issuance -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Manual Certificate Issuance</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <strong>Certificate Requirements:</strong><br>
                • 120-hour Certificate: Requires minimum 120 service hours<br>
                • 240-hour Certificate: Requires minimum 240 service hours
            </div>
            <form method="post" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Volunteer *</label>
                    <select name="volunteer_id" class="form-select" required id="volunteerSelect" onchange="updateEligibility()">
                        <option value="">-- Select Volunteer --</option>
                        <?php foreach ($volunteers as $v): 
                            $certs = $volunteer_certificates[$v['volunteer_id']];
                        ?>
                        <option value="<?= $v['volunteer_id'] ?>" data-hours="<?= $v['total_hours'] ?>" data-120-cert="<?= $certs['has_120_cert'] ? '1' : '0' ?>" data-240-cert="<?= $certs['has_240_cert'] ? '1' : '0' ?>">
                            <?= htmlspecialchars($v['name']) ?> (<?= $v['volunteer_id'] ?>) - <?= $v['total_hours'] ?> hrs
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Certificate Type *</label>
                    <select name="certificate_type" class="form-select" required id="certTypeSelect" onchange="updateEligibility()">
                        <option value="">-- Select Type --</option>
                        <option value="120_hours">120 Hours Certificate</option>
                        <option value="240_hours">240 Hours Certificate</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100" id="issueButton">Issue Certificate</button>
                </div>
            </form>
            <div id="eligibilityMessage" class="mt-3"></div>
        </div>
    </div>

    <!-- Volunteers with Hours and Certificates -->
    <h4>Volunteers Hours & Certificates Status</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Volunteer</th>
                    <th>Total Hours</th>
                    <th>120 Hours Certificate</th>
                    <th>240 Hours Certificate</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($volunteers as $v): 
                    $certs = $volunteer_certificates[$v['volunteer_id']];
                    $can_get_120 = $v['total_hours'] >= 120 && !$certs['has_120_cert'];
                    $can_get_240 = $v['total_hours'] >= 240 && !$certs['has_240_cert'];
                ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($v['name']) ?></strong><br>
                        <small class="text-muted"><?= $v['volunteer_id'] ?></small>
                    </td>
                    <td>
                        <span class="badge bg-<?= $v['total_hours'] >= 240 ? 'success' : ($v['total_hours'] >= 120 ? 'warning' : 'info') ?> hours-badge">
                            <?= $v['total_hours'] ?> hours
                        </span>
                    </td>
                    <td>
                        <?php if ($certs['has_120_cert']): ?>
                            <span class="badge bg-success cert-badge">✓ Issued</span>
                        <?php elseif ($v['total_hours'] >= 120): ?>
                            <span class="badge bg-warning cert-badge">✅ Eligible</span>
                        <?php else: ?>
                            <span class="badge bg-secondary cert-badge">❌ Need <?= 120 - $v['total_hours'] ?> hrs</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($certs['has_240_cert']): ?>
                            <span class="badge bg-success cert-badge">✓ Issued</span>
                        <?php elseif ($v['total_hours'] >= 240): ?>
                            <span class="badge bg-warning cert-badge">✅ Eligible</span>
                        <?php else: ?>
                            <span class="badge bg-secondary cert-badge">❌ Need <?= 240 - $v['total_hours'] ?> hrs</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($can_get_240): ?>
                            <span class="badge bg-danger">240-hour Cert Pending</span>
                        <?php elseif ($can_get_120): ?>
                            <span class="badge bg-warning">120-hour Cert Pending</span>
                        <?php else: ?>
                            <span class="badge bg-success">Up to Date</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>

    <script>
        function updateEligibility() {
            const volunteerSelect = document.getElementById('volunteerSelect');
            const certTypeSelect = document.getElementById('certTypeSelect');
            const issueButton = document.getElementById('issueButton');
            const messageDiv = document.getElementById('eligibilityMessage');
            
            const selectedOption = volunteerSelect.options[volunteerSelect.selectedIndex];
            const certType = certTypeSelect.value;
            
            if (!selectedOption.value || !certType) {
                messageDiv.innerHTML = '';
                issueButton.disabled = false;
                return;
            }
            
            const hours = parseInt(selectedOption.getAttribute('data-hours'));
            const has120Cert = selectedOption.getAttribute('data-120-cert') === '1';
            const has240Cert = selectedOption.getAttribute('data-240-cert') === '1';
            
            let message = '';
            let canIssue = true;
            
            if (certType === '120_hours') {
                if (has120Cert) {
                    message = '<div class="alert alert-danger">❌ 120-hour certificate already issued for this volunteer!</div>';
                    canIssue = false;
                } else if (hours < 120) {
                    message = `<div class="alert alert-danger">❌ Cannot issue 120-hour certificate! Volunteer has only ${hours} hours (requires 120 hours).</div>`;
                    canIssue = false;
                } else {
                    message = '<div class="alert alert-success">✅ Volunteer is eligible for 120-hour certificate!</div>';
                }
            } else if (certType === '240_hours') {
                if (has240Cert) {
                    message = '<div class="alert alert-danger">❌ 240-hour certificate already issued for this volunteer!</div>';
                    canIssue = false;
                } else if (hours < 240) {
                    message = `<div class="alert alert-danger">❌ Cannot issue 240-hour certificate! Volunteer has only ${hours} hours (requires 240 hours).</div>`;
                    canIssue = false;
                } else {
                    message = '<div class="alert alert-success">✅ Volunteer is eligible for 240-hour certificate!</div>';
                }
            }
            
            messageDiv.innerHTML = message;
            issueButton.disabled = !canIssue;
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateEligibility();
        });
    </script>
</body>
</html>