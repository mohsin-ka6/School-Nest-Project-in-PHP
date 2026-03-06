<?php
$current_page = basename($_SERVER['PHP_SELF']);
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
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        main { flex: 1; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
            <?php if (SITE_LOGO && file_exists(ROOT_PATH . '/' . SITE_LOGO)): ?>
                <img src="<?php echo BASE_URL . '/' . SITE_LOGO; ?>" alt="Logo" style="height: 30px; margin-right: 10px;">
            <?php endif; ?>
            <?php echo SITE_NAME; ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="publicNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'about_us.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/about_us.php">About</a></li>
                <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'events.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/events.php">News & Events</a></li>
                <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'contact_us.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/contact_us.php">Contact</a></li>
                <?php if (is_logged_in()): ?>
                    <li class="nav-item"><a class="nav-link btn btn-primary ms-lg-2" href="<?php echo get_dashboard_path_by_role($_SESSION['role']); ?>">Dashboard</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link btn btn-primary ms-lg-2" href="<?php echo BASE_URL; ?>/auth/login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main>