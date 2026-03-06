<?php
$page_title = "Add New Branch Admin";
require_once '../../config.php';
require_once '../../functions.php';

check_role('superadmin');

$full_name = $username = $email = $branch_id = '';
$errors = [];

// Fetch branches for the dropdown
$branch_stmt = $db->query("SELECT id, name FROM branches ORDER BY name ASC");
$branches = $branch_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $branch_id = (int)$_POST['branch_id'];

    if (empty($full_name)) { $errors[] = 'Full name is required.'; }
    if (empty($username)) { $errors[] = 'Username is required.'; }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'A valid email is required.'; }
    if (empty($password)) { $errors[] = 'Password is required.'; }
    if (strlen($password) < 8) { $errors[] = 'Password must be at least 8 characters.'; }
    if (empty($branch_id)) { $errors[] = 'A branch must be assigned.'; }

    // Check for unique username/email
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Username or email already exists.';
        }
    }

    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (full_name, username, email, password, role, branch_id) VALUES (?, ?, ?, ?, 'branchadmin', ?)");
            $stmt->execute([$full_name, $username, $email, $hashed_password, $branch_id]);
            
            $_SESSION['success_message'] = "Branch Admin '{$full_name}' added successfully!";
            redirect('view_admins.php');
        } catch (PDOException $e) {
            $errors[] = "Database error: Could not add admin. " . $e->getMessage();
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
        <li class="breadcrumb-item active">Add Admin</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-user-plus me-1"></i> Admin Details</div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?><p class="mb-0"><?php echo $error; ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="add_admin.php" method="POST">
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
                <div class="mb-3"><label for="password" class="form-label">Password*</label><input type="password" class="form-control" id="password" name="password" required></div>
                
                <button type="submit" class="btn btn-primary">Add Admin</button>
                <a href="view_admins.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>