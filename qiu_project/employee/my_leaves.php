<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireRole('employee');

$conn = getDBConnection(); // ✅ FIX
$user = getCurrentUser();

// Get filter
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$allowed_status = ['', 'pending', 'approved', 'rejected'];
if (!in_array($status_filter, $allowed_status, true)) {
    $status_filter = '';
}

// ===============================
// Get leaves (prepared statement)
// ===============================
if ($status_filter !== '') {
    $stmt = $conn->prepare("
        SELECT * 
        FROM leave_requests 
        WHERE user_id = ? AND status = ?
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("is", $user['id'], $status_filter);
} else {
    $stmt = $conn->prepare("
        SELECT * 
        FROM leave_requests 
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $user['id']);
}
$stmt->execute();
$leaves = $stmt->get_result();
$stmt->close();

// ===============================
// Stats (prepared statements)
// ===============================
function getCount($conn, $userId, $status = null) {
    if ($status === null) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM leave_requests WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM leave_requests WHERE user_id = ? AND status = ?");
        $stmt->bind_param("is", $userId, $status);
    }
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($res['c'] ?? 0);
}

$stats = [
    'total'    => getCount($conn, $user['id']),
    'pending'  => getCount($conn, $user['id'], 'pending'),
    'approved' => getCount($conn, $user['id'], 'approved'),
    'rejected' => getCount($conn, $user['id'], 'rejected'),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Leaves - QIU Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- ✅ Fix design only for this page -->
    <style>
        .main-content { min-width: 0; }
        .content-header { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }

        /* Stats row -> nice cards */
        .stats-grid{
            display:grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap:14px;
            margin: 12px 0 18px;
        }
        @media (max-width: 1100px){
            .stats-grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 600px){
            .stats-grid{ grid-template-columns: 1fr; }
        }

        .stat-card.mini{
            background:#fff;
            border:1px solid rgba(0,0,0,0.08);
            border-radius:14px;
            padding:14px 14px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
            min-width:0;
        }
        .stat-card.mini .stat-info h3{
            margin:0;
            font-size:24px;
            line-height:1.1;
        }
        .stat-card.mini .stat-info p{
            margin:6px 0 0;
            color:#666;
            font-size:13px;
        }

        /* Filter layout */
        .filter-form .form-row{
            display:flex;
            align-items:center;
            justify-content:flex-start;
            gap:12px;
            flex-wrap:wrap;
        }
        .filter-form select{ min-width: 200px; }
        .filter-form .btn{ min-width: 110px; }

        .table-responsive{ overflow-x:auto; }
    </style>
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
            <p>Employee</p>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="leave_request.php"><i class="fas fa-calendar-minus"></i> Leave Requests</a>
            <a href="my_leaves.php" class="active"><i class="fas fa-history"></i> My Leaves</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="content-header">
            <h1><i class="fas fa-history"></i> My Leave History</h1>
            <a href="leave_request.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Request
            </a>
        </header>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card mini">
                <div class="stat-info">
                    <h3><?php echo $stats['total']; ?></h3>
                    <p>Total</p>
                </div>
            </div>
            <div class="stat-card mini">
                <div class="stat-info">
                    <h3><?php echo $stats['pending']; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
            <div class="stat-card mini">
                <div class="stat-info">
                    <h3><?php echo $stats['approved']; ?></h3>
                    <p>Approved</p>
                </div>
            </div>
            <div class="stat-card mini">
                <div class="stat-info">
                    <h3><?php echo $stats['rejected']; ?></h3>
                    <p>Rejected</p>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="card">
            <div class="card-body">
                <form method="GET" class="filter-form">
                    <div class="form-row">
                        <div class="form-group">
                            <select name="status">
                                <option value="" <?php echo $status_filter === '' ? 'selected' : ''; ?>>All Status</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="my_leaves.php" class="btn btn-secondary">Clear</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Leaves Table -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Leave Requests (<?php echo $leaves->num_rows; ?>)</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Leave Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Applied On</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($leaves->num_rows > 0): ?>
                            <?php while ($leave = $leaves->fetch_assoc()):
                                $days = (strtotime($leave['end_date']) - strtotime($leave['start_date'])) / 86400 + 1;
                                ?>
                                <tr>
                                    <td><?php echo (int)$leave['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($leave['leave_type']); ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($leave['start_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($leave['end_date'])); ?></td>
                                    <td><?php echo (int)$days; ?> day<?php echo $days > 1 ? 's' : ''; ?></td>
                                    <td>
                                        <?php
                                        $reason = $leave['reason'] ?? '';
                                        echo htmlspecialchars(substr($reason, 0, 50)) . (strlen($reason) > 50 ? '...' : '');
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php
                                        echo $leave['status'] === 'approved' ? 'success' :
                                            ($leave['status'] === 'pending' ? 'warning' : 'danger');
                                        ?>">
                                            <?php echo ucfirst($leave['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($leave['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No leave requests found.</td>
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
