<?php
$page_title = "Exam Schedule";
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

$exam_types = [];
if ($session_id) {
    $stmt_exams = $db->prepare("SELECT id, name FROM exam_types WHERE session_id = ? AND branch_id = ? ORDER BY name");
    $stmt_exams->execute([$session_id, $branch_id]);
    $exam_types = $stmt_exams->fetchAll();
}

$subjects = [];
if ($class_id) {
    $stmt_subjects = $db->prepare("SELECT s.id, s.name FROM subjects s JOIN class_subjects cs ON s.id = cs.subject_id WHERE cs.class_id = ? ORDER BY s.name");
    $stmt_subjects->execute([$class_id]);
    $subjects = $stmt_subjects->fetchAll();
}

// Handle deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $schedule_id = (int)$_GET['id'];
    try {
        $stmt = $db->prepare("DELETE FROM exam_schedule WHERE id = ? AND branch_id = ?");
        $stmt->execute([$schedule_id, $branch_id]);
        $_SESSION['success_message'] = "Schedule entry deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Could not delete schedule entry. It might be in use in the marks register.";
    }
    redirect("exam_schedule.php?session_id={$session_id}&exam_id={$exam_id}&class_id={$class_id}");
}

// Handle form submission for adding/editing a schedule
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['add_schedule']) || isset($_POST['update_schedule']))) {
    $schedule_id = isset($_POST['schedule_id']) ? (int)$_POST['schedule_id'] : 0;
    $subject_id = (int)$_POST['subject_id'];
    $exam_date = trim($_POST['exam_date']);
    $start_time = trim($_POST['start_time']);
    $end_time = trim($_POST['end_time']);
    $full_marks = trim($_POST['full_marks']);
    $pass_marks = trim($_POST['pass_marks']);
    $room_no = trim($_POST['room_no']);

    if (empty($subject_id) || empty($exam_date) || empty($start_time) || empty($end_time) || !is_numeric($full_marks) || !is_numeric($pass_marks)) {
        $errors[] = "All fields are required and marks must be numeric.";
    }

    if (empty($errors)) {
        try {
            if ($schedule_id > 0) { // Update
                $stmt = $db->prepare("UPDATE exam_schedule SET subject_id=?, exam_date=?, start_time=?, end_time=?, room_no=?, full_marks=?, pass_marks=? WHERE id=? AND branch_id=?");
                $stmt->execute([$subject_id, $exam_date, $start_time, $end_time, $room_no, $full_marks, $pass_marks, $schedule_id, $branch_id]);
                $_SESSION['success_message'] = "Exam schedule updated successfully!";
            } else { // Insert
                $stmt = $db->prepare("INSERT INTO exam_schedule (branch_id, session_id, exam_type_id, class_id, subject_id, exam_date, start_time, end_time, room_no, full_marks, pass_marks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$branch_id, $session_id, $exam_id, $class_id, $subject_id, $exam_date, $start_time, $end_time, $room_no, $full_marks, $pass_marks]);
                $_SESSION['success_message'] = "Exam schedule added successfully!";
            }
            redirect("exam_schedule.php?session_id={$session_id}&exam_id={$exam_id}&class_id={$class_id}");
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // 1062 is the MySQL error code for duplicate entry
                $errors[] = "This subject is already scheduled for this session, exam, and class.";
            } else {
                $errors[] = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// Fetch existing schedule for the selected criteria
$schedule = [];
if ($session_id && $exam_id && $class_id) {
    $stmt_schedule = $db->prepare("
        SELECT es.*, s.name as subject_name 
        FROM exam_schedule es
        JOIN subjects s ON es.subject_id = s.id
        WHERE es.session_id = ? AND es.exam_type_id = ? AND es.class_id = ? AND es.branch_id = ?
        ORDER BY es.exam_date, es.start_time
    ");
    $stmt_schedule->execute([$session_id, $exam_id, $class_id, $branch_id]);
    $schedule = $stmt_schedule->fetchAll();
}

require_once '../../header.php';
?>

<?php require_once '../../sidebar_branchadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Exam Schedule</li>
    </ol>

    <?php display_flash_messages(); ?>
    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-search me-1"></i> Select Criteria</div>
        <div class="card-body">
            <form action="" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-3"><label>Session</label><select name="session_id" id="session_id_filter" class="form-select" required><option value="">-- Select Session --</option><?php foreach ($sessions as $session) echo "<option value='{$session['id']}' " . ($session_id == $session['id'] ? 'selected' : '') . ">" . htmlspecialchars($session['name']) . "</option>"; ?></select></div>
                    <div class="col-md-3"><label>Exam</label><select name="exam_id" id="exam_id_filter" class="form-select" required><option value="">-- Select Session First --</option><?php foreach ($exam_types as $exam) echo "<option value='{$exam['id']}' " . ($exam_id == $exam['id'] ? 'selected' : '') . ">" . htmlspecialchars($exam['name']) . "</option>"; ?></select></div>
                    <div class="col-md-3"><label>Class</label><select name="class_id" class="form-select" required><option value="">-- Select Class --</option><?php foreach ($classes as $class) echo "<option value='{$class['id']}' " . ($class_id == $class['id'] ? 'selected' : '') . ">" . htmlspecialchars($class['name']) . "</option>"; ?></select></div>
                    <div class="col-md-3"><button type="submit" class="btn btn-primary w-100">View Schedule</button></div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($session_id && $exam_id && $class_id): ?>
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-plus me-1"></i> Add to Schedule</div>
                <div class="card-body">
                    <form action="exam_schedule.php?session_id=<?php echo $session_id; ?>&exam_id=<?php echo $exam_id; ?>&class_id=<?php echo $class_id; ?>" method="POST">
                        <div class="mb-3"><label>Subject*</label><select name="subject_id" class="form-select" required><option value="">-- Select Subject --</option><?php foreach ($subjects as $subject) echo "<option value='{$subject['id']}'>" . htmlspecialchars($subject['name']) . "</option>"; ?></select></div>
                        <div class="mb-3"><label>Exam Date*</label><input type="date" name="exam_date" class="form-control" required></div>
                        <div class="row"><div class="col-6 mb-3"><label>Start Time*</label><input type="time" name="start_time" class="form-control" required></div><div class="col-6 mb-3"><label>End Time*</label><input type="time" name="end_time" class="form-control" required></div></div>
                        <div class="row"><div class="col-6 mb-3"><label>Full Marks*</label><input type="number" name="full_marks" class="form-control" required></div><div class="col-6 mb-3"><label>Pass Marks*</label><input type="number" name="pass_marks" class="form-control" required></div></div>
                        <div class="mb-3"><label>Room No.</label><input type="text" name="room_no" class="form-control"></div>
                        <button type="submit" name="add_schedule" class="btn btn-primary">Add Schedule</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-calendar-alt me-1"></i> Current Schedule</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead><tr><th>Subject</th><th>Date</th><th>Time</th><th>Marks (Full/Pass)</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($schedule)): ?>
                                    <tr><td colspan="5" class="text-center">No schedule found for this criteria.</td></tr>
                                <?php else: foreach ($schedule as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['subject_name']); ?></td>
                                    <td><?php echo date('d M, Y', strtotime($item['exam_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($item['start_time'])) . ' - ' . date('h:i A', strtotime($item['end_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($item['full_marks']) . ' / ' . htmlspecialchars($item['pass_marks']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-success edit-schedule-btn" data-bs-toggle="modal" data-bs-target="#editScheduleModal" data-schedule='<?php echo json_encode($item, JSON_HEX_QUOT | JSON_HEX_APOS); ?>' title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="exam_schedule.php?action=delete&id=<?php echo $item['id']; ?>&session_id=<?php echo $session_id; ?>&exam_id=<?php echo $exam_id; ?>&class_id=<?php echo $class_id; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');"><i class="fas fa-trash"></i></a>
                                    </td>
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

    <!-- Edit Schedule Modal -->
    <div class="modal fade" id="editScheduleModal" tabindex="-1" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editScheduleModalLabel">Edit Exam Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="exam_schedule.php?session_id=<?php echo $session_id; ?>&exam_id=<?php echo $exam_id; ?>&class_id=<?php echo $class_id; ?>" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="schedule_id" id="edit_schedule_id">
                        <div class="mb-3"><label for="edit_subject_id">Subject*</label><select name="subject_id" id="edit_subject_id" class="form-select" required><?php foreach ($subjects as $subject) echo "<option value='{$subject['id']}'>" . htmlspecialchars($subject['name']) . "</option>"; ?></select></div>
                        <div class="mb-3"><label for="edit_exam_date">Exam Date*</label><input type="date" id="edit_exam_date" name="exam_date" class="form-control" required></div>
                        <div class="row"><div class="col-6 mb-3"><label for="edit_start_time">Start Time*</label><input type="time" id="edit_start_time" name="start_time" class="form-control" required></div><div class="col-6 mb-3"><label for="edit_end_time">End Time*</label><input type="time" id="edit_end_time" name="end_time" class="form-control" required></div></div>
                        <div class="row"><div class="col-6 mb-3"><label for="edit_full_marks">Full Marks*</label><input type="number" id="edit_full_marks" name="full_marks" class="form-control" required></div><div class="col-6 mb-3"><label for="edit_pass_marks">Pass Marks*</label><input type="number" id="edit_pass_marks" name="pass_marks" class="form-control" required></div></div>
                        <div class="mb-3"><label for="edit_room_no">Room No.</label><input type="text" id="edit_room_no" name="room_no" class="form-control"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_schedule" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sessionFilter = document.getElementById('session_id_filter');
    const examFilter = document.getElementById('exam_id_filter');
    const currentExamId = '<?php echo $exam_id; ?>';

    // Populate edit modal
    const editScheduleModal = document.getElementById('editScheduleModal');
    editScheduleModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const schedule = JSON.parse(button.getAttribute('data-schedule'));

        const modal = this;
        modal.querySelector('#edit_schedule_id').value = schedule.id;
        modal.querySelector('#edit_subject_id').value = schedule.subject_id;
        modal.querySelector('#edit_exam_date').value = schedule.exam_date;
        modal.querySelector('#edit_start_time').value = schedule.start_time;
        modal.querySelector('#edit_end_time').value = schedule.end_time;
        modal.querySelector('#edit_full_marks').value = schedule.full_marks;
        modal.querySelector('#edit_pass_marks').value = schedule.pass_marks;
        modal.querySelector('#edit_room_no').value = schedule.room_no;
    });

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

    sessionFilter.addEventListener('change', function() {
        fetchExams(this.value, examFilter, null);
    });

    if (sessionFilter.value) {
        fetchExams(sessionFilter.value, examFilter, currentExamId);
    }
});
</script>
