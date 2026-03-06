<?php
$page_title = "Tabulation Sheet";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];

// Fetch sessions
$stmt_sessions = $db->prepare("SELECT id, name FROM academic_sessions WHERE branch_id = ? ORDER BY start_date DESC");
$stmt_sessions->execute([$branch_id]);
$sessions = $stmt_sessions->fetchAll();

$stmt_classes = $db->prepare("SELECT id, name FROM classes WHERE branch_id = ? ORDER BY numeric_name");
$stmt_classes->execute([$branch_id]);
$classes = $stmt_classes->fetchAll();

// Determine which session to view, defaulting to the current one
$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
if (!$session_id && !empty($sessions)) {
    $stmt_current_session = $db->prepare("SELECT id FROM academic_sessions WHERE branch_id = ? AND is_current = 1");
    $stmt_current_session->execute([$branch_id]);
    $current_session = $stmt_current_session->fetch();
    $session_id = $current_session ? $current_session['id'] : $sessions[0]['id'];
}

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;

$exam_types = [];
if ($session_id) {
    $stmt_exams = $db->prepare("SELECT id, name FROM exam_types WHERE session_id = ? AND branch_id = ? ORDER BY name");
    $stmt_exams->execute([$session_id, $branch_id]);
    $exam_types = $stmt_exams->fetchAll();
}

$scheduled_subjects = [];
$tabulation_data = [];

