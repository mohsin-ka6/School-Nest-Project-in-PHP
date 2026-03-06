<?php
$page_title = "Marks Register";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$errors = [];

// Fetch data for dropdowns
$stmt_sessions = $db->prepare("SELECT id, name FROM academic_sessions WHERE branch_id = ? ORDER BY start_date DESC");
$stmt_sessions->execute([$branch_id]);
$sessions = $stmt_sessions->fetchAll();

$stmt_classes = $db->prepare("SELECT id, name FROM classes WHERE branch_id = ? ORDER BY numeric_name");
$stmt_classes->execute([$branch_id]);
$classes = $stmt_classes->fetchAll();

// Determine which session to view, defaulting to the current one
$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
if (!$session_id) {
    $stmt_current_session = $db->prepare("SELECT id FROM academic_sessions WHERE branch_id = ? AND is_current = 1");
    $stmt_current_session->execute([$branch_id]);
    $current_session = $stmt_current_session->fetch();
    if ($current_session) {
        $session_id = $current_session['id'];
    }
}

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

$exam_types = [];
if ($session_id) {
    $stmt_exams = $db->prepare("SELECT id, name FROM exam_types WHERE session_id = ? AND branch_id = ? ORDER BY name");
    $stmt_exams->execute([$session_id, $branch_id]);
    $exam_types = $stmt_exams->fetchAll();
}

$students = [];
$schedule_info = null;

