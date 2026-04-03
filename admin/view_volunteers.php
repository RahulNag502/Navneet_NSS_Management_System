<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit;
}

include("../db/connection.php");

// Add status column if it doesn't exist
try {
    $pdo->query("ALTER TABLE volunteers ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
} catch (Exception $e) {
    // Column already exists
}

// Handle deactivation
if (isset($_GET['deactivate'])) {
    $volunteer_id = $_GET['deactivate'];
    $stmt = $pdo->prepare("UPDATE volunteers SET status = 'inactive' WHERE volunteer_id = ?");
    $stmt->execute([$volunteer_id]);
    header("Location: view_volunteers.php");
    exit;
}

// Handle reactivation
if (isset($_GET['activate'])) {
    $volunteer_id = $_GET['activate'];
    $stmt = $pdo->prepare("UPDATE volunteers SET status = 'active' WHERE volunteer_id = ?");
    $stmt->execute([$volunteer_id]);
    header("Location: view_volunteers.php");
    exit;
}

// Handle bulk volunteer registration via Excel
$bulk_message = "";
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['upload_excel'])) {
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['excel_file'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Accept CSV and Excel files
        if (!in_array($file_ext, ['csv', 'xls', 'xlsx'])) {
            $bulk_message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Invalid file format. Please upload CSV or Excel file.</div>";
        } else {
            $volunteers_added = 0;
            $volunteers_failed = 0;
            $errors = [];
            
            // Handle CSV files directly
            if ($file_ext === 'csv') {
                $rows = array_map('str_getcsv', file($file['tmp_name']));
            } else {
                // For Excel files, convert to CSV using simple method
                $rows = [];
                if (function_exists('simplexml_load_file')) {
                    // Try to read as XML (for xlsx)
                    try {
                        $zip = new ZipArchive();
                        if ($zip->open($file['tmp_name']) === true) {
                            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
                            $zip->close();
                            
                            if ($xml) {
                                $data = simplexml_load_string($xml);
                                foreach ($data->sheetData->row as $row) {
                                    $row_data = [];
                                    foreach ($row->c as $cell) {
                                        $row_data[] = (string)$cell->v;
                                    }
                                    if (!empty($row_data)) {
                                        $rows[] = $row_data;
                                    }
                                }
                            }
                        }
                    } catch (Exception $e) {
                        // Fallback: treat as CSV
                        $rows = array_map('str_getcsv', file($file['tmp_name']));
                    }
                } else {
                    // Fallback to CSV parsing
                    $rows = array_map('str_getcsv', file($file['tmp_name']));
                }
            }
            
            // Skip header row if exists
            $header_row = array_shift($rows);
            
            // Expected columns: Name, Email, Phone, Department, Year, Password
            foreach ($rows as $index => $row) {
                if (empty(array_filter($row))) continue; // Skip empty rows
                
                $name = trim($row[0] ?? '');
                $email = trim($row[1] ?? '');
                $phone = trim($row[2] ?? '');
                $department = trim($row[3] ?? '');
                $year = trim($row[4] ?? '');
                $password = trim($row[5] ?? '');
                
                // Validation
                if (empty($name) || empty($email) || empty($password)) {
                    $volunteers_failed++;
                    $errors[] = "Row " . ($index + 2) . ": Name, Email, and Password are required";
                    continue;
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $volunteers_failed++;
                    $errors[] = "Row " . ($index + 2) . ": Invalid email format";
                    continue;
                }
                
                // Check if email already exists
                $check_email = $pdo->prepare("SELECT id FROM volunteers WHERE email = ?");
                $check_email->execute([$email]);
                if ($check_email->fetch()) {
                    $volunteers_failed++;
                    $errors[] = "Row " . ($index + 2) . ": Email already registered";
                    continue;
                }
                
                // Generate volunteer ID
                $volunteer_id = "V" . strtoupper(bin2hex(random_bytes(3)));
                $hashed_password = md5($password);
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO volunteers (volunteer_id, name, email, phone, department, year, password, status) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
                    $stmt->execute([$volunteer_id, $name, $email, $phone, $department, $year, $hashed_password]);
                    
                    // Send welcome email
                    require_once "../includes/EmailSender.php";
                    $emailSender = new EmailSender();
                    $emailSender->sendWelcomeEmail($name, $email, $volunteer_id);
                    
                    $volunteers_added++;
                } catch (PDOException $e) {
                    $volunteers_failed++;
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                }
            }
            
            // Build success message
            $bulk_message = "<div class='alert alert-info'><i class='fas fa-info-circle'></i> <strong>Bulk Import Complete</strong><br>";
            $bulk_message .= "✅ Volunteers Added: <strong>$volunteers_added</strong><br>";
            if ($volunteers_failed > 0) {
                $bulk_message .= "❌ Failed: <strong>$volunteers_failed</strong>";
            }
            $bulk_message .= "</div>";
            
            if (!empty($errors)) {
                $bulk_message .= "<div class='alert alert-warning'><strong>Issues Found:</strong><ul>";
                foreach (array_slice($errors, 0, 10) as $error) {
                    $bulk_message .= "<li>$error</li>";
                }
                if (count($errors) > 10) {
                    $bulk_message .= "<li>... and " . (count($errors) - 10) . " more issues</li>";
                }
                $bulk_message .= "</ul></div>";
            }
        }
    }
}

