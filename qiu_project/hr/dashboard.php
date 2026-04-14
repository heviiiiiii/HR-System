<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireRole('hr');
$user = getCurrentUser();
$employee = getEmployeeData($user['id']);

// Get stats
$conn = getDBConnection();
$total_employees = $conn->query("SELECT COUNT(*) as count FROM users WHERE role IN ('employee', 'hr')")->fetch_assoc()['count'];
$departments = $conn->query("SELECT COUNT(*) as count FROM departments")->fetch_assoc()['count'];
$pending_leaves = $conn->query("SELECT COUNT(*) as count FROM leave_requests WHERE status = 'pending'")->fetch_assoc()['count'];
$open_positions = $conn->query("SELECT COUNT(*) as count FROM job_positions WHERE status = 'open'")->fetch_assoc()['count'];

// Recent employees
$recent_employees = $conn->query("
    SELECT u.full_name, d.name as department_name, u.status
    FROM users u 
    LEFT JOIN employees e ON u.id = e.user_id
    LEFT JOIN departments d ON e.department_id = d.id
    WHERE u.role IN ('employee', 'hr')
    ORDER BY u.created_at DESC LIMIT 5
");

// Get photo path
$photo = $user['photo'] ? '../uploads/photos/' . $user['photo'] : '../assets/images/default-avatar.svg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Dashboard | QIU</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dashboard">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">QAIWAN INTERNATIONAL<br>UNIVERSITY</div>
        
        <div class="profile-box">
            <img src="<?php echo $photo; ?>" alt="HR Manager">
            <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
            <span>Human Resources</span>
        </div>
        
        <ul class="nav-menu">
            <li><a href="dashboard.php" class="active"><i class="fa-solid fa-chart-pie"></i> <span>HR Dashboard</span></a></li>
            <li><a href="employees.php"><i class="fa-solid fa-users"></i> <span>Employees</span></a></li>
            <li><a href="leave_requests.php"><i class="fa-solid fa-calendar-check"></i> <span>Leave Requests</span></a></li>
            <li><a href="job_positions.php"><i class="fa-solid fa-briefcase"></i> <span>Recruitment</span></a></li>
            <li class="logout"><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    
    <!-- Main -->
    <div class="main-content">
        <div class="topbar">
            <h1>HR Dashboard</h1>
        </div>
        
        <div class="content">
            <div class="page-header">
                <h2>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
            </div>
            
            <!-- Cards -->
            <div class="cards-grid">
                <a href="employees.php" class="card-link">
                    <div class="card card-stat">
                        <h3>Total Employees</h3>
                        <p><?php echo $total_employees; ?></p>
                    </div>
                </a>
                
                <a href="../admin/departments.php" class="card-link">
                    <div class="card card-stat">
                        <h3>Departments</h3>
                        <p><?php echo $departments; ?></p>
                    </div>
                </a>
                
                <a href="leave_requests.php" class="card-link">
                    <div class="card card-stat">
                        <h3>Pending Requests</h3>
                        <p><?php echo $pending_leaves; ?></p>
                    </div>
                </a>
                
                <a href="job_positions.php" class="card-link">
                    <div class="card card-stat">
                        <h3>Open Positions</h3>
                        <p><?php echo $open_positions; ?></p>
                    </div>
                </a>
            </div>
            
            <!-- Sections -->
            <div class="sections">
                <div class="card">
                    <div class="card-header">
                        <h3>Recent Employees</h3>
                    </div>
                    <table>
                        <tr>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Status</th>
                        </tr>
                        <?php while ($emp = $recent_employees->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($emp['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($emp['department_name'] ?? 'N/A'); ?></td>
                            <td><span class="status <?php echo $emp['status']; ?>"><?php echo ucfirst($emp['status']); ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
                
                <div>
                    <div class="notice">
                        <h4>HR Announcement</h4>
                        <p>Annual staff evaluation begins next week.</p>
                    </div>
                    
                    <div class="notice">
                        <h4>Recruitment Update</h4>
                        <p>New lecturer position approved for Software Engineering.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
