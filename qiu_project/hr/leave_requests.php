<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireRole('hr');

$conn = getDBConnection(); // ✅ FIX: missing in your file
$user = getCurrentUser();

$error = '';
$success = '';

// ===============================
// Handle approve/reject (SCHEMA + SAFE)
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_id = (int)($_POST['leave_id'] ?? 0);
    $action = sanitize($_POST['action'] ?? '');

    if ($leave_id > 0 && in_array($action, ['approved', 'rejected'], true)) {

        // Update leave request
        $stmt = $conn->prepare("UPDATE leave_requests SET status = ?, approved_by = ? WHERE id = ?");
        $stmt->bind_param("sii", $action, $user['id'], $leave_id);

        if ($stmt->execute()) {
            $stmt->close();

            // If approved: set user status on_leave ONLY if leave is active now
            if ($action === 'approved') {
                $stmtLeave = $conn->prepare("SELECT user_id, start_date, end_date FROM leave_requests WHERE id = ? LIMIT 1");
                $stmtLeave->bind_param("i", $leave_id);
                $stmtLeave->execute();
                $leaveRes = $stmtLeave->get_result();
                $leaveData = $leaveRes->fetch_assoc();
                $stmtLeave->close();

                if ($leaveData) {
                    $now = time();
                    $start = strtotime($leaveData['start_date']);
                    $end = strtotime($leaveData['end_date']);

                    if ($start <= $now && $end >= $now) {
                        $stmtUser = $conn->prepare("UPDATE users SET status = 'on_leave' WHERE id = ?");
                        $stmtUser->bind_param("i", $leaveData['user_id']);
                        $stmtUser->execute();
                        $stmtUser->close();
                    }
                }
            }

            if (function_exists('alertRedirect')) {
                alertRedirect('Leave request ' . $action . ' successfully!', 'leave_requests.php');
                exit;
            } else {
                header("Location: leave_requests.php?msg=" . urlencode($action));
                exit;
            }

        } else {
            $error = "Failed to update leave request: " . $stmt->error;
            $stmt->close();
        }
    } else {
        $error = "Invalid request.";
    }
}

// ===============================
// Filters
// ===============================
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$sql = "
    SELECT 
        lr.*,
        u.full_name,
        u.email,
        d.name AS department_name
    FROM leave_requests lr
    JOIN users u ON lr.user_id = u.id
    LEFT JOIN employees e ON e.user_id = u.id
    LEFT JOIN departments d ON d.id = e.department_id
    WHERE 1=1
";

$params = [];
$types = "";

if ($status_filter !== '') {
    $sql .= " AND lr.status = ?";
    $types .= "s";
    $params[] = $status_filter;
}

$sql .= " ORDER BY lr.created_at DESC";

$stmtList = $conn->prepare($sql);
if (!$stmtList) {
    die("Query prepare failed: " . $conn->error);
}

if (!empty($params)) {
    $stmtList->bind_param($types, ...$params);
}

$stmtList->execute();
$leaves = $stmtList->get_result();
$stmtList->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Requests - QIU HR</title>
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
                <a href="employees.php"><i class="fas fa-user-tie"></i> Employees</a>
                <a href="leave_requests.php" class="active"><i class="fas fa-calendar-minus"></i> Leave Requests</a>
                <a href="job_positions.php"><i class="fas fa-briefcase"></i> Job Positions</a>
                <a href="applications.php"><i class="fas fa-file-alt"></i> Applications</a>
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1><i class="fas fa-calendar-minus"></i> Leave Requests</h1>
            </header>

            <?php if (!empty($_GET['msg'])): ?>
                <div class="alert alert-success">
                    Leave request <?php echo htmlspecialchars($_GET['msg']); ?> successfully!
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <select name="status">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="leave_requests.php" class="btn btn-secondary">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Leave Requests Table -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> All Leave Requests (<?php echo (int)$leaves->num_rows; ?>)</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Leave Type</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($leaves->num_rows > 0): ?>
                                    <?php while ($leave = $leaves->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($leave['full_name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($leave['email']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($leave['department_name'] ?: 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($leave['leave_type']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($leave['start_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($leave['end_date'])); ?></td>
                                        <td><?php echo htmlspecialchars(substr($leave['reason'], 0, 50)); ?>...</td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo $leave['status'] === 'approved' ? 'success' :
                                                    ($leave['status'] === 'pending' ? 'warning' : 'danger');
                                            ?>">
                                                <?php echo ucfirst($leave['status']); ?>
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <?php if ($leave['status'] === 'pending'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="leave_id" value="<?php echo (int)$leave['id']; ?>">
                                                    <input type="hidden" name="action" value="approved">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="leave_id" value="<?php echo (int)$leave['id']; ?>">
                                                    <input type="hidden" name="action" value="rejected">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Reject">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
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