// Handle volunteer registration by admin
$registration_message = "";
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['register_volunteer'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department']);
    $year = $_POST['year'];
    $password = md5($_POST['password']);
    $profile_image = null;

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registration_message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Please enter a valid email address.</div>";
    } else {
        // Check if email already exists
        $check_email = $pdo->prepare("SELECT id FROM volunteers WHERE email = ?");
        $check_email->execute([$email]);
        
        if ($check_email->fetch()) {
            $registration_message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Email already registered. Please use a different email.</div>";
        } else {
            // Handle profile image upload
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../assets/profile_images/';
                
                // Create directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                $fileExtension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                
                if (in_array($fileExtension, $allowedTypes)) {
                    if ($_FILES['profile_image']['size'] <= 2 * 1024 * 1024) { // 2MB
                        $fileName = 'profile_' . time() . '_' . uniqid() . '.' . $fileExtension;
                        $targetPath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
                            $profile_image = $fileName;
                        }
                    } else {
                        $registration_message = "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> Profile image too large. Maximum size is 2MB.</div>";
                    }
                } else {
                    $registration_message = "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> Invalid image format. Please use JPG, PNG, or GIF.</div>";
                }
            }
            
            // Generate unique Volunteer ID
            $volunteer_id = "V" . strtoupper(bin2hex(random_bytes(3)));

            try {
                $stmt = $pdo->prepare("INSERT INTO volunteers (volunteer_id, name, email, phone, department, year, password, profile_image, status) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                $stmt->execute([$volunteer_id, $name, $email, $phone, $department, $year, $password, $profile_image]);
                
                // Send welcome email
                require_once "../includes/EmailSender.php";
                $emailSender = new EmailSender();
                $emailResult = $emailSender->sendWelcomeEmail($name, $email, $volunteer_id);
                
                $registration_message = "<div class='alert alert-success'>
                    <h5><i class='fas fa-check-circle'></i> Registration Successful!</h5>
                    <p class='mb-1'><strong>Volunteer ID:</strong> <code>$volunteer_id</code></p>
                    <p class='mb-0'><strong>Name:</strong> $name</p>";
                
                if ($emailResult['success']) {
                    $registration_message .= "<p class='mt-2'><i class='fas fa-envelope text-success'></i> Welcome email sent to $email</p>";
                } else {
                    $registration_message .= "<p class='mt-2 text-warning'><i class='fas fa-exclamation-triangle'></i> Registration successful, but email notification failed.</p>";
                }
                
                $registration_message .= "</div>";
                
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    // Duplicate volunteer_id, try again
                    $volunteer_id = "V" . strtoupper(bin2hex(random_bytes(3)));
                    $stmt->execute([$volunteer_id, $name, $email, $phone, $department, $year, $password, $profile_image]);
                    
                    // Send welcome email for retry
                    require_once "../includes/EmailSender.php";
                    $emailSender = new EmailSender();
                    $emailResult = $emailSender->sendWelcomeEmail($name, $email, $volunteer_id);
                    
                    $registration_message = "<div class='alert alert-success'>
                        <h5><i class='fas fa-check-circle'></i> Registration Successful!</h5>
                        <p class='mb-1'><strong>Volunteer ID:</strong> <code>$volunteer_id</code></p>
                        <p class='mb-0'><strong>Name:</strong> $name</p>";
                    
                    if ($emailResult['success']) {
                        $registration_message .= "<p class='mt-2'><i class='fas fa-envelope text-success'></i> Welcome email sent to $email</p>";
                    } else {
                        $registration_message .= "<p class='mt-2 text-warning'><i class='fas fa-exclamation-triangle'></i> Registration successful, but email notification failed.</p>";
                    }
                    
                    $registration_message .= "</div>";
                } else {
                    $registration_message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Error: " . $e->getMessage() . "</div>";
                }
            }
        }
    }
}

