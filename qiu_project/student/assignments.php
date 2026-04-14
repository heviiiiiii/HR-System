<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireRole('student');

$conn = getDBConnection();
$user = getCurrentUser();

// ===============================
// Get assignments for enrolled courses (SCHEMA FIXED)
// ===============================
$stmt = $conn->prepare("
    SELECT 
        a.id,
        a.course_id,
        a.title,
        a.description,
        a.due_date,
        a.max_score AS total_marks,
        a.created_at,
        c.course_code,
        c.course_name,
        IFNULL(s.id, 0) AS submitted
    FROM enrollments e
    JOIN courses c ON c.id = e.course_id
    JOIN assignments a ON a.course_id = c.id
    LEFT JOIN assignment_submissions s 
        ON s.assignment_id = a.id AND s.student_id = e.student_user_id
    WHERE e.student_user_id = ?
    ORDER BY a.due_date ASC
");

$stmt->bind_param("i", $user['id']);
$stmt->execute();
$assignments = $stmt->get_result();
$stmt->close();

$error = '';

// ===============================
// Handle submission (SCHEMA FIXED)
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_assignment'])) {

    $assignment_id = (int)($_POST['assignment_id'] ?? 0);

    if ($assignment_id <= 0) {
        $error = "Invalid assignment.";
    } else {

        // Check if already submitted
        $stmtCheck = $conn->prepare("
            SELECT id FROM assignment_submissions 
            WHERE assignment_id = ? AND student_id = ?
            LIMIT 1
        ");
        $stmtCheck->bind_param("ii", $assignment_id, $user['id']);
        $stmtCheck->execute();
        $checkRes = $stmtCheck->get_result();
        $stmtCheck->close();

        if ($checkRes->num_rows > 0) {
            $error = "You have already submitted this assignment.";
        } else {

            // This matches your schema column name: submission_text
            $submission_text = sanitize($_POST['submission_text'] ?? '');
            $file_path = null;

            // Upload file (optional)
            if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {

                // Optional size check 5MB
                if ($_FILES['submission_file']['size'] > 5 * 1024 * 1024) {
                    $error = "File too large. Max 5MB.";
                } else {
                    if (function_exists('uploadFile')) {
                        $uploaded = uploadFile(
                            $_FILES['submission_file'],
                            '../uploads/assignments/',
                            ['pdf', 'doc', 'docx', 'zip']
                        );

                        if (!empty($uploaded['success'])) {
                            $file_path = $uploaded['filename'];
                        } else {
                            $error = $uploaded['error'] ?? "Upload failed.";
                        }
                    } else {
                        // fallback (simple upload) if uploadFile helper doesn't exist
                        $allowed = ['pdf','doc','docx','zip'];
                        $ext = strtolower(pathinfo($_FILES['submission_file']['name'], PATHINFO_EXTENSION));

                        if (!in_array($ext, $allowed, true)) {
                            $error = "Invalid file type. Allowed: PDF, DOC, DOCX, ZIP.";
                        } else {
                            $folder = '../uploads/assignments/';
                            if (!is_dir($folder)) {
                                @mkdir($folder, 0777, true);
                            }

                            $newName = 'sub_' . $user['id'] . '_' . time() . '.' . $ext;
                            $dest = $folder . $newName;

                            if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $dest)) {
                                $file_path = $newName;
                            } else {
                                $error = "Failed to upload file.";
                            }
                        }
                    }
                }
            }

            if (empty($error)) {
                $submitted_at = date('Y-m-d H:i:s');

                // Insert according to your schema:
                // assignment_submissions(assignment_id, student_id, submission_text, file_path, submitted_at, score, feedback)
                $stmtIns = $conn->prepare("
                    INSERT INTO assignment_submissions
                        (assignment_id, student_id, submission_text, file_path, submitted_at)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmtIns->bind_param(
                    "iisss",
                    $assignment_id,
                    $user['id'],
                    $submission_text,
                    $file_path,
                    $submitted_at
                );

                if ($stmtIns->execute()) {
                    $stmtIns->close();

                    // If you have alertRedirect helper
                    if (function_exists('alertRedirect')) {
                        alertRedirect('Assignment submitted successfully!', 'assignments.php');
                        exit;
                    }

                    header("Location: assignments.php?msg=submitted");
                    exit;
                } else {
                    $error = "Error submitting assignment: " . $stmtIns->error;
                    $stmtIns->close();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments - QIU Portal</title>
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
            <p>Student</p>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="courses.php"><i class="fas fa-book"></i> My Courses</a>
            <a href="assignments.php" class="active"><i class="fas fa-tasks"></i> Assignments</a>
            <a href="grades.php"><i class="fas fa-chart-line"></i> Grades</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="content-header">
            <h1><i class="fas fa-tasks"></i> My Assignments</h1>
        </header>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> All Assignments</h3>
            </div>

            <div class="card-body">
                <?php if ($assignments->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Course</th>
                                <th>Assignment</th>
                                <th>Due Date</th>
                                <th>Total Marks</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                            </thead>

                            <tbody>
                            <?php while ($assignment = $assignments->fetch_assoc()):
                                $due = $assignment['due_date'] ? strtotime($assignment['due_date']) : null;
                                $is_overdue = $due ? ($due < time()) : false;
                                $is_submitted = (int)$assignment['submitted'] > 0;
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($assignment['course_code']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($assignment['course_name']); ?></small>
                                    </td>

                                    <td>
                                        <strong><?php echo htmlspecialchars($assignment['title']); ?></strong><br>
                                        <small>
                                            <?php echo htmlspecialchars(substr($assignment['description'] ?: '', 0, 70)); ?>...
                                        </small>
                                    </td>

                                    <td>
                                        <?php echo $assignment['due_date'] ? date('M d, Y', strtotime($assignment['due_date'])) : 'No due date'; ?>
                                        <?php if ($is_overdue && !$is_submitted): ?>
                                            <br><span class="badge badge-danger">Overdue</span>
                                        <?php endif; ?>
                                    </td>

                                    <td><?php echo (int)$assignment['total_marks']; ?></td>

                                    <td>
                                        <?php if ($is_submitted): ?>
                                            <span class="badge badge-success">Submitted</span>
                                        <?php elseif ($is_overdue): ?>
                                            <span class="badge badge-danger">Missed</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (!$is_submitted && !$is_overdue): ?>
                                            <button class="btn btn-sm btn-primary"
                                                    onclick="openSubmitModal(<?php echo (int)$assignment['id']; ?>, '<?php echo htmlspecialchars($assignment['title'], ENT_QUOTES); ?>')">
                                                <i class="fas fa-upload"></i> Submit
                                            </button>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>

                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-check"></i>
                        <h3>No Assignments</h3>
                        <p>You don't have any assignments yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Submit Modal -->
<div id="submitModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2><i class="fas fa-upload"></i> Submit Assignment</h2>
        <p id="assignmentTitle"></p>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="assignment_id" id="assignmentId">

            <div class="form-group">
                <label>Submission Text</label>
                <textarea name="submission_text" rows="4" placeholder="Enter your answer or notes..."></textarea>
            </div>

            <div class="form-group">
                <label>Upload File (Optional)</label>
                <input type="file" name="submission_file" accept=".pdf,.doc,.docx,.zip">
                <small>PDF, DOC, DOCX, ZIP - Max 5MB</small>
            </div>

            <button type="submit" name="submit_assignment" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Submit
            </button>
        </form>
    </div>
</div>

<script>
    function openSubmitModal(id, title) {
        document.getElementById('assignmentId').value = id;
        document.getElementById('assignmentTitle').textContent = title;
        document.getElementById('submitModal').style.display = 'block';
    }
    function closeModal() {
        document.getElementById('submitModal').style.display = 'none';
    }
    window.onclick = function (event) {
        if (event.target === document.getElementById('submitModal')) {
            closeModal();
        }
    }
</script>

</body>
</html>
