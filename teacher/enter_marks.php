<?php
$page_title = "Enter Marks";
require_once '../config.php';
require_once '../functions.php';

check_role('teacher');

$teacher_id = $_SESSION['user_id'];
$branch_id = $_SESSION['branch_id'];
$errors = [];

// Fetch classes/sections assigned to this teacher
$stmt_assigned = $db->prepare("
    SELECT DISTINCT c.id as class_id, c.name as class_name, sec.id as section_id, sec.name as section_name
    FROM teacher_assignments ta
    JOIN sections sec ON ta.section_id = sec.id
    JOIN classes c ON sec.class_id = c.id
    WHERE ta.teacher_id = ?
    ORDER BY c.numeric_name, sec.name
");
$stmt_assigned->execute([$teacher_id]);
$assigned_sections = $stmt_assigned->fetchAll();

// Fetch exam types for the branch
$stmt_exams = $db->prepare("SELECT id, name FROM exam_types WHERE branch_id = ? ORDER BY name");
$stmt_exams->execute([$branch_id]);
$exam_types = $stmt_exams->fetchAll();

$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

$students = [];
$schedule_info = null;

if ($section_id && $exam_id && $subject_id) {
    // Get class_id from section_id
    $stmt_class = $db->prepare("SELECT class_id FROM sections WHERE id = ?");
    $stmt_class->execute([$section_id]);
    $class_id = $stmt_class->fetchColumn();

    // Find the corresponding exam_schedule_id
    $stmt_schedule = $db->prepare("SELECT id, full_marks FROM exam_schedule WHERE exam_type_id = ? AND class_id = ? AND subject_id = ? AND branch_id = ?");
    $stmt_schedule->execute([$exam_id, $class_id, $subject_id, $branch_id]);
    $schedule_info = $stmt_schedule->fetch();

    if ($schedule_info) {
        $exam_schedule_id = $schedule_info['id'];

        // Fetch students of the section and their existing marks for this exam schedule
        $stmt_students = $db->prepare("
            SELECT s.id as student_id, u.full_name, s.roll_no, em.marks_obtained, em.attendance_status
            FROM students s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN exam_marks em ON s.id = em.student_id AND em.exam_schedule_id = ?
            WHERE s.section_id = ? AND s.branch_id = ?
            ORDER BY s.roll_no, u.full_name
        ");
        $stmt_students->execute([$exam_schedule_id, $section_id, $branch_id]);
        $students = $stmt_students->fetchAll();
    } else {
        $errors[] = "This subject is not scheduled for the selected exam and class.";
    }
}

// Handle form submission for saving marks
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_marks'])) {
    $marks_data = $_POST['marks'];
    $attendance_data = $_POST['attendance'];
    $post_exam_schedule_id = (int)$_POST['exam_schedule_id'];
    $post_class_id = (int)$_POST['class_id'];
    $post_section_id = (int)$_POST['section_id'];
    $post_subject_id = (int)$_POST['subject_id'];

    // Security Check: Verify teacher is assigned to this subject/section
    $verify_stmt = $db->prepare("SELECT id FROM teacher_assignments WHERE teacher_id = ? AND section_id = ? AND subject_id = ?");
    $verify_stmt->execute([$teacher_id, $post_section_id, $post_subject_id]);
    if (!$verify_stmt->fetch()) {
        $errors[] = "You are not authorized to enter marks for this subject.";
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("
                INSERT INTO exam_marks (branch_id, student_id, exam_schedule_id, class_id, section_id, subject_id, marks_obtained, attendance_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE marks_obtained = VALUES(marks_obtained), attendance_status = VALUES(attendance_status)
            ");

            foreach ($marks_data as $student_id => $marks) {
                $attendance = $attendance_data[$student_id] ?? 'present';
                $marks_to_save = ($attendance == 'absent') ? 0 : ($marks ?: 0);
                $stmt->execute([$branch_id, $student_id, $post_exam_schedule_id, $post_class_id, $post_section_id, $post_subject_id, $marks_to_save, $attendance]);
            }

            $db->commit();
            $_SESSION['success_message'] = "Marks saved successfully!";
            redirect("enter_marks.php?section_id={$post_section_id}&exam_id={$exam_id}&subject_id={$post_subject_id}");
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

require_once '../header.php';
?>

<?php require_once 'sidebar_teacher.php'; ?>

<div id="page-content-wrapper">
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
        <div class="container-fluid">
            <button class="btn btn-success" id="menu-toggle"><i class="fas fa-bars"></i></button>
            <div class="ms-auto">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</div>
        </div>
    </nav>

    <div class="container-fluid px-4">
        <h1 class="mt-4"><?php echo $page_title; ?></h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Enter Marks</li>
        </ol>

        <?php if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $_SESSION['success_message'] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['success_message']); } ?>
        <?php if (!empty($errors)) { echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; } ?>

        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-search me-1"></i> Select Criteria</div>
            <div class="card-body">
                <form action="" method="GET">
                    <div class="row align-items-end">
                        <div class="col-md-3"><label>Class - Section</label><select name="section_id" id="section_id" class="form-select" required><option value="">-- Select Section --</option><?php foreach ($assigned_sections as $sec) echo "<option value='{$sec['section_id']}' " . ($section_id == $sec['section_id'] ? 'selected' : '') . ">" . htmlspecialchars($sec['class_name'] . ' - ' . $sec['section_name']) . "</option>"; ?></select></div>
                        <div class="col-md-3"><label>Exam Type</label><select name="exam_id" class="form-select" required><option value="">-- Select Exam --</option><?php foreach ($exam_types as $exam) echo "<option value='{$exam['id']}' " . ($exam_id == $exam['id'] ? 'selected' : '') . ">" . htmlspecialchars($exam['name']) . "</option>"; ?></select></div>
                        <div class="col-md-3"><label>Subject</label><select name="subject_id" id="subject_id" class="form-select" required><option value="">-- Select Section First --</option></select></div>
                        <div class="col-md-3"><button type="submit" class="btn btn-primary w-100">Load Students</button></div>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($students)): ?>
            <form action="" method="POST">
                <input type="hidden" name="exam_schedule_id" value="<?php echo $schedule_info['id']; ?>">
                <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                <input type="hidden" name="section_id" value="<?php echo $section_id; ?>">
                <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
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
                                                <select name="attendance[<?php echo $student['student_id']; ?>]" class="form-select form-select-sm">
                                                    <option value="present" <?php echo ($student['attendance_status'] ?? 'present') == 'present' ? 'selected' : ''; ?>>Present</option>
                                                    <option value="absent" <?php echo ($student['attendance_status'] ?? '') == 'absent' ? 'selected' : ''; ?>>Absent</option>
                                                </select>
                                            </td>
                                            <td><input type="number" step="0.01" name="marks[<?php echo $student['student_id']; ?>]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($student['marks_obtained']); ?>" max="<?php echo htmlspecialchars($schedule_info['full_marks']); ?>"></td>
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
        <?php elseif($section_id && $exam_id && $subject_id): ?>
            <div class="alert alert-warning">No students found for the selected criteria, or the subject is not scheduled for this exam.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sectionSelect = document.getElementById('section_id');
    const subjectSelect = document.getElementById('subject_id');
    const currentSubjectId = '<?php echo $subject_id; ?>';

    function fetchSubjectsForTeacher(sectionId, targetSelect, selectedId) {
        if (!sectionId) { targetSelect.innerHTML = '<option value="">-- Select Section First --</option>'; return; }        
        fetch(`<?php echo BASE_URL; ?>/api/get_teacher_subjects_by_section.php?section_id=${sectionId}`)
            .then(response => response.json()).then(data => {
                targetSelect.innerHTML = '<option value="">-- Select Subject --</option>';
                data.forEach(subject => {
                    const selected = subject.id == selectedId ? 'selected' : '';
                    targetSelect.innerHTML += `<option value="${subject.id}" ${selected}>${subject.name}</option>`;
                });
            });
    }

    sectionSelect.addEventListener('change', () => {
        fetchSubjectsForTeacher(sectionSelect.value, subjectSelect, null);
    });

    if (sectionSelect.value) {
        const selectedOption = sectionSelect.options[sectionSelect.selectedIndex];
        const classId = selectedOption ? selectedOption.dataset.classId : null;
        fetchSubjectsForTeacher(sectionSelect.value, subjectSelect, currentSubjectId);
    }
});
</script>