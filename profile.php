<?php
$page_title = "My Profile";
require_once 'config.php';
require_once 'functions.php';

// Security check: Ensure user is logged in.
if (!is_logged_in()) {
    redirect(BASE_URL . '/auth/login.php');
}

$user_id = $_SESSION['user_id'];
$errors = [];

// Fetch current user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    // This should not happen if the user is logged in
    die("Error: User record not found.");
}

require_once 'header.php';

// Include the correct sidebar based on the user's role
if ($_SESSION['role'] === 'superadmin') {
    require_once 'sidebar_superadmin.php';
} elseif ($_SESSION['role'] === 'branchadmin') {
    require_once 'sidebar_branchadmin.php';
} elseif ($_SESSION['role'] === 'parent') {
    require_once 'parent/sidebar_parent.php';
}
// Add other roles (teacher, student) here as needed

?>

<div class="container-fluid px-4">
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-user-circle me-1"></i> Your Profile Information</div>
        <div class="card-body">
            <table class="table table-bordered">
                <tbody>
                    <tr><th width="30%">Full Name</th><td><?php echo htmlspecialchars($user['full_name']); ?></td></tr>
                    <tr><th>Email</th><td><?php echo htmlspecialchars($user['email']); ?></td></tr>
                    <tr><th>Username</th><td><?php echo htmlspecialchars($user['username']); ?></td></tr>
                    <tr><th>Role</th><td><?php echo ucfirst($user['role']); ?></td></tr>
                    <tr><th>Account Status</th><td><span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'danger'; ?>"><?php echo ucfirst($user['status']); ?></span></td></tr>
                </tbody>
            </table>
            <div class="mt-3">
                <p class="text-muted small">To change your password or other details, please contact the system administrator.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>