// Search
$search = $_GET['search'] ?? '';
$where = "";
$params = [];

if (!empty($search)) {
    $where = "WHERE name LIKE ? OR volunteer_id LIKE ? OR email LIKE ? OR department LIKE ?";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

// Fetch data
$stmt = $pdo->prepare("SELECT * FROM volunteers $where ORDER BY registered_at DESC");
$stmt->execute($params);
$volunteers = $stmt->fetchAll();

// ---------- EXPORT FEATURE ----------
if (isset($_GET['export'])) {
    $format = $_GET['export'];

    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=volunteers.csv');
        $out = fopen("php://output", "w");
        fputcsv($out, ['ID', 'Name', 'Email', 'Phone', 'Department', 'Year', 'Registered At']);
        foreach ($volunteers as $v) {
            fputcsv($out, [
                $v['volunteer_id'],
                $v['name'],
                $v['email'],
                $v['phone'],
                $v['department'],
                $v['year'],
                $v['registered_at']
            ]);
        }
        fclose($out);
        exit;
    }

    if ($format === 'excel') {
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=volunteers.xls");
        echo "ID\tName\tEmail\tPhone\tDepartment\tYear\tRegistered At\n";
        foreach ($volunteers as $v) {
            echo "{$v['volunteer_id']}\t{$v['name']}\t{$v['email']}\t{$v['phone']}\t{$v['department']}\t{$v['year']}\t{$v['registered_at']}\n";
        }
        exit;
    }

    if ($format === 'json') {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename=volunteers.json');
        echo json_encode($volunteers);
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Volunteers List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
    <a href="gallery_upload.php">📸 Upload Gallery</a>
    <a href="gallery_view.php">🖼️ View Gallery</a>
    <a href="manage_notifications.php">📢 Notifications</a>
    <a href="view_feedback.php">💬 Feedback</a>
    <a href="../logout.php">🚪 Logout</a>
</div>

<h2>📋 Volunteers List</h2>

<?php if (!empty($registration_message)): ?>
    <?= $registration_message ?>
<?php endif; ?>

<?php if (!empty($bulk_message)): ?>
    <?= $bulk_message ?>
<?php endif; ?>

<!-- Search -->
<form method="get" class="d-flex mb-3">
    <input type="text" name="search" class="form-control me-2" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-primary">Search</button>
    <?php if (!empty($search)): ?>
        <a href="view_volunteers.php" class="btn btn-secondary ms-2">Clear</a>
    <?php endif; ?>
</form>

<!-- Export Buttons & Register Button -->
<div class="mb-3">
    <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#registerVolunteerModal">➕ Register New Volunteer</button>
    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#importExcelModal">📊 Import from Excel</button>
    <a href="?export=csv&search=<?= urlencode($search) ?>" class="btn btn-success btn-sm">Download CSV</a>
    <a href="?export=excel&search=<?= urlencode($search) ?>" class="btn btn-warning btn-sm">Download Excel</a>
    <a href="?export=json&search=<?= urlencode($search) ?>" class="btn btn-dark btn-sm">Download JSON</a>
</div>

<!-- Table -->
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Volunteer ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Department</th>
                <th>Year</th>
                <th>Registered At</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($volunteers): ?>
            <?php foreach ($volunteers as $v): ?>
            <?php $status = $v['status'] ?? 'active'; ?>
            <tr>
                <td><?= htmlspecialchars($v['volunteer_id']) ?></td>
                <td><?= htmlspecialchars($v['name']) ?></td>
                <td><?= htmlspecialchars($v['email']) ?></td>
                <td><?= htmlspecialchars($v['phone']) ?></td>
                <td><?= htmlspecialchars($v['department']) ?></td>
                <td><?= htmlspecialchars($v['year']) ?></td>
                <td><?= date('d M Y, h:i A', strtotime($v['registered_at'])) ?></td>
                <td>
                    <?php if ($status === 'active'): ?>
                        <span class="badge bg-success">Active</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Inactive</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($status === 'active'): ?>
                        <a href="?deactivate=<?= urlencode($v['volunteer_id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to deactivate this volunteer?');">Deactivate</a>
                    <?php else: ?>
                        <a href="?activate=<?= urlencode($v['volunteer_id']) ?>" class="btn btn-sm btn-success">Reactivate</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="9" class="text-center">No volunteers found</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>

<!-- Register Volunteer Modal -->
<div class="modal fade" id="registerVolunteerModal" tabindex="-1" aria-labelledby="registerVolunteerLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="registerVolunteerLabel">Register New Volunteer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Email Address *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" pattern="[0-9]{10}">
                            <div class="form-text">10-digit phone number (optional)</div>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Department *</label>
                            <select name="department" class="form-select" required>
                                <option value="">Select Department</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Electronics">Electronics</option>
                                <option value="Mechanical">Mechanical</option>
                                <option value="Civil">Civil</option>
                                <option value="Electrical">Electrical</option>
                                <option value="Science">Science</option>
                                <option value="Arts">Arts</option>
                                <option value="Commerce">Commerce</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Year *</label>
                            <select name="year" class="form-select" required>
                                <option value="">Select Year</option>
                                <option value="FY">First Year (FY)</option>
                                <option value="SY">Second Year (SY)</option>
                                <option value="TY">Third Year (TY)</option>
                                <option value="Final">Final Year</option>
                            </select>
                        </div>
                        
                        <!-- Profile Image Upload -->
                        <div class="col-md-6">
                            <label class="form-label">Profile Picture</label>
                            <input type="file" name="profile_image" class="form-control" accept="image/*">
                            <div class="form-text">Upload a clear photo (JPG, PNG, GIF, Max: 2MB)</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Password *</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                            <div class="form-text">Minimum 6 characters</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password *</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="register_volunteer" class="btn btn-primary">Register Volunteer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Password confirmation validation
    const registerForm = document.querySelector('#registerVolunteerModal form');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const password = registerForm.querySelector('input[name="password"]');
            const confirmPassword = registerForm.querySelector('input[name="confirm_password"]');
            
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match!');
                confirmPassword.focus();
            }
        });
    }
