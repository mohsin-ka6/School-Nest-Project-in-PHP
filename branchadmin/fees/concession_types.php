<?php
$page_title = "Fee Concession Types";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$errors = [];

// Handle form submission for adding/editing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $concession_id = isset($_POST['concession_id']) ? (int)$_POST['concession_id'] : 0;
    $name = trim($_POST['name']);
    $type = trim($_POST['type']);
    $value = trim($_POST['value']);
    $description = trim($_POST['description']);

    if (empty($name) || empty($type) || !is_numeric($value) || $value < 0) {
        $errors[] = "Name, Type, and a valid Value are required.";
    }
    if ($type == 'percentage' && $value > 100) {
        $errors[] = "Percentage value cannot be more than 100.";
    }

    if (empty($errors)) {
        try {
            if ($concession_id > 0) { // Update
                $stmt = $db->prepare("UPDATE fee_concession_types SET name = ?, type = ?, value = ?, description = ? WHERE id = ? AND branch_id = ?");
                $stmt->execute([$name, $type, $value, $description, $concession_id, $branch_id]);
                $_SESSION['success_message'] = "Concession type updated successfully!";
            } else { // Insert
                $stmt = $db->prepare("INSERT INTO fee_concession_types (branch_id, name, type, value, description) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$branch_id, $name, $type, $value, $description]);
                $_SESSION['success_message'] = "Concession type added successfully!";
            }
            redirect('concession_types.php');
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

// Handle deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $concession_id = (int)$_GET['id'];
    try {
        $stmt = $db->prepare("DELETE FROM fee_concession_types WHERE id = ? AND branch_id = ?");
        $stmt->execute([$concession_id, $branch_id]);
        $_SESSION['success_message'] = "Concession type deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Could not delete concession type. It might be in use.";
    }
    redirect('concession_types.php');
}

// Fetch all concession types
$stmt = $db->prepare("SELECT * FROM fee_concession_types WHERE branch_id = ? ORDER BY name ASC");
$stmt->execute([$branch_id]);
$concession_types = $stmt->fetchAll();

$edit_concession = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM fee_concession_types WHERE id = ? AND branch_id = ?");
    $stmt->execute([$edit_id, $branch_id]);
    $edit_concession = $stmt->fetch();
}

display_flash_messages(true);
require_once '../../header.php';
?>

<?php require_once '../../sidebar_branchadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Concession Types</li>
    </ol>

    <?php display_flash_messages(); ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-plus me-1"></i> <?php echo $edit_concession ? 'Edit' : 'Add New'; ?> Concession Type</div>
                <div class="card-body">
                    <form action="concession_types.php" method="POST">
                        <input type="hidden" name="concession_id" value="<?php echo $edit_concession['id'] ?? 0; ?>">
                        <div class="mb-3"><label class="form-label">Name*</label><input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($edit_concession['name'] ?? ''); ?>" required></div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Type*</label>
                                <select name="type" class="form-select" required>
                                    <option value="percentage" <?php echo (($edit_concession['type'] ?? '') == 'percentage' ? 'selected' : ''); ?>>Percentage</option>
                                    <option value="fixed" <?php echo (($edit_concession['type'] ?? '') == 'fixed' ? 'selected' : ''); ?>>Fixed Amount</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3"><label class="form-label">Value*</label><input type="number" step="0.01" name="value" class="form-control" value="<?php echo htmlspecialchars($edit_concession['value'] ?? ''); ?>" required></div>
                        </div>
                        <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($edit_concession['description'] ?? ''); ?></textarea></div>
                        <button type="submit" class="btn btn-primary"><?php echo $edit_concession ? 'Update' : 'Add'; ?></button>
                        <?php if ($edit_concession): ?><a href="concession_types.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-list me-1"></i> List of Concession Types</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead><tr><th>Name</th><th>Type</th><th>Value</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($concession_types)): ?>
                                    <tr><td colspan="4" class="text-center">No concession types found.</td></tr>
                                <?php else: foreach ($concession_types as $type): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($type['name']); ?></td>
                                    <td><?php echo ucfirst($type['type']); ?></td>
                                    <td><?php echo ($type['type'] == 'percentage') ? $type['value'] . '%' : number_format($type['value'], 2); ?></td>
                                    <td>
                                        <a href="?action=edit&id=<?php echo $type['id']; ?>" class="btn btn-sm btn-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="?action=delete&id=<?php echo $type['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');" title="Delete"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>
