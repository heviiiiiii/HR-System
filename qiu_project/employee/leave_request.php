<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireRole('employee');

$conn = getDBConnection(); // ✅ FIX
$user = getCurrentUser();

// Optional helper
$employee = function_exists('getEmployeeData') ? getEmployeeData($user['id']) : null;

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $leave_type = sanitize($_POST['leave_type'] ?? '');
    $start_date = sanitize($_POST['start_date'] ?? '');
    $end_date   = sanitize($_POST['end_date'] ?? '');
    $reason     = sanitize($_POST['reason'] ?? '');

    // Allowed leave types (MATCH DATABASE ENUM)
    $allowed_types = ['annual', 'sick', 'maternity', 'emergency', 'other'];

    // ===============================
    // Validation
    // ===============================
    if (!in_array($leave_type, $allowed_types, true)) {
        $error = "Invalid leave type selected.";
    } elseif (!$start_date || !$end_date || !$reason) {
        $error = "All required fields must be filled.";
    } elseif (strtotime($end_date) < strtotime($start_date)) {
        $error = "End date must be after start date.";
    } elseif (strtotime($start_date) < strtotime(date('Y-m-d'))) {
        $error = "Start date cannot be in the past.";
    } else {

        // ===============================
        // Handle document upload
        // ===============================
        $document_path = null;

        if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
            $uploaded = uploadFile(
                $_FILES['document'],
                '../uploads/documents/',
                ['pdf', 'jpg', 'jpeg', 'png']
            );

            if ($uploaded['success']) {
                $document_path = $uploaded['filename'];
            } else {
                $error = $uploaded['error'];
            }
        }

        // ===============================
        // Insert request
        // ===============================
        if (empty($error)) {
            $stmt = $conn->prepare("
                INSERT INTO leave_requests 
                (user_id, leave_type, start_date, end_date, reason, document_path)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "isssss",
                $user['id'],
                $leave_type,
                $start_date,
                $end_date,
                $reason,
                $document_path
            );

            if ($stmt->execute()) {
                $success = "Leave request submitted successfully!";
            } else {
                $error = "Error submitting leave request.";
            }

            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Leave - QIU Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="dashboard-container">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/images/qiu.png" alt="QIU Logo" class="logo">
            <h2>QIU Portal</h2>
        </div>

        <div class="profile-box">
            <img src="../uploads/photos/<?php echo $user['photo'] ?: 'default.png'; ?>" class="profile-img">
            <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
            <p>Employee</p>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="leave_request.php" class="active"><i class="fas fa-calendar-minus"></i> Leave Requests</a>
            <a href="my_leaves.php"><i class="fas fa-history"></i> My Leaves</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="content-header">
            <h1><i class="fas fa-calendar-minus"></i> Request Leave</h1>
        </header>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-plus"></i> New Leave Request</h3>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">

                    <div class="form-group">
                        <label>Leave Type *</label>
                        <select name="leave_type" required>
                            <option value="">Select Leave Type</option>
                            <option value="annual">Annual Leave</option>
                            <option value="sick">Sick Leave</option>
                            <option value="maternity">Maternity Leave</option>
                            <option value="emergency">Emergency Leave</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Start Date *</label>
                            <input type="date" name="start_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label>End Date *</label>
                            <input type="date" name="end_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Reason *</label>
                        <textarea name="reason" rows="4" required placeholder="Please provide the reason for your leave request..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Supporting Document (Optional)</label>
                        <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png">
                        <small>PDF, JPG, PNG — Max 5MB</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Submit Request
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>

                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
