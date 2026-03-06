<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!-- Sidebar -->
<div class="sidebar-teacher" id="sidebar-wrapper">
    <div class="sidebar-heading">
        <a href="<?php echo BASE_URL; ?>/teacher/dashboard.php" class="text-white text-decoration-none d-flex align-items-center">
            <?php if (!empty($_SESSION['branch_logo']) && file_exists(ROOT_PATH . '/' . $_SESSION['branch_logo'])): ?>
                <img src="<?php echo BASE_URL . '/' . $_SESSION['branch_logo']; ?>" alt="Logo" style="height: 30px; margin-right: 10px;">
            <?php elseif (SITE_LOGO && file_exists(ROOT_PATH . '/' . SITE_LOGO)): ?>
                <img src="<?php echo BASE_URL . '/' . SITE_LOGO; ?>" alt="Logo" style="height: 30px; margin-right: 10px;">
            <?php else: ?>
                <i class="fas fa-school me-2"></i>
            <?php endif; ?>
            <span><?php echo SITE_NAME; ?></span>
        </a>
    </div>
    <div class="list-group list-group-flush">
        <a href="<?php echo BASE_URL; ?>/teacher/dashboard.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </a>
        <a href="<?php echo BASE_URL; ?>/teacher/manage_attendance.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'manage_attendance.php') ? 'active' : ''; ?>">
            <i class="fas fa-user-clock me-2"></i>Manage Attendance
        </a>
        <a href="<?php echo BASE_URL; ?>/teacher/enter_marks.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'enter_marks.php') ? 'active' : ''; ?>">
            <i class="fas fa-edit me-2"></i>Enter Marks
        </a>
        <a href="#" class="list-group-item list-group-item-action">
            <i class="fas fa-calendar-alt me-2"></i>View Routine
        </a>
        <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="list-group-item list-group-item-action text-danger mt-auto">
            <i class="fas fa-sign-out-alt me-2"></i>Logout
        </a>
    </div>
</div>