<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!-- Sidebar -->
<div class="sidebar-custom" id="sidebar-wrapper">
    <div class="sidebar-heading">
        <a href="<?php echo BASE_URL; ?>/superadmin/dashboard.php" class="text-white text-decoration-none d-flex align-items-center">
            <?php if (SITE_LOGO && file_exists(ROOT_PATH . '/' . SITE_LOGO)): ?>
                <img src="<?php echo BASE_URL . '/' . SITE_LOGO; ?>" alt="Logo" style="height: 30px; margin-right: 10px;">
            <?php else: ?>
                <i class="fas fa-school me-2"></i>
            <?php endif; ?>
            <span><?php echo SITE_NAME; ?></span>
        </a>
    </div>
    <div class="list-group list-group-flush">
        <a href="<?php echo BASE_URL; ?>/superadmin/dashboard.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'dashboard.php' && $current_dir == 'superadmin') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </a>

        <a href="<?php echo BASE_URL; ?>/superadmin/branches/manage_branches.php" class="list-group-item list-group-item-action <?php echo ($current_dir == 'branches') ? 'active' : ''; ?>">
            <i class="fas fa-sitemap me-2"></i>Manage Branches
        </a>
        <a href="<?php echo BASE_URL; ?>/superadmin/admins/view_admins.php" class="list-group-item list-group-item-action <?php echo ($current_dir == 'admins') ? 'active' : ''; ?>">
            <i class="fas fa-user-shield me-2"></i>Manage Admins
        </a>
        <a href="<?php echo BASE_URL; ?>/superadmin/students/manage_all_students.php" class="list-group-item list-group-item-action <?php echo ($current_dir == 'students') ? 'active' : ''; ?>">
            <i class="fas fa-users me-2"></i>Manage Students
        </a>
        <a href="<?php echo BASE_URL; ?>/superadmin/manage_sessions.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'manage_sessions.php') ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt me-2"></i>Academic Sessions
        </a>

        <!-- Communication Dropdown -->
        <a href="#comm-submenu" data-bs-toggle="collapse" class="list-group-item list-group-item-action dropdown-toggle <?php echo ($current_dir == 'communications') ? 'active' : ''; ?>">
            <i class="fas fa-bullhorn me-2"></i>Communication
        </a>
        <div class="collapse <?php echo ($current_dir == 'communications') ? 'show' : ''; ?>" id="comm-submenu">
            <a href="<?php echo BASE_URL; ?>/superadmin/communications/manage_news.php" class="list-group-item list-group-item-action ps-5 <?php echo ($current_page == 'manage_news.php') ? 'active' : ''; ?>">News & Events</a>
        </div>

        <!-- Settings Dropdown -->
        <a href="#settings-submenu" data-bs-toggle="collapse" class="list-group-item list-group-item-action dropdown-toggle <?php echo in_array($current_dir, ['settings', 'backup']) ? 'active' : ''; ?>">
            <i class="fas fa-cogs me-2"></i>Settings
        </a>
        <div class="collapse <?php echo in_array($current_dir, ['settings', 'backup']) ? 'show' : ''; ?>" id="settings-submenu">
            <a href="<?php echo BASE_URL; ?>/superadmin/settings/general_settings.php" class="list-group-item list-group-item-action ps-5 <?php echo ($current_page == 'general_settings.php') ? 'active' : ''; ?>">General Settings</a>
            <a href="<?php echo BASE_URL; ?>/superadmin/settings/manage_content.php" class="list-group-item list-group-item-action ps-5 <?php echo ($current_page == 'manage_content.php') ? 'active' : ''; ?>">Manage Public Content</a>
            <a href="<?php echo BASE_URL; ?>/superadmin/settings/manage_gallery.php" class="list-group-item list-group-item-action ps-5 <?php echo ($current_page == 'manage_gallery.php') ? 'active' : ''; ?>">Manage Photo Gallery</a>
            <a href="<?php echo BASE_URL; ?>/superadmin/backup/index.php" class="list-group-item list-group-item-action ps-5 <?php echo ($current_dir == 'backup') ? 'active' : ''; ?>">Backup & Restore</a>
            <a href="<?php echo BASE_URL; ?>/superadmin/settings/manage_gallery.php" class="list-group-item list-group-item-action ps-5 <?php echo ($current_page == 'manage_gallery.php') ? 'active' : ''; ?>">Manage Photo Gallery</a>
            <a href="<?php echo BASE_URL; ?>/superadmin/settings/manage_news.php" class="list-group-item list-group-item-action ps-5 <?php echo ($current_page == 'manage_news.php') ? 'active' : ''; ?>">Manage News & Events</a>
        </div>

        <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="list-group-item list-group-item-action text-danger mt-auto">
            <i class="fas fa-sign-out-alt me-2"></i>Logout
        </a>
    </div>
</div>