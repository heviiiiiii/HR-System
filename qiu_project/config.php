<?php
/**
 * QIU Portal - Configuration File
 * Database connection and helper functions
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'qiu_portal');

// Site Configuration
define('SITE_NAME', 'Qaiwan International University');
define('SITE_URL', 'http://localhost/qiu_project');

// Create database connection
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
    }
    
    return $conn;
}

// Sanitize input
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user role
function hasRole($role) {
    if (!isLoggedIn()) return false;
    if (is_array($role)) {
        return in_array($_SESSION['role'], $role);
    }
    return $_SESSION['role'] === $role;
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Alert and redirect
function alertRedirect($message, $url) {
    echo "<script>alert('$message'); window.location.href='$url';</script>";
    exit();
}

// Get current user data
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $conn = getDBConnection();
    $userId = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Format date
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Get photo path
function getPhotoPath($photo) {
    if (empty($photo) || $photo === 'default.jpg') {
        return 'assets/images/default.jpg';
    }
    return 'uploads/photos/' . $photo;
}
?>
