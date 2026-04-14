<?php
require_once '../config.php';
require_once '../includes/auth.php';
requireRole('admin');
$user = getCurrentUser();
$conn = getDBConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_department'])) {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        
        $stmt = $conn->prepare("INSERT INTO departments (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        
        if ($stmt->execute()) {
            alertRedirect('Department added successfully!', 'departments.php');
        } else {
            $error = "Error adding department.";
        }
    }
    
    if (isset($_POST['delete_department'])) {
        $id = (int)$_POST['department_id'];
        
        // Check if department has users
        $check = $conn->query("SELECT COUNT(*) as count FROM employees WHERE department = (SELECT name FROM departments WHERE id = $id)");
        $row = $check->fetch_assoc();
        
        if ($row['count'] > 0) {
            $error = "Cannot delete department with assigned employees.";
        } else {
            $stmt = $conn->prepare("DELETE FROM departments WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                alertRedirect('Department deleted successfully!', 'departments.php');
            }
        }
    }
}

// Get all departments with counts
$departments = $conn->query("
    SELECT d.*, 
           (SELECT COUNT(*) FROM employees e WHERE e.department_id = d.id) as employee_count,
           (SELECT COUNT(*) FROM students s WHERE s.department_id = d.id) as student_count
    FROM departments d 
    ORDER BY d.name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments - QIU Admin</title>
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
                <a href="departments.php" class="active"><i class="fas fa-building"></i> Departments</a>
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
                <h1><i class="fas fa-building"></i> Departments Management</h1>
            </header>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Add Department Form -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-plus"></i> Add New Department</h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="form-inline-group">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Department Name *</label>
                                <input type="text" name="name" required placeholder="e.g., Computer Science">
                            </div>
                            <div class="form-group" style="flex: 2;">
                                <label>Description</label>
                                <input type="text" name="description" placeholder="Brief description of the department">
                            </div>
                            <div class="form-group" style="align-self: flex-end;">
                                <button type="submit" name="add_department" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Add Department
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Departments List -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> All Departments</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Employees</th>
                                    <th>Students</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($dept = $departments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $dept['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($dept['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($dept['description'] ?: 'N/A'); ?></td>
                                    <td><span class="badge badge-primary"><?php echo $dept['employee_count']; ?></span></td>
                                    <td><span class="badge badge-info"><?php echo $dept['student_count']; ?></span></td>
                                    <td>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this department?');">
                                            <input type="hidden" name="department_id" value="<?php echo $dept['id']; ?>">
                                            <button type="submit" name="delete_department" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
