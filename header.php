<?php
// This header is included on all authenticated pages.
// It assumes that config.php and functions.php have already been included.

// Security check: Ensure user is logged in.
if (!is_logged_in()) {
    redirect(BASE_URL . '/auth/login.php');
}

// Session Hijacking check: Validate the user agent.
if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
    session_unset();
    session_destroy();
    redirect(BASE_URL . '/auth/login.php?error=session_hijacked');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
<div id="wrapper">
    <!-- Sidebar will be included by the specific page -->
    
    <!-- Main Content Wrapper (this will contain navbar and page content) -->
    <div class="main-content" id="mainContent">
        <?php require_once 'navbar.php'; ?>

    <?php
    // Display a banner if a superadmin is viewing as a branch admin
    if (isset($_SESSION['view_as_branch_id']) && isset($_SESSION['original_user_id'])) :
    ?>
        <div class="view-as-banner">
            You are currently viewing as <strong><?php echo htmlspecialchars($_SESSION['view_as_branch_name']); ?></strong>.
            <a href="<?php echo BASE_URL; ?>/superadmin/branches/exit_view.php" class="btn btn-sm btn-light ms-3">Exit View</a>
        </div>
    <?php endif; ?>

        <?php require_once 'content-header.php'; ?>
        <div class="page-content-inner"> <!-- New wrapper for the actual page content -->