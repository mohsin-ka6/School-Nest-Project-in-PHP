<?php
$page_title = "Manage Academic Sessions";
require_once '../config.php';
require_once '../functions.php';

check_role('superadmin');

$errors = [];

// Fetch all branches for dropdowns
$stmt_branches = $db->query("SELECT id, name FROM branches ORDER BY name ASC");
$branches = $stmt_branches->fetchAll();

// Handle form submission for adding/editing a session
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_session'])) {
    $session_id = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;
    $branch_id = (int)$_POST['branch_id'];
    $name = trim($_POST['name']);
    $start_date = trim($_POST['start_date']);
    $end_date = trim($_POST['end_date']);

    // Validation
    if (empty($branch_id)) $errors[] = "Branch is required.";
    if (empty($name)) $errors[] = "Session name is required.";
    if (empty($start_date)) $errors[] = "Start date is required.";
    if (empty($end_date)) $errors[] = "End date is required.";
    if (!empty($start_date) && !empty($end_date) && $start_date >= $end_date) {
        $errors[] = "End date must be after the start date.";
    }

    if (empty($errors)) {
        try {
            if ($session_id > 0) { // Update
                $stmt = $db->prepare("UPDATE academic_sessions SET branch_id = ?, name = ?, start_date = ?, end_date = ? WHERE id = ?");
                $stmt->execute([$branch_id, $name, $start_date, $end_date, $session_id]);
                $_SESSION['success_message'] = "Academic session updated successfully!";
            } else { // Insert
                $stmt = $db->prepare("INSERT INTO academic_sessions (branch_id, name, start_date, end_date) VALUES (?, ?, ?, ?)");
                $stmt->execute([$branch_id, $name, $start_date, $end_date]);
                $_SESSION['success_message'] = "Academic session added successfully!";
            }
            redirect('manage_sessions.php');
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Handle deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $session_id = (int)$_GET['id'];
    try {
        // Check if session has enrollments before deleting
        $stmt_check = $db->prepare("SELECT COUNT(*) FROM student_enrollments WHERE session_id = ?");
        $stmt_check->execute([$session_id]);
        if ($stmt_check->fetchColumn() > 0) {
            $_SESSION['error_message'] = "Cannot delete session. It has student enrollments associated with it.";
        } else {
            $stmt = $db->prepare("DELETE FROM academic_sessions WHERE id = ?");
            $stmt->execute([$session_id]);
            $_SESSION['success_message'] = "Academic session deleted successfully.";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Could not delete session. Error: " . $e->getMessage();
    }
    redirect('manage_sessions.php');
}

// Handle setting a session as current
if (isset($_GET['action']) && $_GET['action'] == 'set_current' && isset($_GET['id'])) {
    $session_id = (int)$_GET['id'];
    $branch_id_to_update = (int)$_GET['branch_id'];
    try {
        $db->beginTransaction();
        // 1. Unset any current session for this branch
        $stmt_unset = $db->prepare("UPDATE academic_sessions SET is_current = 0 WHERE branch_id = ?");
        $stmt_unset->execute([$branch_id_to_update]);

        // 2. Set the new session as current
        $stmt_set = $db->prepare("UPDATE academic_sessions SET is_current = 1 WHERE id = ?");
        $stmt_set->execute([$session_id]);
        $db->commit();
        $_SESSION['success_message'] = "Academic session has been set as current.";
    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['error_message'] = "Could not set session as current. Error: " . $e->getMessage();
    }
    redirect('manage_sessions.php');
}

// Fetch all sessions to display
$stmt_sessions = $db->query("
    SELECT ac.*, b.name as branch_name 
    FROM academic_sessions ac
    JOIN branches b ON ac.branch_id = b.id
    ORDER BY b.name, ac.start_date DESC
");
$sessions = $stmt_sessions->fetchAll();

require_once '../header.php';
?>

<?php require_once '../sidebar_superadmin.php'; ?>
<?php require_once '../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Manage Sessions</li>
    </ol>

    <?php display_flash_messages(); ?>
    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-plus-circle me-1"></i> Add New Session</div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="branch_id" class="form-label">Branch*</label>
                            <select name="branch_id" id="branch_id" class="form-select" required>
                                <option value="">-- Select Branch --</option>
                                <?php foreach ($branches as $branch) echo "<option value='{$branch['id']}'>" . htmlspecialchars($branch['name']) . "</option>"; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="name" class="form-label">Session Name*</label>
                            <input type="text" name="name" id="name" class="form-control" placeholder="e.g., 2025-2026" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Start Date*</label>
                                <input type="date" name="start_date" id="start_date" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">End Date*</label>
                                <input type="date" name="end_date" id="end_date" class="form-control" required>
                            </div>
                        </div>
                        <button type="submit" name="save_session" class="btn btn-primary">Save Session</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-list me-1"></i> Academic Sessions List</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Branch</th><th>Session</th><th>Start Date</th><th>End Date</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($sessions)): ?>
                                    <tr><td colspan="6" class="text-center">No academic sessions found.</td></tr>
                                <?php else: foreach ($sessions as $session): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($session['branch_name']); ?></td>
                                    <td><?php echo htmlspecialchars($session['name']); ?></td>
                                    <td><?php echo date('d M, Y', strtotime($session['start_date'])); ?></td>
                                    <td><?php echo date('d M, Y', strtotime($session['end_date'])); ?></td>
                                    <td>
                                        <?php if ($session['is_current']): ?>
                                            <span class="badge bg-success">Current</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$session['is_current']): ?>
                                        <a href="?action=set_current&id=<?php echo $session['id']; ?>&branch_id=<?php echo $session['branch_id']; ?>" class="btn btn-sm btn-success" title="Set as Current"><i class="fas fa-check-circle"></i></a>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-primary edit-session-btn" data-bs-toggle="modal" data-bs-target="#editSessionModal" data-session='<?php echo json_encode($session); ?>' title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?action=delete&id=<?php echo $session['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this session?');" title="Delete"><i class="fas fa-trash"></i></a>
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

<!-- Edit Session Modal -->
<div class="modal fade" id="editSessionModal" tabindex="-1" aria-labelledby="editSessionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSessionModalLabel">Edit Academic Session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="session_id" id="edit_session_id">
                    <div class="mb-3">
                        <label for="edit_branch_id" class="form-label">Branch*</label>
                        <select name="branch_id" id="edit_branch_id" class="form-select" required>
                            <option value="">-- Select Branch --</option>
                            <?php foreach ($branches as $branch) echo "<option value='{$branch['id']}'>" . htmlspecialchars($branch['name']) . "</option>"; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Session Name*</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_start_date" class="form-label">Start Date*</label>
                            <input type="date" name="start_date" id="edit_start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_end_date" class="form-label">End Date*</label>
                            <input type="date" name="end_date" id="edit_end_date" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="save_session" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editSessionModal = document.getElementById('editSessionModal');
    editSessionModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const session = JSON.parse(button.getAttribute('data-session'));
        
        const modal = this;
        modal.querySelector('#edit_session_id').value = session.id;
        modal.querySelector('#edit_branch_id').value = session.branch_id;
        modal.querySelector('#edit_name').value = session.name;
        modal.querySelector('#edit_start_date').value = session.start_date;
        modal.querySelector('#edit_end_date').value = session.end_date;
    });
});
</script>