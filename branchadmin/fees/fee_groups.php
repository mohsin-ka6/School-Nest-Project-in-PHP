<?php
$page_title = "Fee Groups";
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
    $group_name = trim($_POST['group_name']);
    $description = trim($_POST['description']);
    $group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;

    if (empty($group_name)) {
        $errors[] = "Group Name is required.";
    }

    if (empty($errors) && $session_id) {
        try {
            if ($group_id > 0) { // Update
                $stmt = $db->prepare("UPDATE fee_groups SET name = ?, description = ? WHERE id = ? AND branch_id = ?");
                $stmt->execute([$group_name, $description, $group_id, $branch_id]);
                $_SESSION['success_message'] = "Fee Group updated successfully!";
            } else { // Insert
                $stmt = $db->prepare("INSERT INTO fee_groups (branch_id, session_id, name, description) VALUES (?, ?, ?, ?)");
                $stmt->execute([$branch_id, $session_id, $group_name, $description]);
                $_SESSION['success_message'] = "Fee Group added successfully!";
            }
            redirect("fee_groups.php?session_id={$session_id}");
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // Handle unique constraint violation
                $errors[] = "A fee group with this name already exists in this session.";
            } else {
                $errors[] = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// Handle deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $group_id = (int)$_GET['id'];
    try {
        $stmt = $db->prepare("DELETE FROM fee_groups WHERE id = ? AND branch_id = ?");
        $stmt->execute([$group_id, $branch_id]);
        $_SESSION['success_message'] = "Fee Group deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Could not delete fee group. It might be in use by fee types.";
    }
    redirect("fee_groups.php?session_id={$session_id}");
}

// Fetch all fee groups for the selected session
$fee_groups = [];
if ($session_id) {
    $stmt = $db->prepare("SELECT * FROM fee_groups WHERE branch_id = ? AND session_id = ? ORDER BY name ASC");
    $stmt->execute([$branch_id, $session_id]);
    $fee_groups = $stmt->fetchAll();
}

// For editing, fetch the specific group
$edit_group = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM fee_groups WHERE id = ? AND branch_id = ?");
    $stmt->execute([$edit_id, $branch_id]);
    $edit_group = $stmt->fetch();
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
        <li class="breadcrumb-item active">Fee Groups</li>
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
                <div class="card-header"><i class="fas fa-plus me-1"></i> <?php echo $edit_group ? 'Edit' : 'Add New'; ?> Fee Group</div>
                <div class="card-body">
                    <form action="fee_groups.php?session_id=<?php echo $session_id; ?>" method="POST">
                        <input type="hidden" name="group_id" value="<?php echo $edit_group['id'] ?? 0; ?>">
                        <div class="mb-3"><label for="group_name" class="form-label">Group Name*</label><input type="text" id="group_name" name="group_name" class="form-control" value="<?php echo htmlspecialchars($edit_group['name'] ?? ''); ?>" required></div>
                        <div class="mb-3"><label for="description" class="form-label">Description</label><textarea id="description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars($edit_group['description'] ?? ''); ?></textarea></div>
                        <button type="submit" class="btn btn-primary"><?php echo $edit_group ? 'Update' : 'Add'; ?></button>
                        <?php if ($edit_group): ?><a href="fee_groups.php?session_id=<?php echo $session_id; ?>" class="btn btn-secondary">Cancel</a><?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- Fee Groups List -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-table me-1"></i> Fee Groups for Selected Session</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead><tr><th>Name</th><th>Description</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($fee_groups)): ?>
                                    <tr><td colspan="3" class="text-center">No fee groups found for this session.</td></tr>
                                <?php else: foreach ($fee_groups as $group): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($group['name']); ?></td>
                                    <td><?php echo htmlspecialchars($group['description']); ?></td>
                                    <td>
                                        <a href="fee_groups.php?action=edit&id=<?php echo $group['id']; ?>&session_id=<?php echo $session_id; ?>" class="btn btn-sm btn-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="fee_groups.php?action=delete&id=<?php echo $group['id']; ?>&session_id=<?php echo $session_id; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');" title="Delete"><i class="fas fa-trash"></i></a>
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
        <div class="alert alert-info">Please select an academic session to manage fee groups.</div>
    <?php endif; ?>
</div>

<?php require_once '../../footer.php'; ?>