if ($session_id && $exam_id && $class_id && $section_id && $subject_id) {
    // Find the corresponding exam_schedule_id and full_marks
    $stmt_schedule = $db->prepare("SELECT id, full_marks FROM exam_schedule WHERE session_id = ? AND exam_type_id = ? AND class_id = ? AND subject_id = ? AND branch_id = ?");
    $stmt_schedule->execute([$session_id, $exam_id, $class_id, $subject_id, $branch_id]);
    $schedule_info = $stmt_schedule->fetch();

    if ($schedule_info) {
        $exam_schedule_id = $schedule_info['id'];

        // Fetch students enrolled in the section for the current session, and their existing marks
        $stmt_students = $db->prepare("
            SELECT s.id as student_id, u.full_name, se.roll_no, em.marks_obtained, em.attendance_status
            FROM student_enrollments se
            JOIN students s ON se.student_id = s.id
            JOIN users u ON s.user_id = u.id
            LEFT JOIN exam_marks em ON s.id = em.student_id AND em.exam_schedule_id = ?
            WHERE se.session_id = ? AND se.section_id = ? AND s.branch_id = ?
            ORDER BY se.roll_no, u.full_name
        ");
        $stmt_students->execute([$exam_schedule_id, $session_id, $section_id, $branch_id]);
        $students = $stmt_students->fetchAll();
    } else {
        $errors[] = "This subject is not scheduled for the selected session, exam, and class.";
    }
}

// Handle form submission for saving marks
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_marks'])) {
    $marks_data = $_POST['marks'] ?? [];
    $attendance_data = $_POST['attendance'] ?? [];
    $post_exam_schedule_id = (int)$_POST['exam_schedule_id'];

    try {
        $db->beginTransaction();
        $stmt = $db->prepare("
            INSERT INTO exam_marks (branch_id, session_id, student_id, exam_schedule_id, class_id, section_id, subject_id, marks_obtained, attendance_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE marks_obtained = VALUES(marks_obtained), attendance_status = VALUES(attendance_status)
        ");

        foreach ($marks_data as $student_id => $marks) {
            $attendance = $attendance_data[$student_id] ?? 'present';
            $marks_to_save = ($attendance == 'absent') ? null : ($marks === '' ? null : $marks);
            $stmt->execute([$branch_id, $session_id, $student_id, $post_exam_schedule_id, $class_id, $section_id, $subject_id, $marks_to_save, $attendance]);
        }

        $db->commit();
        $_SESSION['success_message'] = "Marks saved successfully!";
        redirect("marks_register.php?session_id={$session_id}&exam_id={$exam_id}&class_id={$class_id}&section_id={$section_id}&subject_id={$subject_id}");
    } catch (PDOException $e) {
        $db->rollBack();
        $errors[] = "Database Error: " . $e->getMessage();
    }
}

require_once '../../header.php';
?>

<?php require_once '../../sidebar_branchadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Marks Register</li>
    </ol>

    <?php display_flash_messages(); ?>
    <?php if (!empty($errors)) { echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; } ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-search me-1"></i> Select Criteria to Enter Marks</div>
        <div class="card-body">
            <form action="" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-3"><label>Session</label><select name="session_id" id="session_id_filter" class="form-select" required><option value="">-- Select Session --</option><?php foreach ($sessions as $session) echo "<option value='{$session['id']}' " . ($session_id == $session['id'] ? 'selected' : '') . ">" . htmlspecialchars($session['name']) . "</option>"; ?></select></div>
                    <div class="col-md-2"><label>Exam</label><select name="exam_id" id="exam_id_filter" class="form-select" required><option value="">-- Select Session First --</option><?php foreach ($exam_types as $exam) echo "<option value='{$exam['id']}' " . ($exam_id == $exam['id'] ? 'selected' : '') . ">" . htmlspecialchars($exam['name']) . "</option>"; ?></select></div>
                    <div class="col-md-2"><label>Class</label><select name="class_id" id="class_id" class="form-select" required><option value="">-- Select Class --</option><?php foreach ($classes as $class) echo "<option value='{$class['id']}' " . ($class_id == $class['id'] ? 'selected' : '') . ">" . htmlspecialchars($class['name']) . "</option>"; ?></select></div>
                    <div class="col-md-2"><label>Section</label><select name="section_id" id="section_id" class="form-select" required><option value="">-- Select Class First --</option></select></div>
                    <div class="col-md-2"><label>Subject</label><select name="subject_id" id="subject_id" class="form-select" required><option value="">-- Select Class First --</option></select></div>
                    <div class="col-md-1"><button type="submit" class="btn btn-primary w-100">Load</button></div>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($students) && $schedule_info): ?>
    <form action="marks_register.php?session_id=<?php echo $session_id; ?>&exam_id=<?php echo $exam_id; ?>&class_id=<?php echo $class_id; ?>&section_id=<?php echo $section_id; ?>&subject_id=<?php echo $subject_id; ?>" method="POST">
        <input type="hidden" name="exam_schedule_id" value="<?php echo $schedule_info['id']; ?>">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-edit me-1"></i> Enter Marks (Out of <?php echo htmlspecialchars($schedule_info['full_marks']); ?>)</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead><tr><th>Roll No</th><th>Student Name</th><th>Attendance</th><th>Marks Obtained</th></tr></thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['roll_no']); ?></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td>
                                    <select name="attendance[<?php echo $student['student_id']; ?>]" class="form-select form-select-sm attendance-select">
                                        <option value="present" <?php echo ($student['attendance_status'] ?? 'present') == 'present' ? 'selected' : ''; ?>>Present</option>
                                        <option value="absent" <?php echo ($student['attendance_status'] ?? '') == 'absent' ? 'selected' : ''; ?>>Absent</option>
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" name="marks[<?php echo $student['student_id']; ?>]" class="form-control form-control-sm marks-input" value="<?php echo htmlspecialchars($student['marks_obtained']); ?>" max="<?php echo htmlspecialchars($schedule_info['full_marks']); ?>" <?php echo ($student['attendance_status'] ?? 'present') == 'absent' ? 'disabled' : ''; ?>></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end">
                <button type="submit" name="save_marks" class="btn btn-primary">Save Marks</button>
            </div>
        </div>
    </form>
    <?php elseif($session_id && $exam_id && $class_id && $section_id && $subject_id): ?>
        <div class="alert alert-info">No students found in the selected section, or the exam is not scheduled for this subject.</div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('class_id');
    const sectionSelect = document.getElementById('section_id');
    const subjectSelect = document.getElementById('subject_id');
    const currentSectionId = '<?php echo $section_id; ?>';
    const currentSubjectId = '<?php echo $subject_id; ?>';
    const sessionFilter = document.getElementById('session_id_filter');
    const examFilter = document.getElementById('exam_id_filter');
    const currentExamId = '<?php echo $exam_id; ?>';

    function fetchSections(classId, targetSelect, selectedId) {
        if (!classId) { targetSelect.innerHTML = '<option value="">-- Select Class First --</option>'; return; }
        fetch(`<?php echo BASE_URL; ?>/api/get_sections.php?class_id=${classId}`)
            .then(response => response.json()).then(data => {
                targetSelect.innerHTML = '<option value="">-- Select Section --</option>';
                data.forEach(section => {
                    const selected = section.id == selectedId ? 'selected' : '';
                    targetSelect.innerHTML += `<option value="${section.id}" ${selected}>${section.name}</option>`;
                });
            });
    }

    function fetchSubjects(classId, targetSelect, selectedId) {
        if (!classId) { targetSelect.innerHTML = '<option value="">-- Select Class First --</option>'; return; }
        fetch(`<?php echo BASE_URL; ?>/api/get_subjects.php?class_id=${classId}`)
            .then(response => response.json()).then(data => {
                targetSelect.innerHTML = '<option value="">-- Select Subject --</option>';
                data.forEach(subject => {
                    const selected = subject.id == selectedId ? 'selected' : '';
                    targetSelect.innerHTML += `<option value="${subject.id}" ${selected}>${subject.name}</option>`;
                });
            });
    }

    function fetchExams(sessionId, targetSelect, selectedId) {
        if (!sessionId) { targetSelect.innerHTML = '<option value="">-- Select Session First --</option>'; return; }
        fetch(`<?php echo BASE_URL; ?>/api/get_exams.php?session_id=${sessionId}`)
            .then(response => response.json()).then(data => {
                targetSelect.innerHTML = '<option value="">-- Select Exam --</option>';
                data.forEach(exam => {
                    const selected = exam.id == selectedId ? 'selected' : '';
                    targetSelect.innerHTML += `<option value="${exam.id}" ${selected}>${exam.name}</option>`;
                });
            });
    }

    classSelect.addEventListener('change', () => {
        fetchSections(classSelect.value, sectionSelect, null);
        fetchSubjects(classSelect.value, subjectSelect, null);
    });

    sessionFilter.addEventListener('change', function() {
        fetchExams(this.value, examFilter, null);
    });

    if (classSelect.value) {
        fetchSections(classSelect.value, sectionSelect, currentSectionId);
        fetchSubjects(classSelect.value, subjectSelect, currentSubjectId);
    }
    if (sessionFilter.value) {
        fetchExams(sessionFilter.value, examFilter, currentExamId);
    }

    document.querySelectorAll('.attendance-select').forEach(select => {
        select.addEventListener('change', function() {
            const marksInput = this.closest('tr').querySelector('.marks-input');
            if (this.value === 'absent') {
                marksInput.disabled = true;
                marksInput.value = '';
            } else {
                marksInput.disabled = false;
            }
        });
    });
});
</script>

<?php require_once '../../footer.php'; ?>