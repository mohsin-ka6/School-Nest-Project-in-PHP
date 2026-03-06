<?php
// This file is included in header.php to create the content header

// Function to get user initials for avatar
function get_user_initials($name) {
    $words = explode(" ", $name);
    $initials = "";
    if (count($words) >= 2) {
        $initials .= strtoupper(substr($words[0], 0, 1));
        $initials .= strtoupper(substr(end($words), 0, 1));
    } else {
        $initials .= strtoupper(substr($name, 0, 2));
    }
    return $initials;
}
?>
<div class="content-header">
    <h1 class="page-title"><?php echo $page_title ?? 'Dashboard'; ?></h1>
    <div class="user-info">
        <div class="user-avatar"><?php echo get_user_initials($_SESSION['full_name']); ?></div>
        <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
    </div>
</div>