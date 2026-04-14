<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireRole('hr');

$conn = getDBConnection(); // ✅ FIX: was missing
$user = getCurrentUser();

// Filters
$department_filter = isset($_GET['department']) ? (int)$_GET['department'] : 0; // department_id
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build base query (schema-correct)
$sql = "
    SELECT 
        u.*,
        e.employee_code,
        e.position,
        e.salary,
        e.hire_date,
        d.name AS department_name
    FROM users u
    JOIN employees e ON u.id = e.user_id
    LEFT JOIN departments d ON d.id = e.department_id
    WHERE u.role IN ('employee', 'hr')
";

// Dynamic filters (prepared)
$params = [];
$types = "";

if ($department_filter > 0) {
    $sql .= " AND e.department_id = ?";
    $types .= "i";
    $params[] = $department_filter;
}

if ($search !== '') {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR e.employee_code LIKE ?)";
    $types .= "sss";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY e.hire_date DESC";

// Prepare + execute
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Query prepare failed: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$employees = $stmt->get_result();
$stmt->close();

// Departments for dropdown (use id + name)
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees - QIU HR</title>
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
                <p>HR Manager</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="employees.php" class="active"><i class="fas fa-user-tie"></i> Employees</a>
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
                <h1><i class="fas fa-user-tie"></i> Employee Directory</h1>
            </header>
            
            <!-- Filters -->
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <select name="department">
                                    <option value="0">All Departments</option>
                                    <?php while ($dept = $departments->fetch_assoc()): ?>
                                        <option value="<?php echo (int)$dept['id']; ?>"
                                            <?php echo ($department_filter === (int)$dept['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <input type="text" name="search"
                                       placeholder="Search by name, email, or code..."
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                                <a href="employees.php" class="btn btn-secondary">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Employees Table -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> All Employees (<?php echo (int)$employees->num_rows; ?>)</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Position</th>
                                    <th>Hire Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($employees->num_rows > 0): ?>
                                    <?php while ($emp = $employees->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <img src="../uploads/photos/<?php echo $emp['photo'] ?: 'default.png'; ?>" 
                                                 alt="Photo" class="table-avatar">
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($emp['employee_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($emp['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                        <td><?php echo htmlspecialchars($emp['department_name'] ?: 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($emp['position'] ?: 'N/A'); ?></td>
                                        <td><?php echo $emp['hire_date'] ? date('M d, Y', strtotime($emp['hire_date'])) : 'N/A'; ?></td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo $emp['status'] === 'active' ? 'success' : 
                                                    ($emp['status'] === 'on_leave' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($emp['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No employees found.</td>
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
