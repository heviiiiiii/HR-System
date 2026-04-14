<?php
/**
 * Admin - Edit User
 */
require_once '../config.php';
require_once '../includes/auth.php';

requireRole(['admin', 'hr']);

$conn = getDBConnection();
$user = getCurrentUser();
$departments = getDepartments();

$userId = intval($_GET['id'] ?? 0);

if (!$userId) {
    redirect('users.php');
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$editUser = $stmt->get_result()->fetch_assoc();

if (!$editUser) {
    alertRedirect('User not found', 'users.php');
}

// Get role-specific data
$employeeData = null;
$studentData = null;

if ($editUser['role'] === 'employee' || $editUser['role'] === 'hr') {
    $employeeData = getEmployeeData($userId);
}
if ($editUser['role'] === 'student') {
    $studentData = getStudentData($userId);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $status = sanitize($_POST['status'] ?? 'active');
    $departmentId = intval($_POST['department_id'] ?? 0);
    $newPassword = $_POST['new_password'] ?? '';
    
    // Role-specific fields
    $position = sanitize($_POST['position'] ?? '');
    $salary = floatval($_POST['salary'] ?? 0);
    $program = sanitize($_POST['program'] ?? '');
    $gpa = floatval($_POST['gpa'] ?? 0);
    
    if (empty($fullName) || empty($email)) {
        $error = 'Please fill in all required fields';
    } else {
        // Check if email exists for other users
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $userId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email already used by another user';
        } else {
            // Handle photo upload
            $photoName = $editUser['photo'];
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $upload = uploadFile($_FILES['photo'], '../uploads/photos', ['jpg', 'jpeg', 'png']);
                if ($upload['success']) {
                    $photoName = $upload['filename'];
                }
            }
            
            // Update user
            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, photo = ?, status = ?, password = ? WHERE id = ?");
                $stmt->bind_param("ssssssi", $fullName, $email, $phone, $photoName, $status, $hashedPassword, $userId);
            } else {
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, photo = ?, status = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $fullName, $email, $phone, $photoName, $status, $userId);
            }
            
            if ($stmt->execute()) {
                // Update role-specific data
                if ($editUser['role'] === 'employee' || $editUser['role'] === 'hr') {
                    $stmt2 = $conn->prepare("UPDATE employees SET department_id = ?, position = ?, salary = ? WHERE user_id = ?");
                    $stmt2->bind_param("isdi", $departmentId, $position, $salary, $userId);
                    $stmt2->execute();
                } elseif ($editUser['role'] === 'student') {
                    $stmt2 = $conn->prepare("UPDATE students SET department_id = ?, program = ?, gpa = ? WHERE user_id = ?");
                    $stmt2->bind_param("isdi", $departmentId, $program, $gpa, $userId);
                    $stmt2->execute();
                }
                
                alertRedirect('User updated successfully!', 'users.php');
            } else {
                $error = 'Failed to update user';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">QAIWAN INTERNATIONAL<br>UNIVERSITY</div>
            
            <div class="profile-box">
                <img src="../<?php echo getPhotoPath($user['photo']); ?>" alt="Profile">
                <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                <span><?php echo ucfirst($user['role']); ?></span>
            </div>
            
            <ul class="nav-menu">
                <li><a href="dashboard.php"><i class="fas fa-chart-pie"></i> <span>Dashboard</span></a></li>
                <li><a href="users.php" class="active"><i class="fas fa-users"></i> <span>All Users</span></a></li>
                <li><a href="employees.php"><i class="fas fa-user-tie"></i> <span>Employees</span></a></li>
                <li><a href="students.php"><i class="fas fa-graduation-cap"></i> <span>Students</span></a></li>
                <li><a href="departments.php"><i class="fas fa-building"></i> <span>Departments</span></a></li>
                <li><a href="leave_requests.php"><i class="fas fa-calendar-check"></i> <span>Leave Requests</span></a></li>
                <li><a href="job_positions.php"><i class="fas fa-briefcase"></i> <span>Job Positions</span></a></li>
                <li><a href="applications.php"><i class="fas fa-file-alt"></i> <span>Applications</span></a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
                <li class="logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="topbar">
                <h1>Edit User</h1>
            </div>
            
            <div class="content">
                <a href="users.php" style="display: inline-block; margin-bottom: 20px; color: #1a5dc9;">
                    <i class="fas fa-arrow-left"></i> Back to Users
                </a>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="form-card">
                    <h3>Edit User: <?php echo htmlspecialchars($editUser['full_name']); ?></h3>
                    
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding: 15px; background: #f9fafb; border-radius: 8px;">
                        <img src="../<?php echo getPhotoPath($editUser['photo']); ?>" alt="Current Photo" 
                             style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;">
                        <div>
                            <strong>Role:</strong> <?php echo ucfirst($editUser['role']); ?><br>
                            <?php if ($employeeData): ?>
                                <strong>Code:</strong> <?php echo $employeeData['employee_code']; ?>
                            <?php elseif ($studentData): ?>
                                <strong>Code:</strong> <?php echo $studentData['student_code']; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="full_name">Full Name *</label>
                                <input type="text" id="full_name" name="full_name" required
                                       value="<?php echo htmlspecialchars($editUser['full_name']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" required
                                       value="<?php echo htmlspecialchars($editUser['email']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="tel" id="phone" name="phone"
                                       value="<?php echo htmlspecialchars($editUser['phone'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="active" <?php echo $editUser['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $editUser['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="on_leave" <?php echo $editUser['status'] === 'on_leave' ? 'selected' : ''; ?>>On Leave</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="department_id">Department</label>
                            <select id="department_id" name="department_id">
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" 
                                        <?php echo ($employeeData && $employeeData['department_id'] == $dept['id']) || 
                                                   ($studentData && $studentData['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <?php if ($editUser['role'] === 'employee' || $editUser['role'] === 'hr'): ?>
                        <!-- Employee Fields -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="position">Position</label>
                                <input type="text" id="position" name="position"
                                       value="<?php echo htmlspecialchars($employeeData['position'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="salary">Salary ($)</label>
                                <input type="number" id="salary" name="salary" step="0.01" min="0"
                                       value="<?php echo $employeeData['salary'] ?? 0; ?>">
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($editUser['role'] === 'student'): ?>
                        <!-- Student Fields -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="program">Program</label>
                                <select id="program" name="program">
                                    <option value="">-- Select --</option>
                                    <option value="Undergraduate" <?php echo ($studentData['program'] ?? '') === 'Undergraduate' ? 'selected' : ''; ?>>Undergraduate</option>
                                    <option value="Graduate" <?php echo ($studentData['program'] ?? '') === 'Graduate' ? 'selected' : ''; ?>>Graduate</option>
                                    <option value="PhD" <?php echo ($studentData['program'] ?? '') === 'PhD' ? 'selected' : ''; ?>>PhD</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="gpa">GPA</label>
                                <input type="number" id="gpa" name="gpa" step="0.01" min="0" max="4"
                                       value="<?php echo $studentData['gpa'] ?? 0; ?>">
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="photo">Change Photo</label>
                            <input type="file" id="photo" name="photo" accept="image/*">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password (leave empty to keep current)</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Enter new password">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update User
                            </button>
                            <a href="users.php" class="btn" style="background: #6b7280; color: white;">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
