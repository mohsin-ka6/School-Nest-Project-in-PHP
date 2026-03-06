<?php
$page_title = "Manage Subjects";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$errors = [];

// Handle form submission for adding a new subject
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_subject'])) {
    $subject_name = trim($_POST['subject_name']);
    $subject_code = trim($_POST['subject_code']);
    $subject_type = trim($_POST['subject_type']);

    if (empty($subject_name)) {
        $errors[] = "Subject name is required.";
    }
    if (!in_array($subject_type, ['theory', 'practical', 'optional', 'mandatory'])) {
        $errors[] = "Invalid subject type selected.";
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO subjects (branch_id, name, code, type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$branch_id, $subject_name, $subject_code, $subject_type]);
            $_SESSION['success_message'] = "Subject '{$subject_name}' added successfully!";
            redirect('manage_subjects.php');
        } catch (PDOException $e) {
            $errors[] = "Database Error: Could not add subject. " . $e->getMessage();
        }
    }
}

// Fetch all subjects for this branch
$stmt = $db->prepare("SELECT * FROM subjects WHERE branch_id = ? ORDER BY name ASC");
$stmt->execute([$branch_id]);
$subjects = $stmt->fetchAll();

require_once '../../header.php';
?>

<?php require_once ROOT_PATH . '/sidebar_branchadmin.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">

    <?php if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $_SESSION['success_message'] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['success_message']); } ?>
    <?php if (!empty($errors)) { echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; } ?>

    <div class="row">
        <!-- Add Subject Form -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-book me-1"></i> Add New Subject</div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="mb-3"><label>Subject Name*</label><input type="text" name="subject_name" class="form-control" required></div>
                        <div class="mb-3"><label>Subject Code (e.g., MATH101)</label><input type="text" name="subject_code" class="form-control"></div>
                        <div class="mb-3"><label>Subject Type*</label>
                            <select name="subject_type" class="form-select" required>
                                <option value="mandatory">Mandatory</option>
                                <option value="optional">Optional</option>
                                <option value="theory">Theory</option>
                                <option value="practical">Practical</option>
                            </select>
                        </div>
                        <button type="submit" name="add_subject" class="btn btn-primary">Add Subject</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Subjects List -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-table me-1"></i> Subjects List</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead><tr><th>Subject Name</th><th>Subject Code</th><th>Type</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($subjects as $subject): ?>
                                <tr><td><?php echo htmlspecialchars($subject['name']); ?></td><td><?php echo htmlspecialchars($subject['code']); ?></td><td><?php echo ucfirst($subject['type']); ?></td><td><!-- Edit/Delete buttons here --></td></tr>
                                <?php endforeach; ?>
                                <?php if (empty($subjects)) echo '<tr><td colspan="4" class="text-center">No subjects found.</td></tr>'; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>