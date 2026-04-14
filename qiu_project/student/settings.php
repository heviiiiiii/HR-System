<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireRole('student');

$conn = getDBConnection(); // ✅ FIX: create connection first
$user = getCurrentUser();

// Student data (if you have helper)
$student = null;
if (function_exists('getStudentData')) {
    $student = getStudentData($user['id']);
}

$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    $full_name = sanitize($_POST['full_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');

    if ($full_name === '') {
        $error = "Full name is required.";
    } else {
        // Handle photo upload
        $photo = $user['photo'];

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {

            // Optional file size check: 5MB
            if ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
                $error = "Photo too large. Max 5MB.";
            } else {
                if (function_exists('uploadFile')) {
                    $uploaded = uploadFile($_FILES['photo'], '../uploads/photos/', ['jpg', 'jpeg', 'png']);

                    if (!empty($uploaded['success'])) {
                        // delete old photo (avoid deleting default)
                        if (!empty($user['photo']) && $user['photo'] !== 'default.png' && $user['photo'] !== 'default.jpg') {
                            $oldPath = '../uploads/photos/' . $user['photo'];
                            if (file_exists($oldPath)) {
                                @unlink($oldPath);
                            }
                        }
                        $photo = $uploaded['filename'];
                    } else {
                        $error = $uploaded['error'] ?? "Upload failed.";
                    }
                } else {
                    $error = "uploadFile() function not found. Please add it in helpers.";
                }
            }
        }

        if (empty($error)) {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ?, photo = ? WHERE id = ?");
            $stmt->bind_param("sssi", $full_name, $phone, $photo, $user['id']);

            if ($stmt->execute()) {
                $success = "Profile updated successfully!";
                $user = getCurrentUser(); // refresh
            } else {
                $error = "Error updating profile: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!password_verify($current_password, $user['password'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $user['id']);

        if ($stmt->execute()) {
            $success = "Password changed successfully!";
            $user = getCurrentUser(); // refresh (optional)
        } else {
            $error = "Error changing password: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Department display (safe)
$deptDisplay = 'N/A';
if (is_array($student)) {
    if (!empty($student['department'])) {
        $deptDisplay = $student['department']; // if your helper already joins departments.name
    } elseif (!empty($student['department_name'])) {
        $deptDisplay = $student['department_name'];
    } elseif (isset($student['department_id'])) {
        $deptDisplay = 'Department ID: ' . (int)$student['department_id'];
    }
}

$studentCode = is_array($student) && !empty($student['student_code']) ? $student['student_code'] : 'N/A';
$program = is_array($student) && !empty($student['program']) ? $student['program'] : 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - QIU Portal</title>
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
            <img src="../uploads/photos/<?php echo $user['photo'] ?: 'default.png'; ?>" alt="Profile" class="profile-img">
            <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
            <p>Student</p>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="courses.php"><i class="fas fa-book"></i> My Courses</a>
            <a href="assignments.php"><i class="fas fa-tasks"></i> Assignments</a>
            <a href="grades.php"><i class="fas fa-chart-line"></i> Grades</a>
            <a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="content-header">
            <h1><i class="fas fa-cog"></i> Account Settings</h1>
        </header>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="settings-grid">
            <!-- Profile Settings -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user"></i> Profile Settings</h3>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="profile-photo-section">
                            <img src="../uploads/photos/<?php echo $user['photo'] ?: 'default.png'; ?>"
                                 alt="Profile Photo" class="settings-photo" id="preview-photo">

                            <div class="photo-upload">
                                <label for="photo" class="btn btn-secondary">
                                    <i class="fas fa-camera"></i> Change Photo
                                </label>
                                <input type="file" name="photo" id="photo" accept="image/*"
                                       style="display:none;" onchange="previewImage(this)">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Student Code</label>
                            <input type="text" value="<?php echo htmlspecialchars($studentCode); ?>" readonly class="readonly">
                        </div>

                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly class="readonly">
                            <small>Contact admin to change email</small>
                        </div>

                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>Department</label>
                            <input type="text" value="<?php echo htmlspecialchars($deptDisplay); ?>" readonly class="readonly">
                        </div>

                        <div class="form-group">
                            <label>Program</label>
                            <input type="text" value="<?php echo htmlspecialchars($program); ?>" readonly class="readonly">
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>

            <!-- Password Settings -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-lock"></i> Change Password</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label>Current Password *</label>
                            <input type="password" name="current_password" required>
                        </div>

                        <div class="form-group">
                            <label>New Password *</label>
                            <input type="password" name="new_password" required minlength="6">
                            <small>Minimum 6 characters</small>
                        </div>

                        <div class="form-group">
                            <label>Confirm New Password *</label>
                            <input type="password" name="confirm_password" required>
                        </div>

                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview-photo').src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
</body>
</html>
