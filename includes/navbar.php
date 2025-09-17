<?php
// Navigation bar
$current_page = basename($_SERVER['PHP_SELF']);
$current_module = '';

// Determine current module from the path
$path_parts = explode('/', $_SERVER['REQUEST_URI']);
foreach ($path_parts as $part) {
    if (in_array($part, ['members', 'finance', 'audit'])) {
        $current_module = $part;
        break;
    }
}

function isActive($module, $current_module, $current_page) {
    if ($module === 'dashboard' && ($current_page === 'index.php' && empty($current_module))) {
        return 'active';
    }
    return ($current_module === $module) ? 'active' : '';
}
?>

<nav class="navbar">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 1rem; width: 100%;">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>index.php" 
                   class="nav-link <?php echo isActive('dashboard', $current_module, $current_page); ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>modules/members/index.php" 
                   class="nav-link <?php echo isActive('members', $current_module, $current_page); ?>">
                    <i class="fas fa-users"></i> Members
                </a>
            </li>
            
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>modules/finance/index.php" 
                   class="nav-link <?php echo isActive('finance', $current_module, $current_page); ?>">
                    <i class="fas fa-money-bill-wave"></i> Finance
                </a>
            </li>
            
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>modules/audit/index.php" 
                   class="nav-link <?php echo isActive('audit', $current_module, $current_page); ?>">
                    <i class="fas fa-history"></i> Audit Trail
                </a>
            </li>
        </ul>
    </div>
</nav>

<!-- Breadcrumb Navigation -->
<div style="background: #f8f9fa; border-bottom: 1px solid #dee2e6; padding: 0.75rem 0;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 1rem;">
        <nav aria-label="breadcrumb">
            <ol style="display: flex; list-style: none; margin: 0; padding: 0; font-size: 0.9rem;">
                <li style="margin-right: 0.5rem;">
                    <a href="<?php echo BASE_URL; ?>index.php" style="color: #6c757d; text-decoration: none;">
                        <i class="fas fa-home"></i> Home
                    </a>
                </li>
                
                <?php if (!empty($current_module)): ?>
                    <li style="margin: 0 0.5rem; color: #6c757d;">/</li>
                    <li style="margin-right: 0.5rem;">
                        <a href="<?php echo BASE_URL; ?>modules/<?php echo $current_module; ?>/index.php" 
                           style="color: #6c757d; text-decoration: none; text-transform: capitalize;">
                            <?php echo ucfirst($current_module); ?>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if (isset($page_title) && $page_title !== 'Dashboard'): ?>
                    <li style="margin: 0 0.5rem; color: #6c757d;">/</li>
                    <li style="color: #495057; font-weight: 500;">
                        <?php echo $page_title; ?>
                    </li>
                <?php endif; ?>
            </ol>
        </nav>
    </div>
</div>