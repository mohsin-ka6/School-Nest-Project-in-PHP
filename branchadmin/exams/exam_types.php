<?php
$page_title = "Exam Types";
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
    $exam_type_id = isset($_POST['exam_type_id']) ? (int)$_POST['exam_type_id'] : 0;
    $name = trim($_POST['name']);
    $publish_date = !empty($_POST['publish_date']) ? trim($_POST['publish_date']) : null;

    if (empty($name)) {
        $errors[] = "Exam type name cannot be empty.";
    }

    if (empty($errors)) {
        try {
            if ($exam_type_id > 0) { // Update
                $stmt = $db->prepare("UPDATE exam_types SET name = ?, publish_date = ? WHERE id = ? AND branch_id = ?");
                $stmt->execute([$name, $publish_date, $exam_type_id, $branch_id]);
                $_SESSION['success_message'] = "Exam type updated successfully!";
            } else { // Insert
                $stmt = $db->prepare("INSERT INTO exam_types (branch_id, session_id, name, publish_date) VALUES (?, ?, ?, ?)");
                $stmt->execute([$branch_id, $session_id, $name, $publish_date]);
                $_SESSION['success_message'] = "Exam type added successfully!";
            }
            redirect("exam_types.php?session_id={$session_id}");
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $errors[] = "An exam with this name already exists in this session.";
            } else {
                $errors[] = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// Handle deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $exam_type_id = (int)$_GET['id'];
    try {
        // Future check: Ensure this type is not used in any exam schedule before deleting.
        $stmt = $db->prepare("DELETE FROM exam_types WHERE id = ? AND branch_id = ?");
        $stmt->execute([$exam_type_id, $branch_id]);
        $_SESSION['success_message'] = "Exam type deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Could not delete exam type. It might be in use.";
    }
    redirect("exam_types.php?session_id={$session_id}");
}

// Fetch all exam types for this branch
$exam_types = [];
if ($session_id) {
    $stmt = $db->prepare("SELECT * FROM exam_types WHERE branch_id = ? AND session_id = ? ORDER BY name ASC");
    $stmt->execute([$branch_id, $session_id]);
    $exam_types = $stmt->fetchAll();
}

require_once '../../header.php';
?>

<?php require_once '../../sidebar_branchadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Exam Types</li>
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

    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <?php if ($session_id): ?>
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-plus-circle me-1"></i> Add New Exam Type</div>
                <div class="card-body">
                    <form action="exam_types.php?session_id=<?php echo $session_id; ?>" method="POST">
                        <div class="mb-3"><label>Name*</label><input type="text" name="name" class="form-control" required placeholder="e.g., Mid-Term Exam"></div>
                        <div class="mb-3"><label>Publish Date</label><input type="date" name="publish_date" class="form-control"></div>
                        <button type="submit" class="btn btn-primary">Save Exam Type</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-list-alt me-1"></i> Exam Types for Selected Session</div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead><tr><th>Name</th><th>Publish Date</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php if (empty($exam_types)): ?>
                                <tr><td colspan="3" class="text-center">No exam types found for this session.</td></tr>
                            <?php else: foreach ($exam_types as $type): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($type['name']); ?></td>
                                <td><?php echo $type['publish_date'] ? date('d M, Y', strtotime($type['publish_date'])) : '<span class="text-muted">Not Set</span>'; ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary edit-exam-type-btn" data-bs-toggle="modal" data-bs-target="#editExamTypeModal" data-exam-type='<?php echo json_encode($type); ?>' title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?action=delete&id=<?php echo $type['id']; ?>&session_id=<?php echo $session_id; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure? This action cannot be undone.');" title="Delete"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-info">Please select an academic session to manage exam types.</div>
    <?php endif; ?>
</div>

<!-- Edit Exam Type Modal -->
<div class="modal fade" id="editExamTypeModal" tabindex="-1" aria-labelledby="editExamTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editExamTypeModalLabel">Edit Exam Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="exam_types.php?session_id=<?php echo $session_id; ?>" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="exam_type_id" id="edit_exam_type_id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name*</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_publish_date" class="form-label">Publish Date</label>
                        <input type="date" name="publish_date" id="edit_publish_date" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editExamTypeModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const examType = JSON.parse(button.getAttribute('data-exam-type'));
        
        const modal = this;
        modal.querySelector('#edit_exam_type_id').value = examType.id;
        modal.querySelector('#edit_name').value = examType.name;
        modal.querySelector('#edit_publish_date').value = examType.publish_date;
    });
});
</script>