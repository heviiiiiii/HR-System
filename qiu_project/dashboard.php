<?php
/**
 * Dashboard Router
 * Redirects users to their role-specific dashboard
 */
require_once 'config.php';
require_once 'includes/auth.php';

requireLogin();

$role = $_SESSION['role'];

switch ($role) {
    case 'admin':
        redirect('admin/dashboard.php');
        break;
    case 'hr':
        redirect('admin/dashboard.php'); // HR uses admin dashboard
        break;
    case 'employee':
        redirect('employee/dashboard.php');
        break;
    case 'student':
        redirect('student/dashboard.php');
        break;
    default:
        redirect('login.php');
}
?>
