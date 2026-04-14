<?php
require_once '../config.php';
require_once '../includes/auth.php';

requireRole('employee');
$user = getCurrentUser();
$employee = getEmployeeData($user['id']);

// Get photo path
$photo = $user['photo'] ? '../uploads/photos/' . $user['photo'] : '../assets/images/default-avatar.svg';

// Get leave balance
$conn = getDBConnection();
$leave_stats = $conn->query("
    SELECT 
        SUM(CASE WHEN leave_type = 'vacation' AND status = 'approved' THEN DATEDIFF(end_date, start_date) + 1 ELSE 0 END) as vacation_used,
        SUM(CASE WHEN leave_type = 'sick' AND status = 'approved' THEN DATEDIFF(end_date, start_date) + 1 ELSE 0 END) as sick_used
    FROM leave_requests 
    WHERE user_id = {$user['id']}
")->fetch_assoc();

$vacation_balance = 21 - ($leave_stats['vacation_used'] ?? 0);
$sick_balance = 14 - ($leave_stats['sick_used'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard | QIU</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background: #f2f2f2;">

<div class="top-header">
    Qaiwan International University
</div>

<div class="wrapper">
    <div class="page-title">Employee Dashboard</div>
    
    <div class="layout">
        <!-- Main Content -->
        <div class="main-box">
            <!-- Profile Row -->
            <div class="profile-row">
                <div class="profile-photo">
                    <img src="<?php echo $photo; ?>" alt="Employee Photo">
                </div>
                <div class="profile-info">
                    <h3 style="margin-bottom: 10px;"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                    <p style="color: #666; margin-bottom: 10px;"><?php echo htmlspecialchars($employee['position'] ?? 'Employee'); ?> | <?php echo htmlspecialchars($employee['department_name'] ?? 'Department'); ?></p>
                    <a href="settings.php" class="profile-btn">My Profile</a>
                </div>
            </div>
            
            <!-- Leave Balance -->
            <div class="leave-balance">
                <strong>Leave Balances as of <?php echo date('m/d/Y'); ?></strong>
                <div class="leave-boxes">
                    <div class="leave-box">Vacation Leave <span><?php echo $vacation_balance; ?> days</span></div>
                    <div class="leave-box">Sick Leave <span><?php echo $sick_balance; ?> days</span></div>
                    <div class="leave-box">Holidays <span>12 days</span></div>
                </div>
            </div>
            
            <!-- Accordion Menu -->
            <div class="accordion">
                <div class="accordion-item">
                    <div class="accordion-header">
                        <a href="#">Pay Information</a>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <div class="accordion-content">
                        <div class="pay-row">
                            <strong>Latest Pay Stub:</strong> <?php echo date('m/d/Y', strtotime('-7 days')); ?>
                            <div class="pay-links">
                                <a href="#">All Pay Stubs</a>
                                <a href="#">Direct Deposit</a>
                                <a href="#">Deductions</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <div class="accordion-header">
                        <a href="#">Earnings</a>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <div class="accordion-header">
                        <a href="#">Benefits</a>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <div class="accordion-header">
                        <a href="#">Job Summary</a>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <div class="accordion-header">
                        <a href="#">Employee Summary</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Side Panel -->
        <div class="side-panel">
            <div class="side-header">
                <i class="fa-solid fa-wrench"></i> My Activities
            </div>
            <button class="primary-btn" onclick="window.location.href='leave_request.php'">Enter Leave Report</button>
            <div class="side-links">
                <a href="my_leaves.php">View My Leave Requests</a>
                <a href="settings.php">Update Profile</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
