<?php
$page_title = "Assign Fee Concessions";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$errors = [];

// --- FILTERS ---
$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;

// Fetch data for filter dropdowns
$stmt_sessions = $db->prepare("SELECT id, name FROM academic_sessions WHERE branch_id = ? ORDER BY start_date DESC");
$stmt_sessions->execute([$branch_id]);
$sessions = $stmt_sessions->fetchAll();

$stmt_classes = $db->prepare("SELECT id, name FROM classes WHERE branch_id = ? ORDER BY numeric_name ASC");
$stmt_classes->execute([$branch_id]);
$classes = $stmt_classes->fetchAll();

$stmt_concessions = $db->prepare("SELECT id, name, type, value FROM fee_concession_types WHERE branch_id = ? ORDER BY name ASC");
$stmt_concessions->execute([$branch_id]);
$concession_types = $stmt_concessions->fetchAll();

// Default to current session if none is selected
if (!$session_id && !empty($sessions)) {
    $stmt_current_session = $db->prepare("SELECT id FROM academic_sessions WHERE branch_id = ? AND is_current = 1");
    $stmt_current_session->execute([$branch_id]);
    $session_id = $stmt_current_session->fetchColumn();
}

// Handle form submission for assigning concessions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_concessions'])) {
    $posted_session_id = (int)$_POST['session_id'];
    $concessions = $_POST['concessions'] ?? [];
    $notes = $_POST['notes'] ?? [];

    if ($posted_session_id > 0) {
        try {
            $db->beginTransaction();

            $stmt_upsert = $db->prepare(
                "INSERT INTO student_concessions (student_id, session_id, concession_type_id, notes) 
                 VALUES (?, ?, ?, ?) 
                 ON DUPLICATE KEY UPDATE concession_type_id = VALUES(concession_type_id), notes = VALUES(notes)"
            );
            $stmt_delete = $db->prepare("DELETE FROM student_concessions WHERE student_id = ? AND session_id = ?");

            foreach ($concessions as $student_id => $concession_type_id) {
                $student_id = (int)$student_id;
                $concession_type_id = (int)$concession_type_id;
                $student_notes = trim($notes[$student_id] ?? '');

                if ($concession_type_id > 0) {
                    $stmt_upsert->execute([$student_id, $posted_session_id, $concession_type_id, $student_notes]);
                } else {
                    $stmt_delete->execute([$student_id, $posted_session_id]);
                }
            }

            $db->commit();
            $_SESSION['success_message'] = "Concessions updated successfully for the selected section.";
            redirect("assign_concessions.php?session_id={$session_id}&class_id={$class_id}&section_id={$section_id}");
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Database Error: " . $e->getMessage();
        }
    } else {
        $errors[] = "Session ID was missing from the submission.";
    }
}

// Fetch students if all filters are set
$students = [];
if ($session_id && $class_id && $section_id) {
    $stmt_students = $db->prepare("
        SELECT 
            s.id as student_id, u.full_name, se.roll_no, sc.concession_type_id, sc.notes
        FROM student_enrollments se
        JOIN students s ON se.student_id = s.id
        JOIN users u ON s.user_id = u.id
        LEFT JOIN student_concessions sc ON (s.id = sc.student_id AND se.session_id = sc.session_id)
        WHERE se.session_id = ? AND se.class_id = ? AND se.section_id = ? AND s.branch_id = ?
        ORDER BY se.roll_no, u.full_name
    ");
    $stmt_students->execute([$session_id, $class_id, $section_id, $branch_id]);
    $students = $stmt_students->fetchAll();
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
        <li class="breadcrumb-item active">Assign Concessions</li>
    </ol>

    <?php display_flash_messages(); ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-filter me-1"></i> Filter Students</div>
        <div class="card-body">
            <form action="" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-3 mb-2"><label>Session</label>
                        <select name="session_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($sessions as $session) echo "<option value='{$session['id']}' " . ($session_id == $session['id'] ? 'selected' : '') . ">" . htmlspecialchars($session['name']) . "</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2"><label>Class</label>
                        <select name="class_id" id="class_id" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $class) echo "<option value='{$class['id']}' " . ($class_id == $class['id'] ? 'selected' : '') . ">" . htmlspecialchars($class['name']) . "</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2"><label>Section</label>
                        <select name="section_id" id="section_id" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Select Section --</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2"><button type="submit" class="btn btn-primary w-100">Search</button></div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($session_id && $class_id && $section_id): ?>
    <form action="" method="POST">
        <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-user-tag me-1"></i> Assign Concessions to Students</div>
            <div class="card-body">
                <?php if (empty($students)): ?>
                    <div class="alert alert-info">No students found for the selected section.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr><th>Roll No</th><th>Student Name</th><th>Concession Type</th><th>Notes</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['roll_no']); ?></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td>
                                    <select name="concessions[<?php echo $student['student_id']; ?>]" class="form-select form-select-sm">
                                        <option value="0">-- No Concession --</option>
                                        <?php foreach ($concession_types as $concession): ?>
                                            <option value="<?php echo $concession['id']; ?>" <?php echo ($student['concession_type_id'] == $concession['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($concession['name']); ?> (<?php echo ($concession['type'] == 'percentage') ? $concession['value'] . '%' : 'PKR ' . number_format($concession['value'], 2); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="text" name="notes[<?php echo $student['student_id']; ?>]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($student['notes'] ?? ''); ?>" placeholder="Optional notes..."></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($students)): ?>
            <div class="card-footer text-end">
                <button type="submit" name="assign_concessions" class="btn btn-success">Save All Assignments</button>
            </div>
            <?php endif; ?>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('class_id');
    const sectionSelect = document.getElementById('section_id');
    const currentSectionId = '<?php echo $section_id; ?>';

    function fetchSections(classId, targetSelect, selectedId) {
        if (!classId) {
            targetSelect.innerHTML = '<option value="">-- Select Section --</option>';
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

    if (classSelect.value) {
        fetchSections(classSelect.value, sectionSelect, currentSectionId);
    }
});
</script>

<?php require_once '../../footer.php'; ?>
