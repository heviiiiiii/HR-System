<?php
/**
 * Admin - Leave Requests Management
 */
require_once '../config.php';
require_once '../includes/auth.php';

requireRole(['admin', 'hr']);

$conn = getDBConnection();
$user = getCurrentUser();

// Handle approve/reject
if (isset($_GET['action']) && isset($_GET['id'])) {
    $requestId = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'approve' || $action === 'reject') {
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        
        $stmt = $conn->prepare("UPDATE leave_requests SET status = ?, approved_by = ? WHERE id = ?");
        $stmt->bind_param("sii", $status, $_SESSION['user_id'], $requestId);
        $stmt->execute();
        
        redirect('leave_requests.php?msg=Leave request ' . $status);
    }
}

// Get filter
$statusFilter = $_GET['status'] ?? '';

// Build query
$sql = "SELECT lr.*, u.full_name, u.email, u.phone 
        FROM leave_requests lr 
        JOIN users u ON lr.user_id = u.id";

if ($statusFilter) {
    $sql .= " WHERE lr.status = ?";
}

$sql .= " ORDER BY lr.created_at DESC";

if ($statusFilter) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $statusFilter);
} else {
    $stmt = $conn->prepare($sql);
}
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Requests | <?php echo SITE_NAME; ?></title>
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
                <li><a href="leave_requests.php" class="active"><i class="fas fa-calendar-check"></i> <span>Leave Requests</span></a></li>
                <li><a href="job_positions.php"><i class="fas fa-briefcase"></i> <span>Job Positions</span></a></li>
                <li><a href="applications.php"><i class="fas fa-file-alt"></i> <span>Applications</span></a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
                <li class="logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="topbar">
                <h1>Leave Requests</h1>
            </div>
            
            <div class="content">
                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
                <?php endif; ?>
                
                <div class="table-container">
                    <div class="table-header">
                        <h3>All Leave Requests</h3>
                    </div>
                    
                    <!-- Filters -->
                    <div style="padding: 15px 20px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                        <form method="GET" style="display: flex; gap: 10px;">
                            <select name="status" onchange="this.form.submit()" style="padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                            <?php if ($statusFilter): ?>
                                <a href="leave_requests.php" class="btn btn-sm" style="background: #6b7280; color: white;">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Email</th>
                                <th>Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requests)): ?>
                                <tr><td colspan="8" style="text-align: center;">No leave requests found</td></tr>
                            <?php else: ?>
                                <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($req['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($req['email']); ?></td>
                                    <td><?php echo ucfirst($req['leave_type']); ?></td>
                                    <td><?php echo formatDate($req['start_date']); ?></td>
                                    <td><?php echo formatDate($req['end_date']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($req['reason'], 0, 50)) . (strlen($req['reason']) > 50 ? '...' : ''); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $req['status'] === 'approved' ? 'success' : 
                                                ($req['status'] === 'rejected' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($req['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($req['status'] === 'pending'): ?>
                                            <a href="?action=approve&id=<?php echo $req['id']; ?>" 
                                               class="btn btn-sm btn-success" 
                                               onclick="return confirm('Approve this leave request?')">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="?action=reject&id=<?php echo $req['id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Reject this leave request?')">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
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
