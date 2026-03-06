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
            <a href="<?php echo BASE_URL; ?>/branchadmin/dashboard.php" class="sidebar-link <?php echo ($current_page == 'dashboard.php' && $current_dir == 'branchadmin') ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span class="link-text">Dashboard</span>
            </a>
        </li>

        <li class="sidebar-item">
            <a href="#frontoffice-submenu" data-bs-toggle="collapse" class="sidebar-link dropdown-toggle <?php echo $is_active('frontoffice'); ?>">
                <i class="fas fa-desktop"></i>
                <span class="link-text">Front Office</span>
            </a>
            <ul id="frontoffice-submenu" class="collapse list-unstyled sidebar-submenu <?php echo $is_open('frontoffice'); ?>">
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/frontoffice/admission_query.php" class="sidebar-link <?php echo ($current_page == 'admission_query.php') ? 'active' : ''; ?>"><span class="link-text">Admission Query</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/frontoffice/visitor_book.php" class="sidebar-link <?php echo ($current_page == 'visitor_book.php') ? 'active' : ''; ?>"><span class="link-text">Visitor Book</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/frontoffice/phone_log.php" class="sidebar-link <?php echo ($current_page == 'phone_log.php') ? 'active' : ''; ?>"><span class="link-text">Phone Log</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/frontoffice/complaints.php" class="sidebar-link <?php echo ($current_page == 'complaints.php') ? 'active' : ''; ?>"><span class="link-text">Complaints</span></a></li>
            </ul>
        </li>

        <li class="sidebar-item">
            <a href="#academics-submenu" data-bs-toggle="collapse" class="sidebar-link dropdown-toggle <?php echo $is_active('academics'); ?>">
                <i class="fas fa-graduation-cap"></i>
                <span class="link-text">Academics</span>
            </a>
            <ul id="academics-submenu" class="collapse list-unstyled sidebar-submenu <?php echo $is_open('academics'); ?>">
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/academics/manage_classes.php" class="sidebar-link <?php echo in_array($current_page, ['manage_classes.php', 'manage_sections.php']) ? 'active' : ''; ?>"><span class="link-text">Classes & Sections</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/academics/manage_subjects.php" class="sidebar-link <?php echo ($current_page == 'manage_subjects.php') ? 'active' : ''; ?>"><span class="link-text">Subjects</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/academics/assign_subjects.php" class="sidebar-link <?php echo ($current_page == 'assign_subjects.php') ? 'active' : ''; ?>"><span class="link-text">Assign Subjects</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/academics/assign_teachers.php" class="sidebar-link <?php echo ($current_page == 'assign_teachers.php') ? 'active' : ''; ?>"><span class="link-text">Assign Teachers</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/academics/class_routine.php" class="sidebar-link <?php echo ($current_page == 'class_routine.php') ? 'active' : ''; ?>"><span class="link-text">Class Routine</span></a></li>
            </ul>
        </li>

        <li class="sidebar-item">
            <a href="#students-submenu" data-bs-toggle="collapse" class="sidebar-link dropdown-toggle <?php echo $is_active('students'); ?>">
                <i class="fas fa-user-graduate"></i>
                <span class="link-text">Students</span>
            </a>
            <ul id="students-submenu" class="collapse list-unstyled sidebar-submenu <?php echo $is_open('students'); ?>">
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/students/add_student.php" class="sidebar-link <?php echo ($current_page == 'add_student.php') ? 'active' : ''; ?>"><span class="link-text">Add Student</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/students/manage_students.php" class="sidebar-link <?php echo in_array($current_page, ['manage_students.php', 'view_student.php', 'edit_student.php']) ? 'active' : ''; ?>"><span class="link-text">Manage Students</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/students/promote_student.php" class="sidebar-link <?php echo ($current_page == 'promote_student.php') ? 'active' : ''; ?>"><span class="link-text">Promote Students</span></a></li>
            </ul>
        </li>

        <li class="sidebar-item">
            <a href="#hr-submenu" data-bs-toggle="collapse" class="sidebar-link dropdown-toggle <?php echo $is_active('hr'); ?>">
                <i class="fas fa-sitemap"></i>
                <span class="link-text">Human Resources</span>
            </a>
            <ul id="hr-submenu" class="collapse list-unstyled sidebar-submenu <?php echo $is_open('hr'); ?>">
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/hr/staff_directory.php" class="sidebar-link <?php echo in_array($current_page, ['staff_directory.php', 'view_staff.php', 'edit_staff.php']) ? 'active' : ''; ?>"><span class="link-text">Staff Directory</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/hr/id_card_generator.php" class="sidebar-link <?php echo ($current_page == 'id_card_generator.php') ? 'active' : ''; ?>"><span class="link-text">ID Card Generator</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/hr/manage_parents.php" class="sidebar-link <?php echo in_array($current_page, ['manage_parents.php', 'view_parent.php']) ? 'active' : ''; ?>"><span class="link-text">Manage Parents</span></a></li>
            </ul>
        </li>

        <li class="sidebar-item">
            <a href="#fees-submenu" data-bs-toggle="collapse" class="sidebar-link dropdown-toggle <?php echo $is_active('fees'); ?>">
                <i class="fas fa-money-check-alt"></i>
                <span class="link-text">Fees</span>
            </a>
            <ul id="fees-submenu" class="collapse list-unstyled sidebar-submenu <?php echo $is_open('fees'); ?>">
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/fees/fee_groups.php" class="sidebar-link <?php echo ($current_page == 'fee_groups.php') ? 'active' : ''; ?>"><span class="link-text">Fee Groups</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/fees/fee_types.php" class="sidebar-link <?php echo ($current_page == 'fee_types.php') ? 'active' : ''; ?>"><span class="link-text">Fee Types</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/fees/concession_types.php" class="sidebar-link <?php echo ($current_page == 'concession_types.php') ? 'active' : ''; ?>"><span class="link-text">Concession Types</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/fees/assign_concessions.php" class="sidebar-link <?php echo ($current_page == 'assign_concessions.php') ? 'active' : ''; ?>"><span class="link-text">Assign Concessions</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/fees/fee_structure.php" class="sidebar-link <?php echo ($current_page == 'fee_structure.php') ? 'active' : ''; ?>"><span class="link-text">Fee Structure</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/fees/generate_invoice.php" class="sidebar-link <?php echo ($current_page == 'generate_invoice.php') ? 'active' : ''; ?>"><span class="link-text">Generate Invoice</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/fees/manage_invoices.php" class="sidebar-link <?php echo ($current_page == 'manage_invoices.php') ? 'active' : ''; ?>"><span class="link-text">Manage Invoices</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/fees/quick_collect.php" class="sidebar-link <?php echo ($current_page == 'quick_collect.php') ? 'active' : ''; ?>"><span class="link-text">Quick Collect</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/fees/payment_history.php" class="sidebar-link <?php echo in_array($current_page, ['payment_history.php', 'collect_fees.php']) ? 'active' : ''; ?>"><span class="link-text">Payment History</span></a></li>
            </ul>
        </li>

        <li class="sidebar-item">
            <a href="#comm-submenu" data-bs-toggle="collapse" class="sidebar-link dropdown-toggle <?php echo $is_active('communications'); ?>">
                <i class="fas fa-bullhorn"></i>
                <span class="link-text">Communication</span>
            </a>
            <ul id="comm-submenu" class="collapse list-unstyled sidebar-submenu <?php echo $is_open('communications'); ?>">
                <li><a href="<?php echo BASE_URL; ?>/manage_news.php" class="sidebar-link <?php echo ($current_page == 'manage_news.php') ? 'active' : ''; ?>"><span class="link-text">News & Events</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/communications/invitation_maker.php" class="sidebar-link <?php echo ($current_page == 'invitation_maker.php') ? 'active' : ''; ?>"><span class="link-text">Invitation Maker</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/communications/manage_invitation_templates.php" class="sidebar-link <?php echo ($current_page == 'manage_invitation_templates.php') ? 'active' : ''; ?>"><span class="link-text">Invitation Templates</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/communications/send_sms.php" class="sidebar-link <?php echo ($current_page == 'send_sms.php') ? 'active' : ''; ?>"><span class="link-text">Send SMS</span></a></li>
            </ul>
        </li>

        <li class="sidebar-item">
            <a href="#" class="sidebar-link"><i class="fas fa-user-clock"></i><span class="link-text">Attendance</span></a>
        </li>

        <li class="sidebar-item">
            <a href="#exams-submenu" data-bs-toggle="collapse" class="sidebar-link dropdown-toggle <?php echo $is_active('exams'); ?>">
                <i class="fas fa-book-open"></i>
                <span class="link-text">Exams</span>
            </a>
            <ul id="exams-submenu" class="collapse list-unstyled sidebar-submenu <?php echo $is_open('exams'); ?>">
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/exams/exam_types.php" class="sidebar-link <?php echo ($current_page == 'exam_types.php') ? 'active' : ''; ?>"><span class="link-text">Exam Types</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/exams/marks_grade.php" class="sidebar-link <?php echo ($current_page == 'marks_grade.php') ? 'active' : ''; ?>"><span class="link-text">Marks Grade</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/exams/exam_schedule.php" class="sidebar-link <?php echo ($current_page == 'exam_schedule.php') ? 'active' : ''; ?>"><span class="link-text">Exam Schedule</span></a></li>
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/exams/marks_register.php" class="sidebar-link <?php echo ($current_page == 'marks_register.php') ? 'active' : ''; ?>"><span class="link-text">Marks Register</span></a></li>
            </ul>
        </li>

        <li class="sidebar-item">
            <a href="#settings-submenu" data-bs-toggle="collapse" class="sidebar-link dropdown-toggle <?php echo $is_active('settings'); ?>">
                <i class="fas fa-cogs"></i>
                <span class="link-text">Settings</span>
            </a>
            <ul id="settings-submenu" class="collapse list-unstyled sidebar-submenu <?php echo $is_open('settings'); ?>">
                <li><a href="<?php echo BASE_URL; ?>/branchadmin/settings/email_settings.php" class="sidebar-link <?php echo ($current_page == 'email_settings.php') ? 'active' : ''; ?>"><span class="link-text">Email Settings</span></a></li>
            </ul>
        </li>

        <li class="sidebar-item logout-section">
            <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="sidebar-link logout">
                <i class="fas fa-sign-out-alt"></i>
                <span class="link-text">Logout</span>
            </a>
        </li>
    </ul>
</div>