<?php
/**
 * Authentication Helper Functions
 */

require_once __DIR__ . '/../config.php';

// Require user to be logged in
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

// Require specific role
function requireRole($roles) {
    requireLogin();
    
    if (is_string($roles)) {
        $roles = [$roles];
    }
    
    if (!in_array($_SESSION['role'], $roles)) {
        alertRedirect('Access denied. You do not have permission to view this page.', 'dashboard.php');
    }
}

// Get employee data by user_id
function getEmployeeData($userId) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("
        SELECT e.*, d.name as department_name 
        FROM employees e 
        LEFT JOIN departments d ON e.department_id = d.id 
        WHERE e.user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Get student data by user_id
function getStudentData($userId) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("
        SELECT s.*, d.name as department_name 
        FROM students s 
        LEFT JOIN departments d ON s.department_id = d.id 
        WHERE s.user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Generate unique employee code
function generateEmployeeCode() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT MAX(id) as max_id FROM employees");
    $row = $result->fetch_assoc();
    $nextId = ($row['max_id'] ?? 0) + 1;
    return 'QIU-EMP-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
}

// Generate unique student code
function generateStudentCode() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT MAX(id) as max_id FROM students");
    $row = $result->fetch_assoc();
    $nextId = ($row['max_id'] ?? 0) + 1;
    return 'QIU-STU-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
}

// Upload file helper
function uploadFile($file, $destination, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf']) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error'];
    }
    
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExt, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    // Max 5MB
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'File too large (max 5MB)'];
    }
    
    $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
    $targetPath = $destination . '/' . $newFileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $newFileName];
    }
    
    return ['success' => false, 'message' => 'Failed to move file'];
}

// Get all departments
function getDepartments() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT * FROM departments ORDER BY name");
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>
