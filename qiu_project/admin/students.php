<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireRole('admin');
$user = getCurrentUser();

// Get database connection
$conn = getDBConnection();

// Handle delete
if (isset($_POST['delete_student'])) {
    $user_id = (int)$_POST['user_id'];
    
    // Delete from students table first, then users
    $conn->query("DELETE FROM students WHERE user_id = $user_id");
    $conn->query("DELETE FROM users WHERE id = $user_id");
    
    alertRedirect('Student deleted successfully!', 'students.php');
}

// Get filter
$department_filter = isset($_GET['department']) ? sanitize($_GET['department']) : '';
$program_filter = isset($_GET['program']) ? sanitize($_GET['program']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query - Fixed: using department_id with JOIN
$sql = "SELECT u.*, s.student_code, s.department_id, s.program, s.gpa, s.semester, s.enrollment_date, d.name as department 
        FROM users u 
        JOIN students s ON u.id = s.user_id 
        LEFT JOIN departments d ON s.department_id = d.id
        WHERE u.role = 'student'";

if ($department_filter) {
    $sql .= " AND d.name = '" . $conn->real_escape_string($department_filter) . "'";
}
if ($program_filter) {
    $sql .= " AND s.program = '" . $conn->real_escape_string($program_filter) . "'";
}
if ($search) {
    $sql .= " AND (u.full_name LIKE '%" . $conn->real_escape_string($search) . "%' 
              OR u.email LIKE '%" . $conn->real_escape_string($search) . "%'
              OR s.student_code LIKE '%" . $conn->real_escape_string($search) . "%')";
}
$sql .= " ORDER BY s.enrollment_date DESC";

$students = $conn->query($sql);
$departments = $conn->query("SELECT DISTINCT name FROM departments ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - QIU Admin</title>
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
                <a href="students.php" class="active"><i class="fas fa-user-graduate"></i> Students</a>
                <a href="departments.php"><i class="fas fa-building"></i> Departments</a>
                <a href="leave_requests.php"><i class="fas fa-calendar-minus"></i> Leave Requests</a>
                <a href="job_positions.php"><i class="fas fa-briefcase"></i> Job Positions</a>
                <a href="applications.php"><i class="fas fa-file-alt"></i> Applications</a>
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1><i class="fas fa-user-graduate"></i> Students Management</h1>
                <a href="add_user.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Student</a>
            </header>
            
            <!-- Filters -->
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <select name="department">
                                    <option value="">All Departments</option>
                                    <?php while ($dept = $departments->fetch_assoc()): ?>
                                        <option value="<?php echo $dept['name']; ?>" <?php echo $department_filter === $dept['name'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <select name="program">
                                    <option value="">All Programs</option>
                                    <option value="Undergraduate" <?php echo $program_filter === 'Undergraduate' ? 'selected' : ''; ?>>Undergraduate</option>
                                    <option value="Graduate" <?php echo $program_filter === 'Graduate' ? 'selected' : ''; ?>>Graduate</option>
                                    <option value="PhD" <?php echo $program_filter === 'PhD' ? 'selected' : ''; ?>>PhD</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <input type="text" name="search" placeholder="Search by name, email, or code..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                                <a href="students.php" class="btn btn-secondary">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Students Table -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> All Students (<?php echo $students->num_rows; ?>)</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Student Code</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Program</th>
                                    <th>GPA</th>
                                    <th>Semester</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($students->num_rows > 0): ?>
                                    <?php while ($std = $students->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <img src="../uploads/photos/<?php echo $std['photo'] ?: 'default.png'; ?>" 
                                                 alt="Photo" class="table-avatar">
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($std['student_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($std['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($std['email']); ?></td>
                                        <td><?php echo htmlspecialchars($std['department'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($std['program']); ?></td>
                                        <td><strong><?php echo number_format($std['gpa'], 2); ?></strong></td>
                                        <td><?php echo $std['semester']; ?></td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo $std['status'] === 'active' ? 'success' : 
                                                    ($std['status'] === 'on_leave' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($std['status']); ?>
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <a href="edit_user.php?id=<?php echo $std['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this student?');">
                                                <input type="hidden" name="user_id" value="<?php echo $std['id']; ?>">
                                                <button type="submit" name="delete_student" class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center">No students found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>