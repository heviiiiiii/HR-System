<?php
/**
 * Registration Page
 */
require_once 'config.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';
$departments = getDepartments();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = sanitize($_POST['role'] ?? 'employee');
    $departmentId = intval($_POST['department_id'] ?? 0);
    
    // For employees
    $position = sanitize($_POST['position'] ?? '');
    
    // For students
    $program = sanitize($_POST['program'] ?? '');
    
    // Validation
    if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else {
        $conn = getDBConnection();
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email already registered';
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (email, password, role, full_name, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $email, $hashedPassword, $role, $fullName, $phone);
            
            if ($stmt->execute()) {
                $userId = $conn->insert_id;
                
                // Insert role-specific data
                if ($role === 'employee' || $role === 'hr') {
                    $employeeCode = generateEmployeeCode();
                    $stmt2 = $conn->prepare("INSERT INTO employees (user_id, employee_code, department_id, position, hire_date) VALUES (?, ?, ?, ?, CURDATE())");
                    $stmt2->bind_param("isis", $userId, $employeeCode, $departmentId, $position);
                    $stmt2->execute();
                } elseif ($role === 'student') {
                    $studentCode = generateStudentCode();
                    $stmt2 = $conn->prepare("INSERT INTO students (user_id, student_code, department_id, program, enrollment_date) VALUES (?, ?, ?, ?, CURDATE())");
                    $stmt2->bind_param("isis", $userId, $studentCode, $departmentId, $program);
                    $stmt2->execute();
                }
                
                $success = 'Registration successful! You can now login.';
            } else {
                $error = 'Registration failed. Please try again.';
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
    <title>Register | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-container { width: 380px; }
        .role-fields { display: none; }
        .role-fields.active { display: block; }
    </style>
</head>
<body class="auth-page">
    <div class="bg-blur"></div>
    
    <div class="auth-logo">
        <img src="assets/images/qiu.svg" alt="University Logo">
    </div>
    
    <div class="auth-container">
        <h2>Create Account</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="text" name="full_name" placeholder="Full Name *" required
                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
            
            <input type="email" name="email" placeholder="Email *" required
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            
            <input type="tel" name="phone" placeholder="Phone Number"
                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            
            <select name="role" id="role" required onchange="toggleRoleFields()">
                <option value="employee">Register as Employee</option>
                <option value="student">Register as Student</option>
            </select>
            
            <select name="department_id" required>
                <option value="">-- Select Department --</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                <?php endforeach; ?>
            </select>
            
            <!-- Employee Fields -->
            <div id="employee-fields" class="role-fields active">
                <input type="text" name="position" placeholder="Position (e.g., Lecturer)">
            </div>
            
            <!-- Student Fields -->
            <div id="student-fields" class="role-fields">
                <select name="program">
                    <option value="">-- Select Program --</option>
                    <option value="Undergraduate">Undergraduate</option>
                    <option value="Graduate">Graduate</option>
                    <option value="PhD">PhD</option>
                </select>
            </div>
            
            <input type="password" name="password" placeholder="Password (min 6 chars) *" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password *" required>
            
            <button type="submit" class="login-btn">Create Account</button>
        </form>
        
        <p class="auth-link">
            Already have an account? <a href="login.php"><strong>Sign in</strong></a>
        </p>
    </div>
    
    <footer class="auth-footer">
        <div class="footer-line"></div>
        <div class="footer-content">
            <div class="footer-left">
                <span>Follow Us On</span>
                <a href="https://www.facebook.com/qaiwanuniversity" target="_blank">
                    <i class="fa-brands fa-facebook-f"></i> Facebook
                </a>
                <a href="https://www.instagram.com/qaiwanuniversity" target="_blank">
                    <i class="fa-brands fa-instagram"></i> Instagram
                </a>
            </div>
            <div class="footer-right">
                <span>+964 772 141 1414</span>
                <span>|</span>
                <span>info@uniq.edu.iq</span>
            </div>
        </div>
    </footer>
    
    <script>
        function toggleRoleFields() {
            const role = document.getElementById('role').value;
            document.getElementById('employee-fields').classList.remove('active');
            document.getElementById('student-fields').classList.remove('active');
            
            if (role === 'employee' || role === 'hr') {
                document.getElementById('employee-fields').classList.add('active');
            } else if (role === 'student') {
                document.getElementById('student-fields').classList.add('active');
            }
        }
    </script>
</body>
</html>
