<?php
$page_title = "Edit Branch Admin";
require_once '../../config.php';
require_once '../../functions.php';

check_role('superadmin');

$errors = [];
$admin_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$admin_id) {
    $_SESSION['error_message'] = "Invalid admin ID.";
    redirect('view_admins.php');
}

// Fetch admin data
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'branchadmin'");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();
    if (!$admin) {
        $_SESSION['error_message'] = "Admin not found.";
        redirect('view_admins.php');
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    redirect('view_admins.php');
}

// Fetch branches for the dropdown
$branch_stmt = $db->query("SELECT id, name FROM branches ORDER BY name ASC");
$branches = $branch_stmt->fetchAll();

$full_name = $admin['full_name'];
$username = $admin['username'];
$email = $admin['email'];
$branch_id = $admin['branch_id'];
$status = $admin['status'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $branch_id = (int)$_POST['branch_id'];
    $status = $_POST['status'];
    $password = $_POST['password'];

    if (empty($full_name)) { $errors[] = 'Full name is required.'; }
    if (empty($username)) { $errors[] = 'Username is required.'; }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'A valid email is required.'; }
    if (empty($branch_id)) { $errors[] = 'A branch must be assigned.'; }
    if (!in_array($status, ['active', 'inactive', 'suspended'])) { $errors[] = 'Invalid status selected.'; }
    if (!empty($password) && strlen($password) < 8) { $errors[] = 'New password must be at least 8 characters.'; }

    // Check for unique username/email, excluding the current admin
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $admin_id]);
        if ($stmt->fetch()) {
            $errors[] = 'Username or email already exists for another user.';
        }
    }

    if (empty($errors)) {
        try {
            if (!empty($password)) {
                // Update with new password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET full_name=?, username=?, email=?, branch_id=?, status=?, password=? WHERE id=?");
                $stmt->execute([$full_name, $username, $email, $branch_id, $status, $hashed_password, $admin_id]);
            } else {
                // Update without changing password
                $stmt = $db->prepare("UPDATE users SET full_name=?, username=?, email=?, branch_id=?, status=? WHERE id=?");
                $stmt->execute([$full_name, $username, $email, $branch_id, $status, $admin_id]);
            }
            
            $_SESSION['success_message'] = "Admin '{$full_name}' updated successfully!";
            redirect('view_admins.php');
        } catch (PDOException $e) {
            $errors[] = "Database error: Could not update admin. " . $e->getMessage();
        }
    }
}

require_once '../../header.php';
?>

<?php require_once '../../sidebar_superadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/superadmin/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="view_admins.php">Manage Admins</a></li>
        <li class="breadcrumb-item active">Edit Admin</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-user-edit me-1"></i> Edit Admin Details</div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?><p class="mb-0"><?php echo $error; ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="edit_admin.php?id=<?php echo $admin_id; ?>" method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3"><label for="full_name" class="form-label">Full Name*</label><input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required></div>
                    <div class="col-md-6 mb-3"><label for="branch_id" class="form-label">Assign to Branch*</label>
                        <select class="form-select" id="branch_id" name="branch_id" required>
                            <option value="">Select Branch...</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>" <?php echo ($branch_id == $branch['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($branch['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label for="username" class="form-label">Username*</label><input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required></div>
                    <div class="col-md-6 mb-3"><label for="email" class="form-label">Email*</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required></div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label for="password" class="form-label">New Password</label><input type="password" class="form-control" id="password" name="password"><small class="form-text text-muted">Leave blank to keep current password.</small></div>
                    <div class="col-md-6 mb-3"><label for="status" class="form-label">Status*</label><select class="form-select" id="status" name="status" required><option value="active" <?php echo ($status == 'active') ? 'selected' : ''; ?>>Active</option><option value="inactive" <?php echo ($status == 'inactive') ? 'selected' : ''; ?>>Inactive</option><option value="suspended" <?php echo ($status == 'suspended') ? 'selected' : ''; ?>>Suspended</option></select></div>
                </div>
                <button type="submit" class="btn btn-primary">Update Admin</button>
                <a href="view_admins.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>