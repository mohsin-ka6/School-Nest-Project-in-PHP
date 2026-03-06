<?php
$page_title = "Add New Parent";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and retrieve data
    $father_name = trim($_POST['father_name']);
    $father_phone = trim($_POST['father_phone']);
    $father_cnic = trim($_POST['father_cnic']);
    $father_email = trim($_POST['father_email']);
    $mother_name = trim($_POST['mother_name']);
    $mother_phone = trim($_POST['mother_phone']);
    $mother_cnic = trim($_POST['mother_cnic']);
    $mother_email = trim($_POST['mother_email']);

    // Validation
    if (empty($father_name)) $errors[] = "Father's Name is required.";
    if (empty($father_phone)) $errors[] = "Father's Phone is required (used for login).";
    if (empty($father_cnic)) $errors[] = "Father's CNIC is required.";

    // Check for uniqueness
    if (empty($errors)) {
        // Check if CNIC already exists in this branch
        $stmt = $db->prepare("SELECT id FROM parents WHERE father_cnic = ? AND branch_id = ?");
        $stmt->execute([$father_cnic, $branch_id]);
        if ($stmt->fetch()) {
            $errors[] = "A parent with this CNIC already exists in this branch.";
        }

        // Check if phone number (username) already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$father_phone]);
        if ($stmt->fetch()) {
            $errors[] = "This phone number is already in use as a username for another account.";
        }
    }

    if (empty($errors)) {
        $db->beginTransaction();
        try {
            // 1. Create parent user account
            $parent_username = $father_phone;
            $parent_password = password_hash($father_phone, PASSWORD_DEFAULT);
            $user_email = !empty($father_email) ? $father_email : uniqid() . '_' . $parent_username . '@school.local';

            $parent_user_stmt = $db->prepare("INSERT INTO users (branch_id, username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?, 'parent')");
            $parent_user_stmt->execute([$branch_id, $parent_username, $user_email, $parent_password, $father_name]);
            $parent_user_id = $db->lastInsertId();

            // 2. Create parent details record
            $parent_details_stmt = $db->prepare(
                "INSERT INTO parents (user_id, branch_id, father_name, father_phone, father_cnic, father_email, mother_name, mother_cnic, mother_phone, mother_email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $parent_details_stmt->execute([
                $parent_user_id, $branch_id, $father_name, $father_phone, $father_cnic,
                $father_email, $mother_name, $mother_cnic, $mother_phone, $mother_email
            ]);

            $db->commit();
            $_SESSION['success_message'] = "Parent '{$father_name}' added successfully.";
            redirect('manage_parents.php');

        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Database Error: Could not add parent. " . $e->getMessage();
        }
    }
}

require_once '../../header.php';
?>

<?php require_once ROOT_PATH . '/sidebar_branchadmin.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">

    <?php display_flash_messages(); ?>
    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <form action="" method="POST">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-user-plus me-1"></i> Add New Parent</div>
            <div class="card-body">
                <h5 class="text-primary">Father's Details</h5><hr>
                <div class="row">
                    <div class="col-md-4 mb-3"><label>Father's Name*</label><input type="text" name="father_name" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label>Father's Mobile* (for login)</label><input type="text" name="father_phone" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label>Father's CNIC*</label><input type="text" name="father_cnic" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label>Father's Email (Optional)</label><input type="email" name="father_email" class="form-control"></div>
                </div>
                <h5 class="text-primary mt-4">Mother's Details (Optional)</h5><hr>
                <div class="row">
                    <div class="col-md-4 mb-3"><label>Mother's Name</label><input type="text" name="mother_name" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label>Mother's Mobile</label><input type="text" name="mother_phone" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label>Mother's CNIC</label><input type="text" name="mother_cnic" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label>Mother's Email</label><input type="email" name="mother_email" class="form-control"></div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Save Parent</button>
                <a href="manage_parents.php" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>

<?php require_once '../../footer.php'; ?>
