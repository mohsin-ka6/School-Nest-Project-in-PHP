<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

$is_active = function($dirs) use ($current_dir) {
    return in_array($current_dir, (array)$dirs) ? 'active' : '';
};
$is_open = function($dirs) use ($current_dir) {
    return in_array($current_dir, (array)$dirs) ? 'show' : '';
};
?>
<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-heading">
        <div class="logo-container">
            <div class="logo">
                <?php if (SITE_LOGO && file_exists(ROOT_PATH . '/' . SITE_LOGO)): ?>
                    <img src="<?php echo BASE_URL . '/' . SITE_LOGO; ?>" alt="Logo" style="height: 25px; width: auto;">
                <?php else: ?>
                    <i class="fas fa-school"></i>
                <?php endif; ?>
            </div>
            <div class="school-name"><?php echo SITE_NAME; ?></div>
        </div>
    </div>
    <ul class="list-unstyled sidebar-nav">
        <li class="sidebar-item">
            <a href="<?php echo BASE_URL; ?>/superadmin/dashboard.php" class="sidebar-link <?php echo ($current_page == 'dashboard.php' && $current_dir == 'superadmin') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i><span class="link-text">Dashboard</span></a>
        </li>
        <li class="sidebar-item">
            <a href="<?php echo BASE_URL; ?>/superadmin/branches/manage_branches.php" class="sidebar-link <?php echo $is_active('branches'); ?>"><i class="fas fa-sitemap"></i><span class="link-text">Manage Branches</span></a>
        </li>
        <li class="sidebar-item">
            <a href="<?php echo BASE_URL; ?>/superadmin/admins/view_admins.php" class="sidebar-link <?php echo $is_active('admins'); ?>"><i class="fas fa-user-shield"></i><span class="link-text">Manage Admins</span></a>
        </li>
        <li class="sidebar-item">
            <a href="<?php echo BASE_URL; ?>/superadmin/manage_sessions.php" class="sidebar-link <?php echo ($current_page == 'manage_sessions.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i><span class="link-text">Academic Sessions</span></a>
        </li>

        <li class="sidebar-item">
            <a href="#students-submenu" data-bs-toggle="collapse" class="sidebar-link dropdown-toggle <?php echo $is_active('students'); ?>">
                <i class="fas fa-user-graduate"></i><span class="link-text">Students</span>
            </a>
            <ul id="students-submenu" class="collapse list-unstyled sidebar-submenu <?php echo $is_open('students'); ?>">
                <li><a href="<?php echo BASE_URL; ?>/superadmin/students/manage_all_students.php" class="sidebar-link <?php echo in_array($current_page, ['manage_all_students.php', 'view_student.php', 'transfer_student.php']) ? 'active' : ''; ?>"><span class="link-text">All Students</span></a></li>
            </ul>
        </li>

        <li class="sidebar-item">
            <a href="#security-submenu" data-bs-toggle="collapse" class="sidebar-link dropdown-toggle <?php echo $is_active('security'); ?>">
                <i class="fas fa-shield-alt"></i><span class="link-text">Security</span>
            </a>
            <ul id="security-submenu" class="collapse list-unstyled sidebar-submenu <?php echo $is_open('security'); ?>">
                <li><a href="<?php echo BASE_URL; ?>/superadmin/security/activity_log.php" class="sidebar-link <?php echo ($current_page == 'activity_log.php') ? 'active' : ''; ?>"><span class="link-text">Activity Log</span></a></li>
            </ul>
        </li>

        <li class="sidebar-item">
            <a href="#settings-submenu" data-bs-toggle="collapse" class="sidebar-link dropdown-toggle <?php echo $is_active(['settings', 'backup']); ?>">
                <i class="fas fa-cogs"></i><span class="link-text">Settings</span>
            </a>
            <ul id="settings-submenu" class="collapse list-unstyled sidebar-submenu <?php echo $is_open(['settings', 'backup']); ?>">
                <li><a href="<?php echo BASE_URL; ?>/superadmin/settings/general_settings.php" class="sidebar-link <?php echo ($current_page == 'general_settings.php') ? 'active' : ''; ?>"><span class="link-text">General Settings</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/superadmin/settings/email_settings.php" class="sidebar-link <?php echo ($current_page == 'email_settings.php') ? 'active' : ''; ?>"><span class="link-text">Email Settings</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/superadmin/settings/manage_content.php" class="sidebar-link <?php echo ($current_page == 'manage_content.php') ? 'active' : ''; ?>"><span class="link-text">Public Content</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/superadmin/backup/index.php" class="sidebar-link <?php echo ($current_dir == 'backup') ? 'active' : ''; ?>"><span class="link-text">Backup & Restore</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/superadmin/settings/manage_gallery.php" class="sidebar-link <?php echo ($current_page == 'manage_gallery.php') ? 'active' : ''; ?>"><span class="link-text">Photo Gallery</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/manage_news.php" class="sidebar-link <?php echo ($current_page == 'manage_news.php') ? 'active' : ''; ?>"><span class="link-text">News & Events</span></a></li>
            </ul>
        </li>

        <li class="sidebar-item logout-section">
            <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="sidebar-link logout">
                <i class="fas fa-sign-out-alt"></i><span class="link-text">Logout</span>
            </a>
        </li>
    </ul>
</div>