<?php
$page_title = "Edit Branch";
require_once '../../config.php';
require_once '../../functions.php';

check_role('superadmin');

$branch_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

if (!$branch_id) {
    $_SESSION['error_message'] = "Invalid branch ID.";
    redirect('manage_branches.php');
}

// Fetch branch data
$stmt = $db->prepare("SELECT * FROM branches WHERE id = ?");
$stmt->execute([$branch_id]);
$branch = $stmt->fetch();

if (!$branch) {
    $_SESSION['error_message'] = "Branch not found.";
    redirect('manage_branches.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_branch'])) {
    $name = trim($_POST['name']);
    if (empty($name)) {
        $errors[] = "Branch name is required.";
    }

    // Handle logo upload
    $logo_db_path = $branch['logo'] ?? null; // Keep old logo by default
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['logo']['type'], $allowed_types)) {
            $errors[] = "Invalid file type for logo. Only JPG, PNG, and GIF are allowed.";
        } else {
            $upload_dir = ROOT_PATH . '/assets/uploads/logos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Delete old logo if it exists
            if (!empty($branch['logo']) && file_exists(ROOT_PATH . '/' . $branch['logo'])) {
                unlink(ROOT_PATH . '/' . $branch['logo']);
            }

            $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $new_logo_name = 'branch_' . $branch_id . '_' . time() . '.' . $file_extension;
            $new_logo_path = $upload_dir . $new_logo_name;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $new_logo_path)) {
                $logo_db_path = 'assets/uploads/logos/' . $new_logo_name;
            } else {
                $errors[] = "Failed to upload new logo.";
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("UPDATE branches SET name = ?, address = ?, phone = ?, email = ?, logo = ? WHERE id = ?");
            $stmt->execute([$name, trim($_POST['address']), trim($_POST['phone']), trim($_POST['email']), $logo_db_path, $branch_id]);
            $_SESSION['success_message'] = "Branch '{$name}' updated successfully!";
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
        <li class="breadcrumb-item active">Edit Branch</li>
    </ol>

    <?php if (!empty($errors)) { echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; } ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-edit me-1"></i> Edit Branch Details</div>
        <div class="card-body">
            <form action="edit_branch.php?id=<?php echo $branch_id; ?>" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="name" class="form-label">Branch Name*</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($branch['name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea id="address" name="address" class="form-control" rows="2"><?php echo htmlspecialchars($branch['address']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($branch['phone']); ?>">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($branch['email']); ?>">
                </div>
                <div class="mb-3">
                    <label for="logo" class="form-label">Update Branch Logo (Optional)</label>
                    <input type="file" id="logo" name="logo" class="form-control">
                </div>
                <?php if (!empty($branch['logo']) && file_exists(ROOT_PATH . '/' . $branch['logo'])): ?>
                <div class="mb-3">
                    <label class="form-label">Current Logo</label><br>
                    <img src="<?php echo BASE_URL . '/' . $branch['logo']; ?>" alt="Current Logo" style="max-height: 50px; background: #f0f0f0; padding: 5px; border-radius: 5px;">
                </div>
                <?php endif; ?>
                <button type="submit" name="update_branch" class="btn btn-primary">Update Branch</button>
                <a href="manage_branches.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>