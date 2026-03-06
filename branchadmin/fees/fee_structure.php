<?php
$page_title = "Fee Structure";
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

// --- Filters ---
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// Fetch classes for the main dropdown
$classes = [];
if ($session_id) {
    $stmt_classes = $db->prepare("SELECT id, name FROM classes WHERE branch_id = ? ORDER BY numeric_name ASC");
    $stmt_classes->execute([$branch_id]);
    $classes = $stmt_classes->fetchAll();
}

// Handle deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $structure_id = (int)$_GET['id'];
    try {
        $stmt = $db->prepare("DELETE FROM class_fee_structure WHERE id = ? AND branch_id = ?");
        $stmt->execute([$structure_id, $branch_id]);
        $_SESSION['success_message'] = "Fee structure entry deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Could not delete fee structure entry.";
    }
    redirect("fee_structure.php?session_id={$session_id}&class_id={$class_id}");
}

// Handle form submission for adding a fee to the structure
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_fee_structure'])) {
    $fee_type_id = (int)$_POST['fee_type_id'];
    $amount = trim($_POST['amount']);

    if (empty($fee_type_id) || !is_numeric($amount) || $amount < 0 || !$class_id || !$session_id) {
        $errors[] = "Fee Type must be selected and Amount must be a valid number.";
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO class_fee_structure (branch_id, session_id, class_id, fee_type_id, amount) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$branch_id, $session_id, $class_id, $fee_type_id, $amount]);
            $_SESSION['success_message'] = "Fee added to class structure successfully!";
            redirect("fee_structure.php?session_id={$session_id}&class_id={$class_id}");
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // Handle unique constraint violation
                $errors[] = "This fee type is already assigned to this class for this session.";
            } else {
                $errors[] = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// Fetch fee types for the "Add Fee" dropdown if a session is selected
$fee_types = [];
$structure = [];
if ($session_id) {
    $stmt_fee_types = $db->prepare("SELECT id, name FROM fee_types WHERE branch_id = ? AND session_id = ? ORDER BY name ASC");
    $stmt_fee_types->execute([$branch_id, $session_id]);
    $fee_types = $stmt_fee_types->fetchAll();
}

// Fetch existing fee structure for the selected class and session
if ($class_id && $session_id) {
    $stmt_structure = $db->prepare("
        SELECT cfs.id, ft.name as fee_type_name, fg.name as group_name, cfs.amount
        FROM class_fee_structure cfs
        JOIN fee_types ft ON cfs.fee_type_id = ft.id
        JOIN fee_groups fg ON ft.group_id = fg.id
        WHERE cfs.class_id = ? AND cfs.branch_id = ? AND cfs.session_id = ?
        ORDER BY fg.name, ft.name
    ");
    $stmt_structure->execute([$class_id, $branch_id, $session_id]);
    $structure = $stmt_structure->fetchAll();
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
        <li class="breadcrumb-item active">Fee Structure</li>
    </ol>

    <?php display_flash_messages(); ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-filter me-1"></i> Select Session and Class</div>
        <div class="card-body">
            <form action="" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-5">
                        <label>Session</label>
                        <select name="session_id" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Select Session --</option>
                            <?php foreach ($sessions as $session) echo "<option value='{$session['id']}' " . ($session_id == $session['id'] ? 'selected' : '') . ">" . htmlspecialchars($session['name']) . "</option>"; ?>
                        </select>
                    </div>
                    <?php if ($session_id): ?>
                    <div class="col-md-5">
                        <label>Class</label>
                        <select name="class_id" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $class) echo "<option value='{$class['id']}' " . ($class_id == $class['id'] ? 'selected' : '') . ">" . htmlspecialchars($class['name']) . "</option>"; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 mt-3 mt-md-0">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (!$session_id): ?>
        <div class="alert alert-info">Please select an academic session to begin.</div>
    <?php elseif (!$class_id): ?>
        <div class="alert alert-info">Please select a class to manage its fee structure.</div>
    <?php else: ?>
    <div class="row">
        <!-- Add Fee Form -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-plus me-1"></i> Add Fee to Class</div>
                <div class="card-body">
                    <form action="fee_structure.php?session_id=<?php echo $session_id; ?>&class_id=<?php echo $class_id; ?>" method="POST">
                        <div class="mb-3"><label>Fee Type*</label>
                            <select name="fee_type_id" class="form-select" required>
                                <option value="">-- Select Fee Type --</option>
                                <?php foreach ($fee_types as $type) echo "<option value='{$type['id']}'>" . htmlspecialchars($type['name']) . "</option>"; ?>
                            </select>
                        </div>
                        <div class="mb-3"><label>Amount*</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
                        <button type="submit" name="add_fee_structure" class="btn btn-primary">Add Fee</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Fee Structure List -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-list-alt me-1"></i> Current Fee Structure</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead><tr><th>Fee Group</th><th>Fee Type</th><th>Amount</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($structure)): ?>
                                    <tr><td colspan="4" class="text-center">No fees assigned to this class for this session yet.</td></tr>
                                <?php else: foreach ($structure as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['group_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['fee_type_name']); ?></td>
                                    <td><?php echo number_format($item['amount'], 2); ?></td>
                                    <td><a href="fee_structure.php?action=delete&id=<?php echo $item['id']; ?>&session_id=<?php echo $session_id; ?>&class_id=<?php echo $class_id; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');"><i class="fas fa-trash"></i></a></td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../../footer.php'; ?>
