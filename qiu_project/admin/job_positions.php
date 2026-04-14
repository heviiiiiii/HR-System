<?php
/**
 * Admin - Job Positions Management
 */
require_once '../config.php';
require_once '../includes/auth.php';

requireRole(['admin', 'hr']);

$conn = getDBConnection();
$user = getCurrentUser();
$departments = getDepartments();

$error = '';
$success = '';

// Handle add new position
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $departmentId = intval($_POST['department_id'] ?? 0);
    $description = sanitize($_POST['description'] ?? '');
    $requirements = sanitize($_POST['requirements'] ?? '');
    $salaryRange = sanitize($_POST['salary_range'] ?? '');
    $deadline = sanitize($_POST['deadline'] ?? '');
    
    if (empty($title) || empty($deadline)) {
        $error = 'Title and deadline are required';
    } else {
        $stmt = $conn->prepare("INSERT INTO job_positions (title, department_id, description, requirements, salary_range, deadline) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sissss", $title, $departmentId, $description, $requirements, $salaryRange, $deadline);
        
        if ($stmt->execute()) {
            $success = 'Position added successfully';
        } else {
            $error = 'Failed to add position';
        }
    }
}

// Handle status toggle
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $posId = intval($_GET['id']);
    $newStatus = $_GET['toggle'] === 'open' ? 'open' : 'closed';
    
    $stmt = $conn->prepare("UPDATE job_positions SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $posId);
    $stmt->execute();
    
    redirect('job_positions.php?msg=Status updated');
}

// Handle delete
if (isset($_GET['delete'])) {
    $posId = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM job_positions WHERE id = ?");
    $stmt->bind_param("i", $posId);
    $stmt->execute();
    redirect('job_positions.php?msg=Position deleted');
}

// Get all positions
$positions = $conn->query("
    SELECT jp.*, d.name as department_name,
           (SELECT COUNT(*) FROM job_applications WHERE position_id = jp.id) as applications_count
    FROM job_positions jp 
    LEFT JOIN departments d ON jp.department_id = d.id 
    ORDER BY jp.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Positions | <?php echo SITE_NAME; ?></title>
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
                <li><a href="users.php"><i class="fas fa-users"></i> <span>All Users</span></a></li>
                <li><a href="employees.php"><i class="fas fa-user-tie"></i> <span>Employees</span></a></li>
                <li><a href="students.php"><i class="fas fa-graduation-cap"></i> <span>Students</span></a></li>
                <li><a href="departments.php"><i class="fas fa-building"></i> <span>Departments</span></a></li>
                <li><a href="leave_requests.php"><i class="fas fa-calendar-check"></i> <span>Leave Requests</span></a></li>
                <li><a href="job_positions.php" class="active"><i class="fas fa-briefcase"></i> <span>Job Positions</span></a></li>
                <li><a href="applications.php"><i class="fas fa-file-alt"></i> <span>Applications</span></a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
                <li class="logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="topbar">
                <h1>Job Positions</h1>
            </div>
            
            <div class="content">
                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <!-- Add New Position Form -->
                <div class="card" style="margin-bottom: 25px;">
                    <div class="card-header">
                        <h3>Post New Position</h3>
                    </div>
                    <form method="POST" style="padding: 15px 0;">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="title">Position Title *</label>
                                <input type="text" id="title" name="title" required>
                            </div>
                            <div class="form-group">
                                <label for="department_id">Department</label>
                                <select id="department_id" name="department_id">
                                    <option value="">-- Select --</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="salary_range">Salary Range</label>
                                <input type="text" id="salary_range" name="salary_range" placeholder="e.g., $2,000 - $3,000">
                            </div>
                            <div class="form-group">
                                <label for="deadline">Application Deadline *</label>
                                <input type="date" id="deadline" name="deadline" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="requirements">Requirements</label>
                            <textarea id="requirements" name="requirements" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Post Position
                        </button>
                    </form>
                </div>
                
                <!-- Positions Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3>All Positions (<?php echo count($positions); ?>)</h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Department</th>
                                <th>Salary</th>
                                <th>Deadline</th>
                                <th>Applications</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($positions as $pos): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pos['title']); ?></td>
                                <td><?php echo htmlspecialchars($pos['department_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($pos['salary_range'] ?? '-'); ?></td>
                                <td><?php echo formatDate($pos['deadline']); ?></td>
                                <td>
                                    <a href="applications.php?position=<?php echo $pos['id']; ?>">
                                        <?php echo $pos['applications_count']; ?> applications
                                    </a>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $pos['status'] === 'open' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($pos['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($pos['status'] === 'open'): ?>
                                        <a href="?toggle=closed&id=<?php echo $pos['id']; ?>" class="btn btn-sm btn-warning" title="Close">
                                            <i class="fas fa-lock"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="?toggle=open&id=<?php echo $pos['id']; ?>" class="btn btn-sm btn-success" title="Reopen">
                                            <i class="fas fa-lock-open"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="?delete=<?php echo $pos['id']; ?>" class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Delete this position?')" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
