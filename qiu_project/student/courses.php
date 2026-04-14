<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireRole('student');

$conn = getDBConnection();
$user = getCurrentUser();

// Optional: if you have this helper
if (function_exists('getStudentData')) {
    $student = getStudentData($user['id']);
}

// ===============================
// Get enrolled courses (FIXED)
// ===============================
$stmt = $conn->prepare("
    SELECT 
        c.id,
        c.course_code,
        c.course_name,
        c.description,
        c.credits,
        c.semester,
        e.grade,
        e.enrolled_at,
        u.full_name AS instructor_name
    FROM enrollments e
    JOIN courses c ON c.id = e.course_id
    LEFT JOIN employees emp ON emp.id = c.instructor_id
    LEFT JOIN users u ON u.id = emp.user_id
    WHERE e.student_user_id = ?
    ORDER BY c.course_name
");

$stmt->bind_param("i", $user['id']);
$stmt->execute();
$courses = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - QIU Portal</title>
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
            <img src="../uploads/photos/<?php echo $user['photo'] ?: 'default.png'; ?>" class="profile-img">
            <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
            <p>Student</p>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="courses.php" class="active"><i class="fas fa-book"></i> My Courses</a>
            <a href="assignments.php"><i class="fas fa-tasks"></i> Assignments</a>
            <a href="grades.php"><i class="fas fa-chart-line"></i> Grades</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="content-header">
            <h1><i class="fas fa-book"></i> My Courses</h1>
        </header>

        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-list"></i>
                    Enrolled Courses (<?php echo $courses->num_rows; ?>)
                </h3>
            </div>

            <div class="card-body">
                <?php if ($courses->num_rows > 0): ?>
                    <div class="courses-grid">
                        <?php while ($course = $courses->fetch_assoc()): ?>
                            <div class="course-card">
                                <div class="course-header">
                                    <span class="course-code">
                                        <?php echo htmlspecialchars($course['course_code']); ?>
                                    </span>
                                    <span class="course-credits">
                                        <?php echo (int)$course['credits']; ?> Credits
                                    </span>
                                </div>

                                <h4><?php echo htmlspecialchars($course['course_name']); ?></h4>

                                <p class="course-description">
                                    <?php
                                    echo htmlspecialchars(
                                        substr($course['description'] ?: 'No description available.', 0, 120)
                                    );
                                    ?>
                                </p>

                                <div class="course-meta">
                                    <span>
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($course['instructor_name'] ?: 'TBA'); ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('M Y', strtotime($course['enrolled_at'])); ?>
                                    </span>
                                </div>

                                <div class="course-footer">
                                    <?php if (!empty($course['grade'])): ?>
                                        <span class="badge badge-success">
                                            Grade: <?php echo htmlspecialchars($course['grade']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">In Progress</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-book-open"></i>
                        <h3>No Courses Enrolled</h3>
                        <p>You haven't enrolled in any courses yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>
