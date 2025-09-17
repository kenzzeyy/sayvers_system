<?php
// Logout functionality for SAYVERS Management System
require_once '../../config/config.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Log the logout action
    logAuditTrail('admin_users', $_SESSION['admin_id'], 'LOGOUT');
    
    // Clear all session data
    session_destroy();
    
    // Redirect to login page with success message
    header('Location: login.php?logout=success');
    exit;
} else {
    // If not logged in, redirect to login
    header('Location: login.php');
    exit;
}
?>