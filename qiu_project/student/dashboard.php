<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireRole('student');
$user = getCurrentUser();
$student = getStudentData($user['id']);

// Get photo path
$photo = $user['photo'] ? '../uploads/photos/' . $user['photo'] : '../assets/images/default-avatar.svg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | QIU</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dashboard">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">QAIWAN INTERNATIONAL<br>UNIVERSITY</div>
        
        <div class="profile-box">
            <img src="<?php echo $photo; ?>" alt="Student Photo">
            <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
            <span><?php echo htmlspecialchars($student['department_name'] ?? 'Student'); ?></span>
        </div>
        
        <ul class="nav-menu">
            <li><a href="dashboard.php" class="active"><i class="fa-solid fa-house"></i> <span>Dashboard</span></a></li>
            <li><a href="courses.php"><i class="fa-solid fa-book-open"></i> <span>My Courses</span></a></li>
            <li><a href="assignments.php"><i class="fa-solid fa-file-lines"></i> <span>Assignments</span></a></li>
            <li><a href="grades.php"><i class="fa-solid fa-user-graduate"></i> <span>Grades</span></a></li>
            <li><a href="settings.php"><i class="fa-solid fa-gear"></i> <span>Settings</span></a></li>
            <li class="logout"><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    
    <!-- Main -->
    <div class="main-content">
        <div class="topbar">
            <h1>Student Dashboard</h1>
        </div>
        
        <div class="content">
            <div class="page-header">
                <h2>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
            </div>
            
            <!-- Cards -->
            <div class="cards-grid">
                <a href="courses.php" class="card-link">
                    <div class="card card-stat">
                        <h3>Enrolled Courses</h3>
                        <p>5</p>
                    </div>
                </a>
                
                <a href="assignments.php" class="card-link">
                    <div class="card card-stat">
                        <h3>Pending Assignments</h3>
                        <p>3</p>
                    </div>
                </a>
                
                <a href="grades.php" class="card-link">
                    <div class="card card-stat">
                        <h3>Current GPA</h3>
                        <p>3.75</p>
                    </div>
                </a>
                
                <div class="card card-stat">
                    <h3>Attendance</h3>
                    <p>92%</p>
                </div>
            </div>
            
            <!-- Sections -->
            <div class="sections">
                <div class="card">
                    <div class="card-header">
                        <h3>Upcoming Assignments</h3>
                    </div>
                    <table>
                        <tr>
                            <th>Course</th>
                            <th>Assignment</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                        <tr>
                            <td>CS301</td>
                            <td>Web Project Phase 2</td>
                            <td>Feb 5, 2026</td>
                            <td><span class="status pending">Pending</span></td>
                        </tr>
                        <tr>
                            <td>CS305</td>
                            <td>Database Design</td>
                            <td>Feb 8, 2026</td>
                            <td><span class="status pending">Pending</span></td>
                        </tr>
                        <tr>
                            <td>CS310</td>
                            <td>UML Diagrams</td>
                            <td>Feb 10, 2026</td>
                            <td><span class="status ongoing">In Progress</span></td>
                        </tr>
                    </table>
                </div>
                
                <div>
                    <div class="notice">
                        <h4>Course Update</h4>
                        <p>New materials uploaded for CS301 - Web Development.</p>
                    </div>
                    
                    <div class="notice">
                        <h4>Exam Schedule</h4>
                        <p>Midterm examinations start February 15, 2026.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
