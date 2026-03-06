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
<div class="sidebar-custom" id="sidebar-wrapper">
    <div class="sidebar-heading">
        <a href="<?php echo BASE_URL; ?>/branchadmin/dashboard.php" class="text-white text-decoration-none d-flex align-items-center">
            <?php if (SITE_LOGO && file_exists(ROOT_PATH . '/' . SITE_LOGO)): ?>
                <img src="<?php echo BASE_URL . '/' . SITE_LOGO; ?>" alt="Logo" style="height: 30px; margin-right: 10px;">
            <?php else: ?>
                <i class="fas fa-school me-2"></i>
            <?php endif; ?>
            <span class="fs-5"><?php echo SITE_NAME; ?></span>
        </a>
    </div>
    <ul class="list-unstyled sidebar-nav">
        <li class="sidebar-item">
            <a href="<?php echo BASE_URL; ?>/branchadmin/dashboard.php" class="sidebar-link <?php echo ($current_page == 'dashboard.php' && $current_dir == 'branchadmin') ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
        </li>

        <li class="sidebar-item">
            <a href="#frontoffice-submenu" data-bs-toggle="collapse" class="sidebar-link dropdown-toggle <?php echo $is_active('frontoffice'); ?>">
                <i class="fas fa-desktop me-2"></i>Front Office
            </a>
            <ul id="frontoffice-submenu" class="collapse list-unstyled sidebar-submenu <?php echo $is_open('frontoffice'); ?>">
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/frontoffice/admission_query.php" class="sidebar-link <?php echo ($current_page == 'admission_query.php') ? 'active' : ''; ?>">Admission Query</a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/frontoffice/visitor_book.php" class="sidebar-link <?php echo ($current_page == 'visitor_book.php') ? 'active' : ''; ?>">Visitor Book</a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/frontoffice/phone_log.php" class="sidebar-link <?php echo ($current_page == 'phone_log.php') ? 'active' : ''; ?>">Phone Log</a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/frontoffice/complaints.php" class="sidebar-link <?php echo ($current_page == 'complaints.php') ? 'active' : ''; ?>">Complaints</a></li>
            </ul>
        </li>

        <li class="sidebar-item">
            <a href="#academics-submenu" data-bs-toggle="collapse" class="sidebar-link dropdown-toggle <?php echo $is_active('academics'); ?>">
                <i class="fas fa-graduation-cap me-2"></i>Academics
            </a>
            <ul id="academics-submenu" class="collapse list-unstyled sidebar-submenu <?php echo $is_open('academics'); ?>">
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/academics/manage_classes.php" class="sidebar-link <?php echo in_array($current_page, ['manage_classes.php', 'manage_sections.php']) ? 'active' : ''; ?>">Classes & Sections</a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/academics/manage_subjects.php" class="sidebar-link <?php echo ($current_page == 'manage_subjects.php') ? 'active' : ''; ?>">Subjects</a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/academics/assign_subjects.php" class="sidebar-link <?php echo ($current_page == 'assign_subjects.php') ? 'active' : ''; ?>">Assign Subjects</a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/academics/assign_teachers.php" class="sidebar-link <?php echo ($current_page == 'assign_teachers.php') ? 'active' : ''; ?>">Assign Teachers</a></li>
            </ul>
        </li>

        <li class="sidebar-item">
            <a href="#students-submenu" data-bs-toggle="collapse" class="sidebar-link dropdown-toggle <?php echo $is_active('students'); ?>">
                <i class="fas fa-user-graduate me-2"></i>Students
            </a>
            <ul id="students-submenu" class="collapse list-unstyled sidebar-submenu <?php echo $is_open('students'); ?>">
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/students/add_student.php" class="sidebar-link <?php echo ($current_page == 'add_student.php') ? 'active' : ''; ?>">Add Student</a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/students/manage_students.php" class="sidebar-link <?php echo in_array($current_page, ['manage_students.php', 'view_student.php', 'edit_student.php']) ? 'active' : ''; ?>">Manage Students</a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/students/promote_student.php" class="sidebar-link <?php echo ($current_page == 'promote_student.php') ? 'active' : ''; ?>">Promote Students</a></li>
            </ul>
        </li>

        <li class="sidebar-item">
            <a href="#hr-submenu" data-bs-toggle="collapse" class="sidebar-link dropdown-toggle <?php echo $is_active('hr'); ?>">
                <i class="fas fa-sitemap me-2"></i>Human Resources
            </a>
            <ul id="hr-submenu" class="collapse list-unstyled sidebar-submenu <?php echo $is_open('hr'); ?>">
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/hr/staff_directory.php" class="sidebar-link <?php echo in_array($current_page, ['staff_directory.php', 'view_staff.php', 'edit_staff.php']) ? 'active' : ''; ?>">Staff Directory</a></li>
            </ul>
        </li>

        <li class="sidebar-item"><a href="#" class="sidebar-link"><i class="fas fa-money-check-alt me-2"></i>Fees</a></li>
        <li class="sidebar-item"><a href="#" class="sidebar-link"><i class="fas fa-user-clock me-2"></i>Attendance</a></li>
        <li class="sidebar-item"><a href="#" class="sidebar-link"><i class="fas fa-book me-2"></i>Exams</a></li>
        <li class="sidebar-item"><a href="#" class="sidebar-link"><i class="fas fa-bullhorn me-2"></i>Communication</a></li>

        <li class="sidebar-item mt-auto">
            <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="sidebar-link logout">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </li>
    </ul>
</div>