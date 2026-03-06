<?php
$page_title = "Promote Students";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$errors = [];

// Fetch academic sessions for dropdowns
$stmt_sessions = $db->prepare("SELECT id, name FROM academic_sessions WHERE branch_id = ? ORDER BY start_date DESC");
$stmt_sessions->execute([$branch_id]);
$sessions = $stmt_sessions->fetchAll();

// Fetch classes for dropdowns
$stmt_classes = $db->prepare("SELECT id, name FROM classes WHERE branch_id = ? ORDER BY numeric_name ASC, name ASC");
$stmt_classes->execute([$branch_id]);
$classes = $stmt_classes->fetchAll();

$students_to_promote = [];
$current_class_id = isset($_POST['current_class_id']) ? (int)$_POST['current_class_id'] : 0;
$current_section_id = isset($_POST['current_section_id']) ? (int)$_POST['current_section_id'] : 0;
$current_session_id = isset($_POST['current_session_id']) ? (int)$_POST['current_session_id'] : 0;

// Handle "Manage" button click to fetch students
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['manage_promotion'])) {
    if ($current_session_id && $current_class_id && $current_section_id) {
        $stmt = $db->prepare("
            SELECT s.id, se.roll_no, u.full_name
            FROM student_enrollments se
            JOIN students s ON se.student_id = s.id
            JOIN users u ON s.user_id = u.id
            WHERE se.session_id = ? AND se.class_id = ? AND se.section_id = ? AND s.branch_id = ?
            ORDER BY se.roll_no, u.full_name
        ");
        $stmt->execute([$current_session_id, $current_class_id, $current_section_id, $branch_id]);
        $students_to_promote = $stmt->fetchAll();
    } else {
        $errors[] = "Please select both a current class and section.";
    }
}

// Handle "Promote" button click
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['promote_students'])) {
    $promote_to_session_id = (int)$_POST['promote_to_session_id'];
    $promote_to_class_id = (int)$_POST['promote_to_class_id'];
    $promote_to_section_id = (int)$_POST['promote_to_section_id'];
    $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];

    if (empty($promote_to_session_id) || empty($promote_to_class_id) || empty($promote_to_section_id)) {
        $errors[] = "Please select the Session, Class, and Section to promote to.";
    }
    if (empty($student_ids)) {
        $errors[] = "No students selected for promotion.";
    }
    if ($promote_to_session_id == $current_session_id) {
        $errors[] = "Cannot promote students to the same session they are already in.";
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();
            // Insert new enrollment records for the new session
            $stmt = $db->prepare("INSERT INTO student_enrollments (session_id, student_id, class_id, section_id) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE class_id=VALUES(class_id), section_id=VALUES(section_id)");
            foreach ($student_ids as $student_id) {
                $stmt->execute([$promote_to_session_id, (int)$student_id, $promote_to_class_id, $promote_to_section_id]);
            }
            $db->commit();
            $_SESSION['success_message'] = count($student_ids) . " student(s) promoted successfully!";
            redirect('promote_student.php');
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Database Error: Could not promote students. " . $e->getMessage();
        }
    }
}

require_once '../../header.php';
?>

