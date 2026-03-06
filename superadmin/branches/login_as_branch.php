<?php
require_once '../../config.php';
require_once '../../functions.php';

// 1. Ensure only a logged-in Super Admin can use this.
check_role('superadmin');

$branch_id_to_view = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;

if ($branch_id_to_view > 0) {
    // 2. Check if the branch actually exists to prevent errors.
    $stmt = $db->prepare("SELECT id, name FROM branches WHERE id = ?");
    $stmt->execute([$branch_id_to_view]);
    $branch = $stmt->fetch();

    if ($branch) {
        // 3. Set the special session variables.
        $_SESSION['original_user_id'] = $_SESSION['user_id'];
        $_SESSION['view_as_branch_id'] = $branch['id'];
        $_SESSION['view_as_branch_name'] = $branch['name'];

        // 4. Redirect to the branch admin's dashboard.
        redirect(BASE_URL . '/branchadmin/dashboard.php');
    }
}

// If branch_id is invalid or not found, redirect back with an error.
$_SESSION['error_message'] = "Invalid branch specified.";
redirect('manage_branches.php');