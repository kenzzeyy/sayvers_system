<?php
// Common header for all pages
if (!defined('SYSTEM_NAME')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

// Require login for all pages that include this header
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SYSTEM_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/logo.png">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Additional page-specific styles -->
    <?php if (isset($additional_styles)): ?>
        <?php foreach ($additional_styles as $style): ?>
            <link rel="stylesheet" href="<?php echo $style; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo-section">
                <img src="<?php echo BASE_URL; ?>assets/images/logo.png" alt="SAYVERS Logo" class="logo">
                <div class="org-info">
                    <h1><?php echo ORGANIZATION_SHORT; ?></h1>
                    <p>Management System</p>
                </div>
            </div>
            <div class="user-info">
                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span><br>
                <small><i class="fas fa-clock"></i> <?php echo formatDateTime(date('Y-m-d H:i:s')); ?></small><br>
                <a href="<?php echo BASE_URL; ?>modules/auth/logout.php" class="btn btn-sm btn-secondary" style="margin-top: 0.5rem;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>
    
    <!-- Navigation -->
    <?php include 'navbar.php'; ?>
    
    <!-- Alert Container -->
    <div id="alert-container"></div>
    
    <!-- Main Content -->
    <main>