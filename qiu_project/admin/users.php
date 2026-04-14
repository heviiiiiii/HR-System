<?php
/**
 * Admin - Users Management
 */
require_once '../config.php';
require_once '../includes/auth.php';

requireRole(['admin', 'hr']);

$conn = getDBConnection();
$user = getCurrentUser();

// Handle status toggle
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $userId = intval($_GET['id']);
    $newStatus = $_GET['toggle'] === 'activate' ? 'active' : 'inactive';
    
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $userId);
    $stmt->execute();
    
    redirect('users.php?msg=Status updated');
}

// Handle delete
if (isset($_GET['delete'])) {
    $userId = intval($_GET['delete']);
    
    // Prevent deleting self
    if ($userId !== $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        redirect('users.php?msg=User deleted');
    }
}

// Get filters
$roleFilter = $_GET['role'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];
$types = "";

if ($roleFilter) {
    $sql .= " AND role = ?";
    $params[] = $roleFilter;
    $types .= "s";
}

if ($search) {
    $sql .= " AND (full_name LIKE ? OR email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management | <?php echo SITE_NAME; ?></title>
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
                <li><a href="users.php" class="active"><i class="fas fa-users"></i> <span>All Users</span></a></li>
                <li><a href="employees.php"><i class="fas fa-user-tie"></i> <span>Employees</span></a></li>
                <li><a href="students.php"><i class="fas fa-graduation-cap"></i> <span>Students</span></a></li>
                <li><a href="departments.php"><i class="fas fa-building"></i> <span>Departments</span></a></li>
                <li><a href="leave_requests.php"><i class="fas fa-calendar-check"></i> <span>Leave Requests</span></a></li>
                <li><a href="job_positions.php"><i class="fas fa-briefcase"></i> <span>Job Positions</span></a></li>
                <li><a href="applications.php"><i class="fas fa-file-alt"></i> <span>Applications</span></a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
                <li class="logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="topbar">
                <h1>Users Management</h1>
            </div>
            
            <div class="content">
                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
                <?php endif; ?>
                
                <div class="table-container">
                    <div class="table-header">
                        <h3>All Users (<?php echo count($users); ?>)</h3>
                        <a href="add_user.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add New User</a>
                    </div>
                    
                    <!-- Filters -->
                    <div style="padding: 15px 20px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                        <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <select name="role" onchange="this.form.submit()" style="padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <option value="">All Roles</option>
                                <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="hr" <?php echo $roleFilter === 'hr' ? 'selected' : ''; ?>>HR</option>
                                <option value="employee" <?php echo $roleFilter === 'employee' ? 'selected' : ''; ?>>Employee</option>
                                <option value="student" <?php echo $roleFilter === 'student' ? 'selected' : ''; ?>>Student</option>
                            </select>
                            <input type="text" name="search" placeholder="Search name or email..." 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   style="padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 6px; flex: 1; min-width: 200px;">
                            <button type="submit" class="btn btn-primary btn-sm">Search</button>
                            <?php if ($roleFilter || $search): ?>
                                <a href="users.php" class="btn btn-sm" style="background: #6b7280; color: white;">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?php echo $u['id']; ?></td>
                                <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><?php echo htmlspecialchars($u['phone'] ?? '-'); ?></td>
                                <td><span class="badge badge-info"><?php echo ucfirst($u['role']); ?></span></td>
                                <td>
                                    <span class="badge badge-<?php echo $u['status'] === 'active' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($u['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($u['created_at']); ?></td>
                                <td>
                                    <a href="edit_user.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                        <?php if ($u['status'] === 'active'): ?>
                                            <a href="?toggle=deactivate&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-warning" 
                                               onclick="return confirm('Deactivate this user?')" title="Deactivate">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="?toggle=activate&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-success" title="Activate">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?delete=<?php echo $u['id']; ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Delete this user? This cannot be undone.')" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
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
