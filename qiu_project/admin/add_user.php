<?php
/**
 * Admin - Add New User
 */
require_once '../config.php';
require_once '../includes/auth.php';

requireRole(['admin', 'hr']);

$conn = getDBConnection();
$user = getCurrentUser();
$departments = getDepartments();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = sanitize($_POST['role'] ?? 'employee');
    $status = sanitize($_POST['status'] ?? 'active');
    $departmentId = intval($_POST['department_id'] ?? 0);
    
    // Role-specific fields
    $position = sanitize($_POST['position'] ?? '');
    $salary = floatval($_POST['salary'] ?? 0);
    $program = sanitize($_POST['program'] ?? '');
    $gpa = floatval($_POST['gpa'] ?? 0);
    
    if (empty($fullName) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email already exists';
        } else {
            // Handle photo upload
            $photoName = 'default.jpg';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $upload = uploadFile($_FILES['photo'], '../uploads/photos', ['jpg', 'jpeg', 'png']);
                if ($upload['success']) {
                    $photoName = $upload['filename'];
                }
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (email, password, role, full_name, phone, photo, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $email, $hashedPassword, $role, $fullName, $phone, $photoName, $status);
            
            if ($stmt->execute()) {
                $userId = $conn->insert_id;
                
                // Insert role-specific data
                if ($role === 'employee' || $role === 'hr') {
                    $employeeCode = generateEmployeeCode();
                    $stmt2 = $conn->prepare("INSERT INTO employees (user_id, employee_code, department_id, position, salary, hire_date) VALUES (?, ?, ?, ?, ?, CURDATE())");
                    $stmt2->bind_param("isisi", $userId, $employeeCode, $departmentId, $position, $salary);
                    $stmt2->execute();
                } elseif ($role === 'student') {
                    $studentCode = generateStudentCode();
                    $stmt2 = $conn->prepare("INSERT INTO students (user_id, student_code, department_id, program, gpa, enrollment_date) VALUES (?, ?, ?, ?, ?, CURDATE())");
                    $stmt2->bind_param("isisd", $userId, $studentCode, $departmentId, $program, $gpa);
                    $stmt2->execute();
                }
                
                alertRedirect('User added successfully!', 'users.php');
            } else {
                $error = 'Failed to add user';
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
    <title>Add User | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .role-fields { display: none; }
        .role-fields.active { display: block; }
    </style>
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
                <h1>Add New User</h1>
            </div>
            
            <div class="content">
                <a href="users.php" style="display: inline-block; margin-bottom: 20px; color: #1a5dc9;">
                    <i class="fas fa-arrow-left"></i> Back to Users
                </a>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="form-card">
                    <h3>User Information</h3>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="full_name">Full Name *</label>
                                <input type="text" id="full_name" name="full_name" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="tel" id="phone" name="phone">
                            </div>
                            <div class="form-group">
                                <label for="password">Password *</label>
                                <input type="password" id="password" name="password" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="role">Role *</label>
                                <select id="role" name="role" required onchange="toggleRoleFields()">
                                    <option value="employee">Employee</option>
                                    <option value="hr">HR</option>
                                    <option value="student">Student</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="department_id">Department</label>
                            <select id="department_id" name="department_id">
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Employee Fields -->
                        <div id="employee-fields" class="role-fields active">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="position">Position</label>
                                    <input type="text" id="position" name="position" placeholder="e.g., Lecturer">
                                </div>
                                <div class="form-group">
                                    <label for="salary">Salary ($)</label>
                                    <input type="number" id="salary" name="salary" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Student Fields -->
                        <div id="student-fields" class="role-fields">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="program">Program</label>
                                    <select id="program" name="program">
                                        <option value="">-- Select --</option>
                                        <option value="Undergraduate">Undergraduate</option>
                                        <option value="Graduate">Graduate</option>
                                        <option value="PhD">PhD</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="gpa">GPA</label>
                                    <input type="number" id="gpa" name="gpa" step="0.01" min="0" max="4">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="photo">Profile Photo</label>
                            <input type="file" id="photo" name="photo" accept="image/*">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Add User
                            </button>
                            <a href="users.php" class="btn" style="background: #6b7280; color: white;">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function toggleRoleFields() {
            const role = document.getElementById('role').value;
            document.getElementById('employee-fields').classList.remove('active');
            document.getElementById('student-fields').classList.remove('active');
            
            if (role === 'employee' || role === 'hr') {
                document.getElementById('employee-fields').classList.add('active');
            } else if (role === 'student') {
                document.getElementById('student-fields').classList.add('active');
            }
        }
    </script>
</body>
</html>
