<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireRole('admin');
$user = getCurrentUser();

// Get database connection
$conn = getDBConnection();

$success = '';
$error = '';

// Handle profile update
if (isset($_POST['update_profile'])) {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    
    // Check if email exists for another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user['id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $error = "Email already exists for another user.";
    } else {
        // Handle photo upload
        $photo = $user['photo'];
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploaded = uploadFile($_FILES['photo'], '../uploads/photos/', ['jpg', 'jpeg', 'png']);
            if ($uploaded['success']) {
                // Delete old photo if exists
                if ($user['photo'] && file_exists('../uploads/photos/' . $user['photo'])) {
                    unlink('../uploads/photos/' . $user['photo']);
                }
                $photo = $uploaded['filename'];
            } else {
                $error = $uploaded['error'];
            }
        }
        
        if (empty($error)) {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, photo = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $full_name, $email, $phone, $photo, $user['id']);
            
            if ($stmt->execute()) {
                $success = "Profile updated successfully!";
                // Refresh user data
                $user = getCurrentUser();
            } else {
                $error = "Error updating profile.";
            }
        }
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get current password hash from database
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    
    if (!password_verify($current_password, $user_data['password'])) {
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
        } else {
            $error = "Error changing password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - QIU Admin</title>
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
                <p>Administrator</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="users.php"><i class="fas fa-users"></i> All Users</a>
                <a href="employees.php"><i class="fas fa-user-tie"></i> Employees</a>
                <a href="students.php"><i class="fas fa-user-graduate"></i> Students</a>
                <a href="departments.php"><i class="fas fa-building"></i> Departments</a>
                <a href="leave_requests.php"><i class="fas fa-calendar-minus"></i> Leave Requests</a>
                <a href="job_positions.php"><i class="fas fa-briefcase"></i> Job Positions</a>
                <a href="applications.php"><i class="fas fa-file-alt"></i> Applications</a>
                <a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1><i class="fas fa-cog"></i> Account Settings</h1>
            </header>
            
            <?php if ($success): ?>
                <div class="alert alert-success" style="margin: 0 30px;"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger" style="margin: 0 30px;"><?php echo $error; ?></div>
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
                                    <input type="file" name="photo" id="photo" accept="image/*" style="display: none;" onchange="previewImage(this)">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Email Address *</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
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