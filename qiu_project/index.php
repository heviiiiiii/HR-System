<?php
/**
 * QIU Portal - Index Page
 * Redirects to login or dashboard based on session
 */
require_once 'config.php';

// If user is logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
} else {
    // Otherwise redirect to login
    redirect('login.php');
}
