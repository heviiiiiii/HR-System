<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireRole('hr');

$conn = getDBConnection(); // ✅ FIX
$user = getCurrentUser();

$error = '';

// ===============================
// Handle form submissions (SCHEMA FIXED)
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add new position
    if (isset($_POST['add_position'])) {

        $title = sanitize($_POST['title'] ?? '');
        $department_id = (int)($_POST['department'] ?? 0);
        $description = sanitize($_POST['description'] ?? '');
        $requirements = sanitize($_POST['requirements'] ?? '');
        $salary_range = sanitize($_POST['salary_range'] ?? '');
        $deadline = sanitize($_POST['deadline'] ?? '');

        if ($title === '' || $department_id <= 0 || $description === '' || $deadline === '') {
            $error = "Please fill in all required fields.";
        } else {
            // Schema: (title, department_id, description, requirements, salary_range, posted_date, deadline, status)
            $stmt = $conn->prepare("
                INSERT INTO job_positions (title, department_id, description, requirements, salary_range, deadline)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sissss", $title, $department_id, $description, $requirements, $salary_range, $deadline);

            if ($stmt->execute()) {
                $stmt->close();
                if (function_exists('alertRedirect')) {
                    alertRedirect('Job position added successfully!', 'job_positions.php');
                    exit;
                }
                header("Location: job_positions.php?msg=added");
                exit;
            } else {
                $error = "Error adding position: " . $stmt->error;
                $stmt->close();
            }
        }
    }

    // Toggle status
    if (isset($_POST['toggle_status'])) {
        $id = (int)($_POST['position_id'] ?? 0);
        $status = sanitize($_POST['new_status'] ?? '');

        if ($id > 0 && in_array($status, ['open', 'closed'], true)) {
            $stmt = $conn->prepare("UPDATE job_positions SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();
            $stmt->close();

            if (function_exists('alertRedirect')) {
                alertRedirect('Position status updated!', 'job_positions.php');
                exit;
            }
            header("Location: job_positions.php?msg=status");
            exit;
        } else {
            $error = "Invalid status update.";
        }
    }
}

// ===============================
// Get all positions (SCHEMA FIXED)
// ===============================
$positions = $conn->query("
    SELECT 
        jp.*,
        d.name AS department_name,
        (SELECT COUNT(*) FROM job_applications WHERE position_id = jp.id) AS application_count
    FROM job_positions jp
    LEFT JOIN departments d ON d.id = jp.department_id
    ORDER BY jp.posted_date DESC
");

// Departments for select (id + name)
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Positions - QIU HR</title>
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
            <a href="leave_requests.php"><i class="fas fa-calendar-minus"></i> Leave Requests</a>
            <a href="job_positions.php" class="active"><i class="fas fa-briefcase"></i> Job Positions</a>
            <a href="applications.php"><i class="fas fa-file-alt"></i> Applications</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="content-header">
            <h1><i class="fas fa-briefcase"></i> Job Positions</h1>
        </header>

        <?php if (!empty($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php
                if ($_GET['msg'] === 'added') echo "Job position added successfully!";
                elseif ($_GET['msg'] === 'status') echo "Position status updated!";
                ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Add Position Form -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-plus"></i> Post New Position</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Job Title *</label>
                            <input type="text" name="title" required placeholder="e.g., Software Developer">
                        </div>

                        <div class="form-group">
                            <label>Department *</label>
                            <select name="department" required>
                                <option value="0">Select Department</option>
                                <?php while ($dept = $departments->fetch_assoc()): ?>
                                    <option value="<?php echo (int)$dept['id']; ?>">
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description *</label>
                        <textarea name="description" rows="3" required placeholder="Job description..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Requirements</label>
                        <textarea name="requirements" rows="3" placeholder="Required qualifications..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Salary Range</label>
                            <input type="text" name="salary_range" placeholder="e.g., $2,500 - $3,500">
                        </div>
                        <div class="form-group">
                            <label>Application Deadline *</label>
                            <input type="date" name="deadline" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <button type="submit" name="add_position" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Post Position
                    </button>
                </form>
            </div>
        </div>

        <!-- Positions List -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> All Positions (<?php echo (int)$positions->num_rows; ?>)</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
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
                        <?php if ($positions->num_rows > 0): ?>
                            <?php while ($pos = $positions->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($pos['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($pos['department_name'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($pos['salary_range'] ?: 'N/A'); ?></td>
                                    <td><?php echo $pos['deadline'] ? date('M d, Y', strtotime($pos['deadline'])) : 'N/A'; ?></td>
                                    <td><span class="badge badge-info"><?php echo (int)$pos['application_count']; ?></span></td>
                                    <td>
                                        <span class="badge badge-<?php echo $pos['status'] === 'open' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($pos['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="position_id" value="<?php echo (int)$pos['id']; ?>">
                                            <input type="hidden" name="new_status" value="<?php echo $pos['status'] === 'open' ? 'closed' : 'open'; ?>">
                                            <button type="submit" name="toggle_status"
                                                    class="btn btn-sm btn-<?php echo $pos['status'] === 'open' ? 'warning' : 'success'; ?>">
                                                <?php echo $pos['status'] === 'open' ? 'Close' : 'Open'; ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No positions found.</td>
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
