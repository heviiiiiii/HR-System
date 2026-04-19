<?php
/**
 * Login Page
 * QIU Portal - Qaiwan International University
 */
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    // Redirect based on role
    $role = $_SESSION['role'] ?? '';
    switch ($role) {
        case 'admin':
            redirect('admin/dashboard.php');
            break;
        case 'hr':
            redirect('hr/dashboard.php');
            break;
        case 'employee':
            redirect('employee/dashboard.php');
            break;
        case 'student':
            redirect('student/dashboard.php');
            break;
        default:
            redirect('dashboard.php');
            break;
    }
}

$error = '';

// Check for error messages
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'access_denied') {
        $error = 'Access denied. You do not have permission to view that page.';
    } elseif ($_GET['error'] === 'invalid_role') {
        $error = 'Invalid user role. Please contact administrator.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("SELECT id, email, password, role, full_name, photo FROM users WHERE email = ? AND status = 'active'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Plain text password comparison
            if ($password === $user['password']) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['photo'] = $user['photo'];
                
                // Redirect based on user role
                switch ($user['role']) {
                    case 'admin':
                        redirect('admin/dashboard.php');
                        break;
                    case 'hr':
                        redirect('hr/dashboard.php');
                        break;
                    case 'employee':
                        redirect('employee/dashboard.php');
                        break;
                    case 'student':
                        redirect('student/dashboard.php');
                        break;
                    default:
                        redirect('dashboard.php');
                        break;
                }
            } else {
                $error = 'Invalid email or password';
            }
        } else {
            $error = 'Invalid email or password';
        }
        
        $stmt->close();
    }
}
?
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="bg-blur"></div>
    
    <div class="auth-logo">
        <img src="assets/images/qiu.svg" alt="University Logo">
    </div>
    
    <div class="auth-container">
        <h2>NEW VERSION LOGIN</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Email" required 
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            <input type="password" name="password" placeholder="Password" required>
            
            <a href="#" class="forgot-link">Forgot your password?</a>
            
            <button type="submit" class="login-btn">Login</button>
        </form>
        
        <p class="auth-or">or continue with</p>
        
        <div class="social-buttons">
            <button type="button" class="btn-google" onclick="window.open('https://accounts.google.com/signin', '_blank')">
                <i class="fa-brands fa-google"></i> Google
            </button>
            <button type="button" class="btn-github" onclick="window.open('https://github.com/login', '_blank')">
                <i class="fa-brands fa-github"></i> GitHub
            </button>
        </div>
        
        <p class="auth-link">
            Don't have an account?
            <a href="register.php"><strong>Register for free</strong></a>
        </p>
        
        <p class="auth-link" style="margin-top: 10px;">
            <a href="careers.php"><i class="fas fa-briefcase"></i> View Job Openings</a>
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
</body>
</html>
