<?php
$page_title = "Assign Teachers";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$errors = [];

// Handle form submission for adding a new assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_teacher'])) {
    $teacher_id = (int)$_POST['teacher_id'];
    $section_id = (int)$_POST['section_id'];
    $subject_id = (int)$_POST['subject_id'];

    if (empty($teacher_id) || empty($section_id) || empty($subject_id)) {
        $errors[] = "Teacher, Section, and Subject are all required.";
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO teacher_assignments (branch_id, teacher_id, section_id, subject_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$branch_id, $teacher_id, $section_id, $subject_id]);
            $_SESSION['success_message'] = "Teacher assigned successfully!";
            redirect('assign_teachers.php');
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') { // Integrity constraint violation (duplicate entry)
                $errors[] = "This teacher is already assigned to this section and subject.";
            } else {
                $errors[] = "Database Error: Could not assign teacher. " . $e->getMessage();
            }
        }
    }
}

// Fetch data for dropdowns
$stmt_teachers = $db->prepare("SELECT id, full_name FROM users WHERE branch_id = ? AND role = 'teacher' ORDER BY full_name ASC");
$stmt_teachers->execute([$branch_id]);
$teachers = $stmt_teachers->fetchAll();

$stmt_sections = $db->prepare("SELECT s.id, s.name as section_name, c.name as class_name FROM sections s JOIN classes c ON s.class_id = c.id WHERE s.branch_id = ? ORDER BY c.numeric_name, c.name, s.name ASC");
$stmt_sections->execute([$branch_id]);
$sections = $stmt_sections->fetchAll();

$stmt_subjects = $db->prepare("SELECT id, name, code FROM subjects WHERE branch_id = ? ORDER BY name ASC");
$stmt_subjects->execute([$branch_id]);
$subjects = $stmt_subjects->fetchAll();

// Fetch existing assignments to display in the table
$stmt_assignments = $db->prepare("
    SELECT ta.id, u.full_name as teacher_name, sub.name as subject_name, sec.name as section_name, c.name as class_name
    FROM teacher_assignments ta
    JOIN users u ON ta.teacher_id = u.id
    JOIN subjects sub ON ta.subject_id = sub.id
    JOIN sections sec ON ta.section_id = sec.id
    JOIN classes c ON sec.class_id = c.id
    WHERE ta.branch_id = ?
    ORDER BY u.full_name, c.name, sec.name, sub.name
");
$stmt_assignments->execute([$branch_id]);
$assignments = $stmt_assignments->fetchAll();

require_once '../../header.php';
?>

<?php require_once ROOT_PATH . '/sidebar_branchadmin.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">

    <?php if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $_SESSION['success_message'] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['success_message']); } ?>
    <?php if (!empty($errors)) { echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; } ?>

    <div class="row">
        <!-- Assign Teacher Form -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-user-plus me-1"></i> Create New Assignment</div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="mb-3"><label>Teacher*</label>
                            <select name="teacher_id" class="form-select" required>
                                <option value="">-- Select Teacher --</option>
                                <?php foreach ($teachers as $teacher) echo "<option value='{$teacher['id']}'>" . htmlspecialchars($teacher['full_name']) . "</option>"; ?>
                            </select>
                        </div>
                        <div class="mb-3"><label>Section*</label>
                            <select name="section_id" class="form-select" required>
                                <option value="">-- Select Section --</option>
                                <?php foreach ($sections as $section) echo "<option value='{$section['id']}'>" . htmlspecialchars($section['class_name'] . ' - ' . $section['section_name']) . "</option>"; ?>
                            </select>
                        </div>
                        <div class="mb-3"><label>Subject*</label>
                            <select name="subject_id" class="form-select" required>
                                <option value="">-- Select Subject --</option>
                                <?php foreach ($subjects as $subject) echo "<option value='{$subject['id']}'>" . htmlspecialchars($subject['name']) . "</option>"; ?>
                            </select>
                        </div>
                        <button type="submit" name="assign_teacher" class="btn btn-primary">Assign Teacher</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Assignments List -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-table me-1"></i> Current Assignments</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead><tr><th>Teacher</th><th>Class</th><th>Section</th><th>Subject</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($assignments as $assignment): ?>
                                <tr><td><?php echo htmlspecialchars($assignment['teacher_name']); ?></td><td><?php echo htmlspecialchars($assignment['class_name']); ?></td><td><?php echo htmlspecialchars($assignment['section_name']); ?></td><td><?php echo htmlspecialchars($assignment['subject_name']); ?></td><td><!-- Delete button here --></td></tr>
                                <?php endforeach; ?>
                                <?php if (empty($assignments)) echo '<tr><td colspan="5" class="text-center">No teachers have been assigned yet.</td></tr>'; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>