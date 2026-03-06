<?php
$page_title = "Fee Types";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$errors = [];

// Fetch academic sessions
$stmt_sessions = $db->prepare("SELECT id, name FROM academic_sessions WHERE branch_id = ? ORDER BY start_date DESC");
$stmt_sessions->execute([$branch_id]);
$sessions = $stmt_sessions->fetchAll();

// Determine which session to view, defaulting to the current one
$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
if (!$session_id && !empty($sessions)) {
    $stmt_current_session = $db->prepare("SELECT id FROM academic_sessions WHERE branch_id = ? AND is_current = 1");
    $stmt_current_session->execute([$branch_id]);
    $current_session = $stmt_current_session->fetch();
    $session_id = $current_session ? $current_session['id'] : $sessions[0]['id'];
}

// Handle form submission for adding/editing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $group_id = (int)$_POST['group_id'];
    $type_name = trim($_POST['type_name']);
    $fee_code = trim($_POST['fee_code']);
    $description = trim($_POST['description']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    $type_id = isset($_POST['type_id']) ? (int)$_POST['type_id'] : 0;

    if (empty($group_id) || empty($type_name) || empty($fee_code)) {
        $errors[] = "Fee Group, Type Name, and Fee Code are required.";
    }

    if (empty($errors) && $session_id) {
        try {
            if ($type_id > 0) { // Update
                $stmt = $db->prepare("UPDATE fee_types SET group_id = ?, name = ?, fee_code = ?, description = ?, is_default = ? WHERE id = ? AND branch_id = ?");
                $stmt->execute([$group_id, $type_name, $fee_code, $description, $is_default, $type_id, $branch_id]);
                $_SESSION['success_message'] = "Fee Type updated successfully!";
            } else { // Insert
                $stmt = $db->prepare("INSERT INTO fee_types (branch_id, session_id, group_id, name, fee_code, description, is_default) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$branch_id, $session_id, $group_id, $type_name, $fee_code, $description, $is_default]);
                $_SESSION['success_message'] = "Fee Type added successfully!";
            }
            redirect("fee_types.php?session_id={$session_id}");
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // Handle unique constraint violation for fee_code
                $errors[] = "The Fee Code '{$fee_code}' already exists in this session. Please choose a unique code.";
            } else {
                $errors[] = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// Handle deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $type_id = (int)$_GET['id'];
    try {
        $stmt = $db->prepare("DELETE FROM fee_types WHERE id = ? AND branch_id = ?");
        $stmt->execute([$type_id, $branch_id]);
        $_SESSION['success_message'] = "Fee Type deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Could not delete fee type. It might be in use.";
    }
    redirect("fee_types.php?session_id={$session_id}");
}

// Fetch fee groups and fee types for the selected session
$fee_groups = [];
$fee_types = [];
if ($session_id) {
    // Fetch fee groups for the dropdown
    $stmt_groups = $db->prepare("SELECT id, name FROM fee_groups WHERE branch_id = ? AND session_id = ? ORDER BY name ASC");
    $stmt_groups->execute([$branch_id, $session_id]);
    $fee_groups = $stmt_groups->fetchAll();

    // Fetch all fee types with their group names
    $stmt = $db->prepare("SELECT ft.*, fg.name as group_name FROM fee_types ft JOIN fee_groups fg ON ft.group_id = fg.id WHERE ft.branch_id = ? AND ft.session_id = ? ORDER BY fg.name, ft.name ASC");
    $stmt->execute([$branch_id, $session_id]);
    $fee_types = $stmt->fetchAll();
}

// For editing, fetch the specific type
$edit_type = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    if ($session_id) {
        $stmt = $db->prepare("SELECT * FROM fee_types WHERE id = ? AND branch_id = ? AND session_id = ?");
        $stmt->execute([$edit_id, $branch_id, $session_id]);
        $edit_type = $stmt->fetch();
    }
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
        <li class="breadcrumb-item active">Fee Types</li>
    </ol>

    <?php display_flash_messages(); ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-filter me-1"></i> Filter by Session</div>
        <div class="card-body">
            <form action="" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-4"><label>Session</label><select name="session_id" class="form-select" required onchange="this.form.submit()"><option value="">-- Select Session --</option><?php foreach ($sessions as $session) echo "<option value='{$session['id']}' " . ($session_id == $session['id'] ? 'selected' : '') . ">" . htmlspecialchars($session['name']) . "</option>"; ?></select></div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($session_id): ?>
    <div class="row">
        <!-- Add/Edit Form -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-plus me-1"></i> <?php echo $edit_type ? 'Edit' : 'Add New'; ?> Fee Type</div>
                <div class="card-body">
                    <form action="fee_types.php?session_id=<?php echo $session_id; ?>" method="POST">
                        <input type="hidden" name="type_id" value="<?php echo $edit_type['id'] ?? 0; ?>">
                        <div class="mb-3"><label for="group_id" class="form-label">Fee Group*</label>
                            <select id="group_id" name="group_id" class="form-select" required>
                                <option value="">-- Select Group --</option>
                                <?php foreach ($fee_groups as $group): ?>
                                    <option value="<?php echo $group['id']; ?>" <?php echo (($edit_type['group_id'] ?? '') == $group['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($group['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3"><label for="type_name" class="form-label">Fee Type Name*</label><input type="text" id="type_name" name="type_name" class="form-control" value="<?php echo htmlspecialchars($edit_type['name'] ?? ''); ?>" required></div>
                        <div class="mb-3"><label for="fee_code" class="form-label">Fee Code*</label><input type="text" id="fee_code" name="fee_code" class="form-control" value="<?php echo htmlspecialchars($edit_type['fee_code'] ?? ''); ?>" required></div>
                        <div class="mb-3"><label for="description" class="form-label">Description</label><textarea id="description" name="description" class="form-control" rows="2"><?php echo htmlspecialchars($edit_type['description'] ?? ''); ?></textarea></div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_default" value="1" id="is_default" <?php echo (isset($edit_type['is_default']) && $edit_type['is_default']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_default">Is a Default Fee?</label>
                        </div>
                        <button type="submit" class="btn btn-primary"><?php echo $edit_type ? 'Update' : 'Add'; ?></button>
                        <?php if ($edit_type): ?><a href="fee_types.php?session_id=<?php echo $session_id; ?>" class="btn btn-secondary">Cancel</a><?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- Fee Types List -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-table me-1"></i> Fee Types for Selected Session</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead><tr><th>Name</th><th>Group</th><th>Fee Code</th><th>Default</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($fee_types)): ?>
                                    <tr><td colspan="5" class="text-center">No fee types found for this session. Create a Fee Group first.</td></tr>
                                <?php else: foreach ($fee_types as $type): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($type['name']); ?></td>
                                    <td><?php echo htmlspecialchars($type['group_name']); ?></td>
                                    <td><?php echo htmlspecialchars($type['fee_code']); ?></td>
                                    <td><?php echo $type['is_default'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'; ?></td>
                                    <td>
                                        <a href="fee_types.php?action=edit&id=<?php echo $type['id']; ?>&session_id=<?php echo $session_id; ?>" class="btn btn-sm btn-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="fee_types.php?action=delete&id=<?php echo $type['id']; ?>&session_id=<?php echo $session_id; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');" title="Delete"><i class="fas fa-trash"></i></a>
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
    <?php else: ?>
        <div class="alert alert-info">Please select an academic session to manage fee types.</div>
    <?php endif; ?>
</div>

<?php require_once '../../footer.php'; ?>
