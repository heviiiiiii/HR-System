<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireRole('student');

$conn = getDBConnection();
$user = getCurrentUser();

// Student data (from students table)
$student = null;
if (function_exists('getStudentData')) {
    $student = getStudentData($user['id']);
}

// Get all enrollments with grades
$stmt = $conn->prepare("
    SELECT 
        c.course_code,
        c.course_name,
        c.credits,
        e.grade,
        e.enrolled_at
    FROM enrollments e
    JOIN courses c ON c.id = e.course_id
    WHERE e.student_user_id = ?
    ORDER BY e.enrolled_at DESC
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$grades = $stmt->get_result();

// Grade points scale
$grade_points = [
    'A+' => 4.0, 'A' => 4.0, 'A-' => 3.7,
    'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7,
    'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7,
    'D+' => 1.3, 'D' => 1.0,
    'F'  => 0.0
];

// Calculate GPA
$total_points = 0;
$total_credits = 0;

$rows = [];
while ($row = $grades->fetch_assoc()) {
    $rows[] = $row;

    $g = $row['grade'];
    if (!empty($g) && isset($grade_points[$g])) {
        $credits = (int)$row['credits'];
        $total_points += $grade_points[$g] * $credits;
        $total_credits += $credits;
    }
}

$calculated_gpa = ($total_credits > 0) ? ($total_points / $total_credits) : 0;
$total_courses = count($rows);

// Use stored GPA if exists, otherwise calculated
$display_gpa = ($student && isset($student['gpa'])) ? (float)$student['gpa'] : $calculated_gpa;
$display_semester = ($student && isset($student['semester'])) ? (int)$student['semester'] : 1;
$display_program = ($student && isset($student['program'])) ? $student['program'] : 'N/A';

function gradeBadgeClass($g) {
    if (in_array($g, ['A+', 'A', 'A-'])) return 'g-badge g-success';
    if (in_array($g, ['B+', 'B', 'B-'])) return 'g-badge g-primary';
    if (in_array($g, ['C+', 'C', 'C-'])) return 'g-badge g-warning';
    return 'g-badge g-danger';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Grades - QIU Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- ✅ Nicer UI (safe enhancement) -->
    <style>
        .main-content { min-width: 0; }
        .content-wrap { padding: 0; min-width: 0; }

        .nice-page { display: flex; flex-direction: column; gap: 16px; }
        .nice-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .nice-sub {
            color: #666;
            font-size: 14px;
            margin-top: 4px;
        }

        /* Summary cards */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }
        @media (max-width: 1100px) { .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 650px) { .summary-grid { grid-template-columns: 1fr; } }

        .summary-card {
            background: #fff;
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: 14px;
            padding: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
            min-width: 0;
        }
        .summary-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: grid; place-items: center;
            color: #fff;
            flex: 0 0 auto;
        }
        .summary-info { min-width: 0; }
        .summary-info h3 {
            margin: 0;
            font-size: 20px;
            line-height: 1.2;
            word-break: break-word;
        }
        .summary-info p {
            margin: 4px 0 0;
            color: #666;
            font-size: 13px;
        }

        /* Cards */
        .nice-card {
            background: #fff;
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .nice-card-header {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .nice-card-header h3 {
            margin: 0;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nice-card-body { padding: 14px 16px; }

        /* Table */
        .nice-table-wrap { overflow-x: auto; }
        .nice-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
            min-width: 700px;
        }
        .nice-table thead th {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: 10px 12px;
            text-align: left;
            white-space: nowrap;
        }
        .nice-table tbody tr {
            background: #fafafa;
            border-radius: 12px;
            overflow: hidden;
        }
        .nice-table tbody td {
            padding: 12px;
            border-top: 1px solid rgba(0,0,0,0.04);
            border-bottom: 1px solid rgba(0,0,0,0.04);
            white-space: nowrap;
        }
        .nice-table tbody td:first-child {
            border-left: 1px solid rgba(0,0,0,0.04);
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }
        .nice-table tbody td:last-child {
            border-right: 1px solid rgba(0,0,0,0.04);
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }
        .course-name {
            white-space: normal;
            max-width: 420px;
            line-height: 1.3;
        }

        /* Badges */
        .g-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid rgba(0,0,0,0.06);
        }
        .g-success { background: rgba(46, 204, 113, 0.14); color: #1f7a45; }
        .g-primary { background: rgba(52, 152, 219, 0.14); color: #1f5f8a; }
        .g-warning { background: rgba(241, 196, 15, 0.18); color: #8a6b00; }
        .g-danger  { background: rgba(231, 76, 60, 0.14); color: #8a2f28; }
        .g-muted   { background: rgba(0,0,0,0.06); color: #555; }

        /* Grade scale chips */
        .scale-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .scale-chip {
            background: #fafafa;
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: 999px;
            padding: 8px 12px;
            display: inline-flex;
            gap: 10px;
            align-items: center;
            font-size: 13px;
        }
        .scale-chip .num {
            font-weight: 800;
            color: #333;
        }
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
            <p>Student</p>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="courses.php"><i class="fas fa-book"></i> My Courses</a>
            <a href="assignments.php"><i class="fas fa-tasks"></i> Assignments</a>
            <a href="grades.php" class="active"><i class="fas fa-chart-line"></i> Grades</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-wrap nice-page">

            <div class="nice-header">
                <div>
                    <h1 style="margin:0;"><i class="fas fa-chart-line"></i> My Grades</h1>
                    <div class="nice-sub">
                        See your performance, GPA, and course grades in one place.
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-icon" style="background:#2ecc71;"><i class="fas fa-graduation-cap"></i></div>
                    <div class="summary-info">
                        <h3><?php echo number_format($display_gpa, 2); ?></h3>
                        <p>Current GPA</p>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon" style="background:#3498db;"><i class="fas fa-book"></i></div>
                    <div class="summary-info">
                        <h3><?php echo $total_courses; ?></h3>
                        <p>Total Courses</p>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon" style="background:#9b59b6;"><i class="fas fa-layer-group"></i></div>
                    <div class="summary-info">
                        <h3><?php echo $display_semester; ?></h3>
                        <p>Current Semester</p>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon" style="background:#e74c3c;"><i class="fas fa-award"></i></div>
                    <div class="summary-info">
                        <h3><?php echo htmlspecialchars($display_program); ?></h3>
                        <p>Program</p>
                    </div>
                </div>
            </div>

            <!-- Grades Table -->
            <div class="nice-card">
                <div class="nice-card-header">
                    <h3><i class="fas fa-list"></i> Course Grades</h3>
                </div>
                <div class="nice-card-body">
                    <?php if ($total_courses > 0): ?>
                        <div class="nice-table-wrap">
                            <table class="nice-table">
                                <thead>
                                <tr>
                                    <th>Course Code</th>
                                    <th>Course Name</th>
                                    <th>Credits</th>
                                    <th>Enrolled</th>
                                    <th>Grade</th>
                                    <th>Points</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($rows as $grade): ?>
                                    <?php
                                    $g = $grade['grade'];
                                    $points = (!empty($g) && isset($grade_points[$g])) ? number_format($grade_points[$g], 1) : '-';
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($grade['course_code']); ?></strong></td>
                                        <td class="course-name"><?php echo htmlspecialchars($grade['course_name']); ?></td>
                                        <td><?php echo (int)$grade['credits']; ?></td>
                                        <td><?php echo date('M Y', strtotime($grade['enrolled_at'])); ?></td>
                                        <td>
                                            <?php if (!empty($g)): ?>
                                                <span class="<?php echo gradeBadgeClass($g); ?>">
                                                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($g); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="g-badge g-muted">
                                                    <i class="fas fa-hourglass-half"></i> In Progress
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo $points; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-chart-bar"></i>
                            <h3>No Grades Yet</h3>
                            <p>Your grades will appear here once you complete courses.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Grade Scale -->
            <div class="nice-card">
                <div class="nice-card-header">
                    <h3><i class="fas fa-info-circle"></i> Grading Scale</h3>
                </div>
                <div class="nice-card-body">
                    <div class="scale-grid">
                        <div class="scale-chip"><span class="g-badge g-success">A+, A</span> <span class="num">4.0</span></div>
                        <div class="scale-chip"><span class="g-badge g-success">A-</span> <span class="num">3.7</span></div>
                        <div class="scale-chip"><span class="g-badge g-primary">B+</span> <span class="num">3.3</span></div>
                        <div class="scale-chip"><span class="g-badge g-primary">B</span> <span class="num">3.0</span></div>
                        <div class="scale-chip"><span class="g-badge g-primary">B-</span> <span class="num">2.7</span></div>
                        <div class="scale-chip"><span class="g-badge g-warning">C+</span> <span class="num">2.3</span></div>
                        <div class="scale-chip"><span class="g-badge g-warning">C</span> <span class="num">2.0</span></div>
                        <div class="scale-chip"><span class="g-badge g-warning">C-</span> <span class="num">1.7</span></div>
                        <div class="scale-chip"><span class="g-badge g-danger">D+</span> <span class="num">1.3</span></div>
                        <div class="scale-chip"><span class="g-badge g-danger">D</span> <span class="num">1.0</span></div>
                        <div class="scale-chip"><span class="g-badge g-danger">F</span> <span class="num">0.0</span></div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>
</body>
</html>
