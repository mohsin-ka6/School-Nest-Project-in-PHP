<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!-- Sidebar -->
<div class="sidebar-custom sidebar-parent" id="sidebar-wrapper">
    <div class="sidebar-heading">
        <a href="<?php echo BASE_URL; ?>/parent/dashboard.php" class="text-white text-decoration-none d-flex align-items-center">
            <?php if (SITE_LOGO && file_exists(ROOT_PATH . '/' . SITE_LOGO)): ?>
                <img src="<?php echo BASE_URL . '/' . SITE_LOGO; ?>" alt="Logo" style="height: 30px; margin-right: 10px;">
            <?php else: ?>
                <i class="fas fa-school me-2"></i>
            <?php endif; ?>
            <span><?php echo SITE_NAME; ?></span>
        </a>
    </div>
    <div class="list-group list-group-flush">
        <li class="sidebar-item">
            <a href="<?php echo BASE_URL; ?>/parent/dashboard.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'dashboard.php' && $current_dir == 'parent') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </a>
        <a href="<?php echo BASE_URL; ?>/parent/my_children.php" class="list-group-item list-group-item-action <?php echo in_array($current_page, ['my_children.php', 'view_child_profile.php', 'view_results.php', 'view_report_card.php']) ? 'active' : ''; ?>">
            <i class="fas fa-child me-2"></i>My Children
        </a>
        <a href="<?php echo BASE_URL; ?>/parent/fees.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'fees.php') ? 'active' : ''; ?>">
            <i class="fas fa-money-check-alt me-2"></i>Fees
        </a>
        
        <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="list-group-item list-group-item-action text-danger mt-auto">
            <i class="fas fa-sign-out-alt me-2"></i>Logout
        </a>
    </div>
</div>