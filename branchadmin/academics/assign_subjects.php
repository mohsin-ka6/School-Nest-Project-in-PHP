<?php
$page_title = "Assign Subjects";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$errors = [];
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// Fetch all classes for the dropdown
$stmt_classes = $db->prepare("SELECT id, name FROM classes WHERE branch_id = ? ORDER BY numeric_name ASC, name ASC");
$stmt_classes->execute([$branch_id]);
$classes = $stmt_classes->fetchAll();

// Fetch all subjects for the multi-select box
$stmt_subjects = $db->prepare("SELECT id, name, code FROM subjects WHERE branch_id = ? ORDER BY name ASC");
$stmt_subjects->execute([$branch_id]);
$subjects = $stmt_subjects->fetchAll();

$assigned_subject_ids = [];
if ($class_id) {
    // Fetch subjects already assigned to the selected class
    $stmt_assigned = $db->prepare("SELECT subject_id FROM class_subjects WHERE class_id = ? AND branch_id = ?");
    $stmt_assigned->execute([$class_id, $branch_id]);
    $assigned_subject_ids = $stmt_assigned->fetchAll(PDO::FETCH_COLUMN);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_subjects'])) {
    $posted_class_id = (int)$_POST['class_id'];
    $posted_subject_ids = isset($_POST['subject_ids']) ? $_POST['subject_ids'] : [];

    if (empty($posted_class_id)) {
        $errors[] = "Please select a class.";
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // 1. Delete all existing assignments for this class
            $delete_stmt = $db->prepare("DELETE FROM class_subjects WHERE class_id = ? AND branch_id = ?");
            $delete_stmt->execute([$posted_class_id, $branch_id]);

            // 2. Insert the new assignments
            if (!empty($posted_subject_ids)) {
                $insert_stmt = $db->prepare("INSERT INTO class_subjects (branch_id, class_id, subject_id) VALUES (?, ?, ?)");
                foreach ($posted_subject_ids as $subject_id) {
                    $insert_stmt->execute([$branch_id, $posted_class_id, (int)$subject_id]);
                }
            }

            $db->commit();
            $_SESSION['success_message'] = "Subjects assigned successfully!";
            redirect("assign_subjects.php?class_id={$posted_class_id}");
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Database Error: Could not assign subjects. " . $e->getMessage();
        }
    }
}

require_once '../../header.php';
?>

<?php require_once ROOT_PATH . '/sidebar_branchadmin.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">

    <?php if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $_SESSION['success_message'] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['success_message']); } ?>
    <?php if (!empty($errors)) { echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; } ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-link me-1"></i> Assign Subjects to a Class</div>
        <div class="card-body">
            <form action="" method="GET" class="mb-4">
                <div class="row align-items-end">
                    <div class="col-md-6">
                        <label for="class_id_select" class="form-label">Select Class</label>
                        <select name="class_id" id="class_id_select" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Select a Class --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo ($class_id == $class['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>

            <?php if ($class_id): ?>
            <hr>
            <form action="" method="POST">
                <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                <div class="mb-3">
                    <label for="subject_ids" class="form-label">Select Subjects</label>
                    <p class="text-muted small">Hold down the Ctrl (windows) / Command (Mac) button to select multiple options.</p>
                    <select name="subject_ids[]" id="subject_ids" class="form-select" multiple size="10">
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['id']; ?>" <?php echo in_array($subject['id'], $assigned_subject_ids) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject['name']); ?> (<?php echo htmlspecialchars($subject['code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="assign_subjects" class="btn btn-primary">Save Assignments</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>