if ($session_id && $exam_id && $class_id && $section_id) {
    // 1. Get scheduled subjects for the header
    $stmt_subjects = $db->prepare("
        SELECT es.subject_id, s.name as subject_name, es.full_marks, es.pass_marks
        FROM exam_schedule es
        JOIN subjects s ON es.subject_id = s.id
        WHERE es.session_id = ? AND es.exam_type_id = ? AND es.class_id = ? AND es.branch_id = ?
        ORDER BY s.name
    ");
    $stmt_subjects->execute([$session_id, $exam_id, $class_id, $branch_id]);
    $scheduled_subjects = $stmt_subjects->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($scheduled_subjects)) {
        // 2. Get students in the section
        $stmt_students = $db->prepare("
            SELECT s.id as student_id, u.full_name, se.roll_no
            FROM student_enrollments se
            JOIN students s ON se.student_id = s.id
            JOIN users u ON s.user_id = u.id
            WHERE se.session_id = ? AND se.section_id = ? AND s.branch_id = ?
            ORDER BY se.roll_no, u.full_name
        ");
        $stmt_students->execute([$session_id, $section_id, $branch_id]);
        $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

        // 3. Get all marks for this exam/class/section
        $stmt_marks = $db->prepare("
            SELECT em.student_id, em.subject_id, em.marks_obtained, em.attendance_status
            FROM exam_marks em
            JOIN exam_schedule es ON em.exam_schedule_id = es.id
            WHERE es.session_id = ? AND es.exam_type_id = ? AND em.class_id = ? AND em.section_id = ? AND em.branch_id = ?
        ");
        $stmt_marks->execute([$session_id, $exam_id, $class_id, $section_id, $branch_id]);
        $all_marks = $stmt_marks->fetchAll(PDO::FETCH_ASSOC);

        // 4. Create a lookup array for marks
        $marks_lookup = [];
        foreach ($all_marks as $mark) {
            $marks_lookup[$mark['student_id']][$mark['subject_id']] = ['marks' => $mark['marks_obtained'], 'attendance' => $mark['attendance_status']];
        }

        // 5. Get grading scale
        $stmt_grades = $db->prepare("SELECT * FROM marks_grades WHERE branch_id = ? ORDER BY percent_from DESC");
        $stmt_grades->execute([$branch_id]);
        $grades = $stmt_grades->fetchAll(PDO::FETCH_ASSOC);

        // 6. Prepare data for tabulation
        foreach ($students as $student) {
            $total_marks_obtained = 0;
            $total_full_marks = 0;
            $is_fail = false;
            $student_marks = [];

            foreach ($scheduled_subjects as $subject) {
                $subject_id = $subject['subject_id'];
                $marks_info = $marks_lookup[$student['student_id']][$subject_id] ?? ['marks' => null, 'attendance' => 'present'];
                $student_marks[$subject_id] = $marks_info;

                if ($marks_info['attendance'] == 'present' && $marks_info['marks'] !== null) {
                    $total_marks_obtained += $marks_info['marks'];
                    if ($marks_info['marks'] < $subject['pass_marks']) $is_fail = true;
                }
                $total_full_marks += $subject['full_marks'];
            }

            $percentage = ($total_full_marks > 0) ? ($total_marks_obtained / $total_full_marks) * 100 : 0;
            $final_grade = 'N/A';
            foreach ($grades as $grade) {
                if ($percentage >= $grade['percent_from'] && $percentage <= $grade['percent_upto']) {
                    $final_grade = $grade['grade_name'];
                    break;
                }
            }

            $tabulation_data[$student['student_id']] = [
                'info' => $student, 'marks' => $student_marks, 'total_obtained' => $total_marks_obtained,
                'total_full' => $total_full_marks, 'percentage' => $percentage, 'grade' => $final_grade,
                'result' => ($is_fail) ? 'Fail' : 'Pass'
            ];
        }
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
        <li class="breadcrumb-item active">Tabulation Sheet</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-search me-1"></i> Select Criteria</div>
        <div class="card-body">
            <form action="" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-3"><label>Session</label><select name="session_id" id="session_id_filter" class="form-select" required><option value="">-- Select Session --</option><?php foreach ($sessions as $session) echo "<option value='{$session['id']}' " . ($session_id == $session['id'] ? 'selected' : '') . ">" . htmlspecialchars($session['name']) . "</option>"; ?></select></div>
                    <div class="col-md-3"><label>Exam</label><select name="exam_id" id="exam_id_filter" class="form-select" required><option value="">-- Select Session First --</option><?php foreach ($exam_types as $exam) echo "<option value='{$exam['id']}' " . ($exam_id == $exam['id'] ? 'selected' : '') . ">" . htmlspecialchars($exam['name']) . "</option>"; ?></select></div>
                    <div class="col-md-2"><label>Class</label><select name="class_id" id="class_id" class="form-select" required><option value="">-- Select Class --</option><?php foreach ($classes as $class) echo "<option value='{$class['id']}' " . ($class_id == $class['id'] ? 'selected' : '') . ">" . htmlspecialchars($class['name']) . "</option>"; ?></select></div>
                    <div class="col-md-2"><label>Section</label><select name="section_id" id="section_id" class="form-select" required><option value="">-- Select Class First --</option></select></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">View Sheet</button></div>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($tabulation_data)): ?>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-table me-1"></i> Marksheet</span>
            <a href="print_tabulation_sheet.php?session_id=<?php echo $session_id; ?>&exam_id=<?php echo $exam_id; ?>&class_id=<?php echo $class_id; ?>&section_id=<?php echo $section_id; ?>" target="_blank" class="btn btn-secondary btn-sm">
                <i class="fas fa-print me-1"></i> Print
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover text-center">
                    <thead>
                        <tr class="bg-light">
                            <th>Roll No</th><th class="text-start">Student Name</th>
                            <?php foreach ($scheduled_subjects as $subject) echo "<th>" . htmlspecialchars($subject['subject_name']) . " (" . $subject['full_marks'] . ")</th>"; ?>
                            <th>Total Marks</th><th>Percentage</th><th>Grade</th><th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tabulation_data as $data): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($data['info']['roll_no']); ?></td>
                            <td class="text-start"><?php echo htmlspecialchars($data['info']['full_name']); ?></td>
                            <?php foreach ($scheduled_subjects as $subject): ?>
                                <td>
                                    <?php 
                                    $marks_info = $data['marks'][$subject['subject_id']];
                                    if ($marks_info['attendance'] == 'absent') echo '<span class="badge bg-danger">Absent</span>';
                                    else echo htmlspecialchars($marks_info['marks'] ?? '-');
                                    ?>
                                </td>
                            <?php endforeach; ?>
                            <td><?php echo $data['total_obtained'] . ' / ' . $data['total_full']; ?></td>
                            <td><?php echo round($data['percentage'], 2); ?>%</td>
                            <td><?php echo htmlspecialchars($data['grade']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $data['result'] == 'Pass' ? 'success' : 'danger'; ?>"><?php echo $data['result']; ?></span>
                                <a href="print_report_card.php?session_id=<?php echo $session_id; ?>&exam_id=<?php echo $exam_id; ?>&student_id=<?php echo $data['info']['student_id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary ms-2" title="View Report Card">
                                    <i class="fas fa-id-card"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php elseif($session_id && $exam_id && $class_id && $section_id): ?>
        <div class="alert alert-warning">No exam schedule or students found for the selected criteria.</div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('class_id');
    const sectionSelect = document.getElementById('section_id');
    const currentSectionId = '<?php echo $section_id; ?>';
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

    classSelect.addEventListener('change', () => fetchSections(classSelect.value, sectionSelect, null));
    sessionFilter.addEventListener('change', function() {
        // Clear dependent dropdowns and submit form to reload exams
        document.getElementById('exam_id_filter').innerHTML = '';
        this.form.submit();
    });

    if (classSelect.value) fetchSections(classSelect.value, sectionSelect, currentSectionId);
    if (sessionFilter.value) fetchExams(sessionFilter.value, examFilter, currentExamId);
});
</script>

<?php require_once '../../footer.php'; ?>