<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireRole('admin');
$user = getCurrentUser();

// Get stats
$conn = getDBConnection();
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_employees = $conn->query("SELECT COUNT(*) as count FROM users WHERE role IN ('employee', 'hr')")->fetch_assoc()['count'];
$total_students = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'")->fetch_assoc()['count'];
$departments = $conn->query("SELECT COUNT(*) as count FROM departments")->fetch_assoc()['count'];

// Recent users
$recent_users = $conn->query("
    SELECT full_name, email, role, status, created_at
    FROM users
    ORDER BY created_at DESC LIMIT 5
");

// Get photo path
$photo = $user['photo'] ? '../uploads/photos/' . $user['photo'] : '../assets/images/default-avatar.svg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | QIU</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dashboard">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">QAIWAN INTERNATIONAL<br>UNIVERSITY</div>
        
        <div class="profile-box">
            <img src="<?php echo $photo; ?>" alt="Admin">
            <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
            <span>Administrator</span>
        </div>
        
        <ul class="nav-menu">
            <li><a href="dashboard.php" class="active"><i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span></a></li>
            <li><a href="users.php"><i class="fa-solid fa-users-gear"></i> <span>Users</span></a></li>
            <li><a href="employees.php"><i class="fa-solid fa-user-tie"></i> <span>Employees</span></a></li>
            <li><a href="students.php"><i class="fa-solid fa-user-graduate"></i> <span>Students</span></a></li>
            <li><a href="departments.php"><i class="fa-solid fa-building"></i> <span>Departments</span></a></li>
            <li><a href="job_positions.php"><i class="fa-solid fa-briefcase"></i> <span>Job Positions</span></a></li>
            <li><a href="applications.php"><i class="fa-solid fa-file-lines"></i> <span>Applications</span></a></li>
            <li><a href="leave_requests.php"><i class="fa-solid fa-calendar-check"></i> <span>Leave Requests</span></a></li>
            <li><a href="settings.php"><i class="fa-solid fa-gear"></i> <span>Settings</span></a></li>
            <li class="logout"><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    
    <!-- Main -->
    <div class="main-content">
        <div class="topbar">
            <h1>Admin Dashboard</h1>
        </div>
        
        <div class="content">
            <div class="page-header">
                <h2>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
            </div>
            
            <!-- Cards -->
            <div class="cards-grid">
                <a href="users.php" class="card-link">
                    <div class="card card-stat">
                        <h3>Total Users</h3>
                        <p><?php echo $total_users; ?></p>
                    </div>
                </a>
                
                <a href="employees.php" class="card-link">
                    <div class="card card-stat">
                        <h3>Employees</h3>
                        <p><?php echo $total_employees; ?></p>
                    </div>
                </a>
                
                <a href="students.php" class="card-link">
                    <div class="card card-stat">
                        <h3>Students</h3>
                        <p><?php echo $total_students; ?></p>
                    </div>
                </a>
                
                <a href="departments.php" class="card-link">
                    <div class="card card-stat">
                        <h3>Departments</h3>
                        <p><?php echo $departments; ?></p>
                    </div>
                </a>
            </div>
            
            <!-- Sections -->
            <div class="sections">
                <div class="card">
                    <div class="card-header">
                        <h3>Recent Users</h3>
                        <a href="users.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <table>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                        </tr>
                        <?php while ($row = $recent_users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><span class="badge badge-info"><?php echo ucfirst($row['role']); ?></span></td>
                            <td><span class="status <?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
                
                <div>
                    <div class="notice">
                        <h4>System Notice</h4>
                        <p>System maintenance scheduled for Sunday at 2:00 AM.</p>
                    </div>
                    
                    <div class="notice">
                        <h4>New Feature</h4>
                        <p>Online job application portal is now live!</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
