<?php
/**
 * Admin - Job Applications Management
 */
require_once '../config.php';
require_once '../includes/auth.php';

requireRole(['admin', 'hr']);

$conn = getDBConnection();
$user = getCurrentUser();

$msg = sanitize($_GET['msg'] ?? '');

// Allowed statuses
$allowedStatuses = ['pending', 'reviewed', 'shortlisted', 'hired', 'rejected'];

// Handle status update
if (isset($_POST['update_status'])) {
    $appId = intval($_POST['app_id'] ?? 0);
    $newStatus = sanitize($_POST['status'] ?? '');

    if ($appId > 0 && in_array($newStatus, $allowedStatuses, true)) {
        $stmt = $conn->prepare("UPDATE job_applications SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $appId);
        $stmt->execute();
        $stmt->close();

        redirect('applications.php?msg=' . urlencode('Application status updated'));
    } else {
        redirect('applications.php?msg=' . urlencode('Invalid status update request'));
    }
}

// Get filter
$positionFilter = intval($_GET['position'] ?? 0);
$statusFilter = sanitize($_GET['status'] ?? '');
if ($statusFilter !== '' && !in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

// Build query
$sql = "SELECT ja.*, jp.title as position_title, d.name as department_name
        FROM job_applications ja 
        JOIN job_positions jp ON ja.position_id = jp.id
        LEFT JOIN departments d ON jp.department_id = d.id
        WHERE 1=1";

$params = [];
$types = "";

if ($positionFilter) {
    $sql .= " AND ja.position_id = ?";
    $params[] = $positionFilter;
    $types .= "i";
}

if ($statusFilter) {
    $sql .= " AND ja.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$sql .= " ORDER BY ja.applied_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get positions for filter
$positions = $conn->query("SELECT id, title FROM job_positions ORDER BY title")->fetch_all(MYSQLI_ASSOC);

/**
 * Build correct CV URL:
 * - If DB stores only filename -> ../uploads/cvs/filename.pdf
 * - If DB stores full path like uploads/cvs/filename.pdf -> ../uploads/cvs/filename.pdf (no duplicate)
 * - If DB stores absolute or strange path -> fallback to ../uploads/cvs/<basename>
 */
function cvUrl($cv_path) {
    $cv_path = trim((string)$cv_path);

    if ($cv_path === '') return '';

    // If already includes "uploads/cvs/"
    if (strpos($cv_path, 'uploads/cvs/') !== false) {
        // Make sure it starts from project root with ../
        // e.g. uploads/cvs/file.pdf => ../uploads/cvs/file.pdf
        if (strpos($cv_path, '../') === 0) return $cv_path;
        return '../' . ltrim($cv_path, '/');
    }

    // Otherwise assume it's filename only
    $file = basename($cv_path);
    return '../uploads/cvs/' . $file;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications | <?php echo SITE_NAME; ?></title>
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
                <li><a href="job_positions.php"><i class="fas fa-briefcase"></i> <span>Job Positions</span></a></li>
                <li><a href="applications.php" class="active"><i class="fas fa-file-alt"></i> <span>Applications</span></a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
                <li class="logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="topbar">
                <h1>Job Applications</h1>
            </div>
            
            <div class="content">
                <?php if (!empty($msg)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
                <?php endif; ?>
                
                <div class="table-container">
                    <div class="table-header">
                        <h3>All Applications (<?php echo count($applications); ?>)</h3>
                    </div>
                    
                    <!-- Filters -->
                    <div style="padding: 15px 20px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                        <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <select name="position" onchange="this.form.submit()" style="padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <option value="">All Positions</option>
                                <?php foreach ($positions as $pos): ?>
                                    <option value="<?php echo (int)$pos['id']; ?>" <?php echo $positionFilter == $pos['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($pos['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select name="status" onchange="this.form.submit()" style="padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="reviewed" <?php echo $statusFilter === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                <option value="shortlisted" <?php echo $statusFilter === 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                                <option value="hired" <?php echo $statusFilter === 'hired' ? 'selected' : ''; ?>>Hired</option>
                                <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>

                            <?php if ($positionFilter || $statusFilter): ?>
                                <a href="applications.php" class="btn btn-sm" style="background: #6b7280; color: white;">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Position</th>
                                <th>Department</th>
                                <th>CV</th>
                                <th>Applied</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($applications)): ?>
                                <tr><td colspan="9" style="text-align: center;">No applications found</td></tr>
                            <?php else: ?>
                                <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($app['applicant_name']); ?></td>
                                    <td><?php echo htmlspecialchars($app['applicant_email']); ?></td>
                                    <td><?php echo htmlspecialchars($app['applicant_phone'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($app['position_title']); ?></td>
                                    <td><?php echo htmlspecialchars($app['department_name'] ?? '-'); ?></td>
                                    <td>
                                        <?php $cv = cvUrl($app['cv_path'] ?? ''); ?>
                                        <?php if ($cv): ?>
                                            <a href="<?php echo htmlspecialchars($cv); ?>" target="_blank" class="btn btn-sm btn-primary">
                                                <i class="fas fa-download"></i> CV
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDate($app['applied_at']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $app['status'] === 'hired' ? 'success' : 
                                                ($app['status'] === 'rejected' ? 'danger' : 
                                                ($app['status'] === 'shortlisted' ? 'info' : 'warning')); 
                                        ?>">
                                            <?php echo ucfirst($app['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="app_id" value="<?php echo (int)$app['id']; ?>">
                                            <select name="status" onchange="this.form.submit()" style="padding: 5px; border-radius: 4px; border: 1px solid #e5e7eb;">
                                                <option value="pending" <?php echo $app['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="reviewed" <?php echo $app['status'] === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                                <option value="shortlisted" <?php echo $app['status'] === 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                                                <option value="hired" <?php echo $app['status'] === 'hired' ? 'selected' : ''; ?>>Hired</option>
                                                <option value="rejected" <?php echo $app['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
