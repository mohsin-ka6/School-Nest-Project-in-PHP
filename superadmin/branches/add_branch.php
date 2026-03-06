<?php
$page_title = "Add New Branch";
require_once '../../config.php';
require_once '../../functions.php';

check_role('superadmin');

$errors = [];

// Handle form submission for adding a new branch
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_branch'])) {
    $name = trim($_POST['name']);
    if (empty($name)) {
        $errors[] = "Branch name is required.";
    }

    // Handle logo upload
    $logo_db_path = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['logo']['type'], $allowed_types)) {
            $errors[] = "Invalid file type for logo. Only JPG, PNG, and GIF are allowed.";
        } else {
            $upload_dir = ROOT_PATH . '/assets/uploads/logos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $new_logo_name = 'branch_' . time() . '.' . $file_extension;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $new_logo_name)) {
                $logo_db_path = 'assets/uploads/logos/' . $new_logo_name;
            } else {
                $errors[] = "Failed to upload branch logo.";
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO branches (name, address, phone, email, logo) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, trim($_POST['address']), trim($_POST['phone']), trim($_POST['email']), $logo_db_path]);
            $_SESSION['success_message'] = "Branch '{$name}' added successfully!";
            redirect('manage_branches.php');
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
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
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="manage_branches.php">Manage Branches</a></li>
        <li class="breadcrumb-item active">Add Branch</li>
    </ol>

    <?php if (!empty($errors)) { echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; } ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-plus me-1"></i> Add New Branch</div>
        <div class="card-body">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="mb-3"><label>Branch Name*</label><input type="text" name="name" class="form-control" required></div>
                <div class="mb-3"><label>Address</label><textarea name="address" class="form-control" rows="2"></textarea></div>
                <div class="mb-3"><label>Phone</label><input type="text" name="phone" class="form-control"></div>
                <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control"></div>
                <div class="mb-3"><label>Branch Logo (Optional)</label><input type="file" name="logo" class="form-control"></div>
                <button type="submit" name="add_branch" class="btn btn-primary">Add Branch</button>
                <a href="manage_branches.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>