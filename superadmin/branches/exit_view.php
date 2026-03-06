<?php
require_once '../../config.php';
require_once '../../functions.php';

// Ensure only a logged-in user can access this
check_role('superadmin');

// Unset the special session variables to return to normal
unset($_SESSION['view_as_branch_id']);
unset($_SESSION['view_as_branch_name']);
unset($_SESSION['original_user_id']);

// Redirect back to the superadmin dashboard
redirect(BASE_URL . '/superadmin/dashboard.php');