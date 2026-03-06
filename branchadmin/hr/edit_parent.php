<?php
$page_title = "Edit Parent";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$parent_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

if (!$parent_id) {
    $_SESSION['error_message'] = "Invalid parent ID.";
    redirect('manage_parents.php');
}

// Fetch parent details
$stmt = $db->prepare("SELECT p.*, u.email as user_email FROM parents p JOIN users u ON p.user_id = u.id WHERE p.id = ? AND p.branch_id = ?");
$stmt->execute([$parent_id, $branch_id]);
$parent = $stmt->fetch();

if (!$parent) {
    $_SESSION['error_message'] = "Parent not found.";
    redirect('manage_parents.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and retrieve data
    $father_name = trim($_POST['father_name']);
    $father_phone = trim($_POST['father_phone']);
    $father_email = trim($_POST['father_email']);
    $mother_name = trim($_POST['mother_name']);
    $mother_phone = trim($_POST['mother_phone']);
    $mother_cnic = trim($_POST['mother_cnic']);
    $mother_email = trim($_POST['mother_email']);
    $password = $_POST['password'];

    // Validation
    if (empty($father_name)) $errors[] = "Father's Name is required.";
    if (empty($father_phone)) $errors[] = "Father's Phone is required (used for login).";
    if (!empty($password) && strlen($password) < 8) $errors[] = "New password must be at least 8 characters long.";

    // Check for uniqueness if phone number changed
    if ($father_phone !== $parent['father_phone']) {
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$father_phone]);
        if ($stmt->fetch()) {
            $errors[] = "This phone number is already in use as a username for another account.";
        }
    }

    if (empty($errors)) {
        $db->beginTransaction();
        try {
            // 1. Update user account
            $user_sql = "UPDATE users SET full_name = ?, username = ?, email = ?";
            $user_params = [$father_name, $father_phone, $father_email];
            if (!empty($password)) {
                $user_sql .= ", password = ?";
                $user_params[] = password_hash($password, PASSWORD_DEFAULT);
            }
            $user_sql .= " WHERE id = ?";
            $user_params[] = $parent['user_id'];
            $user_stmt = $db->prepare($user_sql);
            $user_stmt->execute($user_params);

            // 2. Update parent details
            $parent_stmt = $db->prepare("UPDATE parents SET father_name=?, father_phone=?, father_email=?, mother_name=?, mother_cnic=?, mother_phone=?, mother_email=? WHERE id=?");
            $parent_stmt->execute([$father_name, $father_phone, $father_email, $mother_name, $mother_cnic, $mother_phone, $mother_email, $parent_id]);

            $db->commit();
            $_SESSION['success_message'] = "Parent '{$father_name}' updated successfully.";
            redirect('manage_parents.php');

        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Database Error: Could not update parent. " . $e->getMessage();
        }
    }
}

require_once '../../header.php';
?>

<?php require_once ROOT_PATH . '/sidebar_branchadmin.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">

    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <form action="edit_parent.php?id=<?php echo $parent_id; ?>" method="POST">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-user-edit me-1"></i> Edit Parent Details</div>
            <div class="card-body">
                <h5 class="text-primary">Father's Details</h5><hr>
                <div class="row">
                    <div class="col-md-4 mb-3"><label>Father's Name*</label><input type="text" name="father_name" class="form-control" value="<?php echo htmlspecialchars($parent['father_name']); ?>" required></div>
                    <div class="col-md-4 mb-3"><label>Father's Mobile* (for login)</label><input type="text" name="father_phone" class="form-control" value="<?php echo htmlspecialchars($parent['father_phone']); ?>" required></div>
                    <div class="col-md-4 mb-3"><label>Father's CNIC</label><input type="text" name="father_cnic" class="form-control" value="<?php echo htmlspecialchars($parent['father_cnic']); ?>" readonly><small class="text-muted">CNIC cannot be changed.</small></div>
                    <div class="col-md-4 mb-3"><label>Father's Email (Optional)</label><input type="email" name="father_email" class="form-control" value="<?php echo htmlspecialchars($parent['father_email']); ?>"></div>
                    <div class="col-md-4 mb-3"><label>New Password</label><input type="password" name="password" class="form-control"><small class="text-muted">Leave blank to keep current.</small></div>
                </div>
                <h5 class="text-primary mt-4">Mother's Details (Optional)</h5><hr>
                <div class="row">
                    <div class="col-md-4 mb-3"><label>Mother's Name</label><input type="text" name="mother_name" class="form-control" value="<?php echo htmlspecialchars($parent['mother_name']); ?>"></div>
                    <div class="col-md-4 mb-3"><label>Mother's Mobile</label><input type="text" name="mother_phone" class="form-control" value="<?php echo htmlspecialchars($parent['mother_phone']); ?>"></div>
                    <div class="col-md-4 mb-3"><label>Mother's CNIC</label><input type="text" name="mother_cnic" class="form-control" value="<?php echo htmlspecialchars($parent['mother_cnic']); ?>"></div>
                    <div class="col-md-4 mb-3"><label>Mother's Email</label><input type="email" name="mother_email" class="form-control" value="<?php echo htmlspecialchars($parent['mother_email']); ?>"></div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="manage_parents.php" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>

<?php require_once '../../footer.php'; ?>