</script>

<!-- Import from Excel Modal -->
<div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="importExcelLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importExcelLabel">📊 Bulk Import Volunteers from Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>📋 File Format Instructions:</strong>
                        <p class="mb-1">Your Excel/CSV file should have columns in this order:</p>
                        <ol class="mb-2">
                            <li><strong>Name</strong> - Full name of volunteer (required)</li>
                            <li><strong>Email</strong> - Email address (required)</li>
                            <li><strong>Phone</strong> - Phone number (optional)</li>
                            <li><strong>Department</strong> - Computer Science, Electronics, etc. (optional)</li>
                            <li><strong>Year</strong> - FY, SY, TY, Final (optional)</li>
                            <li><strong>Password</strong> - Login password (required, min 6 characters)</li>
                        </ol>
                        <p class="mb-0">
                            <a href="#" onclick="downloadTemplate()" class="btn btn-sm btn-success">📥 Download Template File</a>
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="excel_file" class="form-label">Select Excel/CSV File *</label>
                        <input type="file" name="excel_file" id="excel_file" class="form-control" accept=".csv,.xls,.xlsx" required>
                        <div class="form-text">Supported formats: CSV, XLS, XLSX (Max 5MB)</div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <strong>⚠️ Note:</strong> 
                        <ul class="mb-0">
                            <li>First row is treated as header and will be skipped</li>
                            <li>Duplicate emails will be skipped</li>
                            <li>Missing required fields will be skipped with error message</li>
                            <li>Welcome emails will be sent automatically to each volunteer</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="upload_excel" class="btn btn-primary">📤 Import Volunteers</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function downloadTemplate() {
        const csv = "Name,Email,Phone,Department,Year,Password\n" +
                    "John Doe,john@example.com,9876543210,Computer Science,FY,password123\n" +
                    "Jane Smith,jane@example.com,9876543211,Electronics,SY,password456\n" +
                    "Bob Wilson,bob@example.com,9876543212,Mechanical,TY,password789";
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'volunteer_template.csv';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    }
</script>

</body>
</html>