<?php require_once '../../sidebar_branchadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">

    <?php if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>'; unset($_SESSION['success_message']); } ?>
    <?php if (!empty($errors)) { echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; } ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-user-graduate me-1"></i> Select Class for Promotion</div>
        <div class="card-body">
            <form action="" method="POST">
                <p class="text-muted">Select the session and class you want to promote students FROM.</p>
                <div class="row">
                    <div class="col-md-4">
                        <label>Current Session</label>
                        <select name="current_session_id" class="form-select" required>
                            <option value="">-- Select Session --</option>
                            <?php foreach ($sessions as $session) echo "<option value='{$session['id']}' " . ($current_session_id == $session['id'] ? 'selected' : '') . ">" . htmlspecialchars($session['name']) . "</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Current Class</label>
                        <select name="current_class_id" id="class_id" class="form-select" required>
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $class) echo "<option value='{$class['id']}' " . ($current_class_id == $class['id'] ? 'selected' : '') . ">" . htmlspecialchars($class['name']) . "</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Current Section</label>
                        <select name="current_section_id" id="section_id" class="form-select" required>
                            <option value="">-- Select Class First --</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" name="manage_promotion" class="btn btn-info w-100">Manage</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($students_to_promote)): ?>
    <form action="" method="POST">
        <input type="hidden" name="current_class_id" value="<?php echo $current_class_id; ?>">
        <input type="hidden" name="current_section_id" value="<?php echo $current_section_id; ?>">
        <input type="hidden" name="current_session_id" value="<?php echo $current_session_id; ?>">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-list me-1"></i> Students to Promote</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead><tr><th><input type="checkbox" id="checkAll"></th><th>Roll No</th><th>Student Name</th></tr></thead>
                        <tbody>
                            <?php foreach ($students_to_promote as $student): ?>
                            <tr>
                                <td><input type="checkbox" name="student_ids[]" value="<?php echo $student['id']; ?>" checked></td>
                                <td><?php echo htmlspecialchars($student['roll_no']); ?></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <p class="text-muted">Select the session and class you want to promote students TO.</p>
                <div class="row align-items-end">
                    <div class="col-md-3"><label>Promote to Session</label><select name="promote_to_session_id" class="form-select" required><option value="">-- Select Session --</option><?php foreach ($sessions as $session) echo "<option value='{$session['id']}'>" . htmlspecialchars($session['name']) . "</option>"; ?></select></div>
                    <div class="col-md-3"><label>Promote to Class</label><select name="promote_to_class_id" id="promote_class_id" class="form-select" required><option value="">-- Select Class --</option><?php foreach ($classes as $class) echo "<option value='{$class['id']}'>" . htmlspecialchars($class['name']) . "</option>"; ?></select></div>
                    <div class="col-md-3"><label>Promote to Section</label><select name="promote_to_section_id" id="promote_section_id" class="form-select" required><option value="">-- Select Class First --</option></select></div>
                    <div class="col-md-3"><button type="submit" name="promote_students" class="btn btn-success w-100">Promote Selected Students</button></div>
                </div>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('class_id');
    const sectionSelect = document.getElementById('section_id');
    const currentSectionId = '<?php echo $current_section_id; ?>';

    function fetchSections(classId, targetSelect, selectedId) {
        if (!classId) {
            targetSelect.innerHTML = '<option value="">-- Select Class First --</option>';
            return;
        }
        targetSelect.innerHTML = '<option value="">Loading...</option>';
        fetch(`<?php echo BASE_URL; ?>/api/get_sections.php?class_id=${classId}`)
            .then(response => response.json())
            .then(data => {
                targetSelect.innerHTML = '<option value="">-- Select Section --</option>';
                data.forEach(section => {
                    const selected = section.id == selectedId ? 'selected' : '';
                    targetSelect.innerHTML += `<option value="${section.id}" ${selected}>${section.name}</option>`;
                });
            });
    }

    classSelect.addEventListener('change', () => fetchSections(classSelect.value, sectionSelect, null));

    // For "Promote To" dropdowns
    const promoteClassSelect = document.getElementById('promote_class_id');
    const promoteSectionSelect = document.getElementById('promote_section_id');
    if (promoteClassSelect) {
        promoteClassSelect.addEventListener('change', () => fetchSections(promoteClassSelect.value, promoteSectionSelect, null));
    }

    // Initial load for current section if class is already selected
    if (classSelect.value) {
        fetchSections(classSelect.value, sectionSelect, currentSectionId);
    }

    // Check/uncheck all
    const checkAll = document.getElementById('checkAll');
    if(checkAll) {
        checkAll.addEventListener('change', function() {
            document.querySelectorAll('input[name="student_ids[]"]').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
});
</script>

<?php require_once '../../footer.php'; ?>
```

This completes the "Promote Student" functionality. The Branch Admin can now efficiently manage student progression at the end of each academic year.

<!--
[PROMPT_SUGGESTION]Let's start the "Exams" module by creating the `exam_types.php` and `marks_grade.php` pages.[/PROMPT_SUGGESTION]
[PROMPT_SUGGESTION]Now, let's create the `class_routine.php` page to build timetables.[/PROMPT_SUGGESTION]
