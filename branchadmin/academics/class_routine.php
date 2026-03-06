<?php
$page_title = "Class Routine";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$errors = [];

// Fetch classes for dropdown
$stmt_classes = $db->prepare("SELECT id, name FROM classes WHERE branch_id = ? ORDER BY numeric_name ASC, name ASC");
$stmt_classes->execute([$branch_id]);
$classes = $stmt_classes->fetchAll();

$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;

// Handle Add/Edit/Delete actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_routine'])) {
    $day = $_POST['day_of_week'];
    $subject_id = (int)$_POST['subject_id'];
    $teacher_id = (int)$_POST['teacher_id'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $room_no = trim($_POST['room_no']);
    $routine_id = (int)$_POST['routine_id'];

    if (empty($day) || empty($subject_id) || empty($teacher_id) || empty($start_time) || empty($end_time)) {
        $errors[] = "All fields except Room No. are required.";
    } elseif (strtotime($end_time) <= strtotime($start_time)) {
        $errors[] = "End time must be after start time.";
    }

    if (empty($errors)) {
        try {
            if ($routine_id) {
                // Update existing entry
                $stmt = $db->prepare("UPDATE class_routine SET subject_id=?, teacher_id=?, day_of_week=?, start_time=?, end_time=?, room_no=? WHERE id=? AND branch_id=?");
                $stmt->execute([$subject_id, $teacher_id, $day, $start_time, $end_time, $room_no, $routine_id, $branch_id]);
                $_SESSION['success_message'] = "Routine entry updated successfully!";
            } else {
                // Add new entry
                $stmt = $db->prepare("INSERT INTO class_routine (branch_id, class_id, section_id, subject_id, teacher_id, day_of_week, start_time, end_time, room_no) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$branch_id, $class_id, $section_id, $subject_id, $teacher_id, $day, $start_time, $end_time, $room_no]);
                $_SESSION['success_message'] = "Routine entry added successfully!";
            }
            redirect("class_routine.php?class_id={$class_id}&section_id={$section_id}");
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $routine_id_to_delete = (int)$_GET['id'];
    if ($routine_id_to_delete > 0) {
        try {
            $stmt = $db->prepare("DELETE FROM class_routine WHERE id = ? AND branch_id = ?");
            $stmt->execute([$routine_id_to_delete, $branch_id]);
            $_SESSION['success_message'] = "Routine entry deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database Error: Could not delete entry.";
        }
        redirect("class_routine.php?class_id={$class_id}&section_id={$section_id}");
    }
}

$routine = [];
if ($class_id && $section_id) {
    $stmt = $db->prepare("
        SELECT r.*, sub.name as subject_name, t.full_name as teacher_name
        FROM class_routine r
        JOIN subjects sub ON r.subject_id = sub.id
        JOIN users t ON r.teacher_id = t.id
        WHERE r.class_id = ? AND r.section_id = ? AND r.branch_id = ?
        ORDER BY r.start_time
    ");
    $stmt->execute([$class_id, $section_id, $branch_id]);
    $results = $stmt->fetchAll();
    // Organize routine by day
    foreach ($results as $row) {
        $routine[$row['day_of_week']][] = $row;
    }
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

// Fetch subjects and teachers for the modal form, but only if a class is selected
$subjects = [];
$teachers = [];
if ($class_id) {
    // Fetch subjects assigned to this class
    $stmt_subjects = $db->prepare("SELECT s.id, s.name FROM subjects s JOIN class_subjects cs ON s.id = cs.subject_id WHERE cs.class_id = ? ORDER BY s.name");
    $stmt_subjects->execute([$class_id]);
    $subjects = $stmt_subjects->fetchAll();

    // Fetch all teachers in the branch
    $stmt_teachers = $db->prepare("SELECT id, full_name FROM users WHERE branch_id = ? AND role = 'teacher' ORDER BY full_name");
    $stmt_teachers->execute([$branch_id]);
    $teachers = $stmt_teachers->fetchAll();
}

require_once '../../header.php';
?>

<?php require_once ROOT_PATH . '/sidebar_branchadmin.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">

    <?php if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $_SESSION['success_message'] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['success_message']); } ?>
    <?php if (isset($_SESSION['error_message'])) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . $_SESSION['error_message'] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['error_message']); } ?>
    <?php if (!empty($errors)) { echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; } ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-search me-1"></i> Select Class to View Routine</div>
        <div class="card-body">
            <form action="" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-5">
                        <label>Class</label>
                        <select name="class_id" id="class_id" class="form-select" required>
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $class) echo "<option value='{$class['id']}' " . ($class_id == $class['id'] ? 'selected' : '') . ">" . htmlspecialchars($class['name']) . "</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label>Section</label>
                        <select name="section_id" id="section_id" class="form-select" required>
                            <option value="">-- Select Class First --</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">View Routine</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($class_id && $section_id): ?>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-calendar-alt me-1"></i> Weekly Timetable</span>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addEntryModal"><i class="fas fa-plus me-1"></i> Add Routine Entry</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-center">
                    <thead>
                        <tr class="bg-light">
                            <th>Day</th>
                            <th>Schedule</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($days as $day): ?>
                        <tr>
                            <td class="fw-bold" style="width: 120px;"><?php echo $day; ?></td>
                            <td>
                                <?php if (isset($routine[$day])): ?>
                                    <div class="d-flex flex-wrap">
                                    <?php foreach ($routine[$day] as $entry): ?>
                                        <div class="alert alert-info p-2 m-1 flex-grow-1 position-relative routine-entry">
                                            <div style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#addEntryModal" data-routine='<?php echo json_encode($entry); ?>'>
                                                <strong><?php echo htmlspecialchars($entry['subject_name']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($entry['teacher_name']); ?></small><br>
                                                <small class="text-muted"><?php echo date('h:i A', strtotime($entry['start_time'])) . ' - ' . date('h:i A', strtotime($entry['end_time'])); ?></small>
                                            </div>
                                            <a href="?class_id=<?php echo $class_id; ?>&section_id=<?php echo $section_id; ?>&action=delete&id=<?php echo $entry['id']; ?>" class="position-absolute top-0 end-0 p-1" onclick="return confirm('Are you sure you want to delete this entry?');">
                                                <i class="fas fa-trash-alt text-danger small"></i>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">No classes scheduled</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="addEntryModal" tabindex="-1" aria-labelledby="entryModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="class_routine.php?class_id=<?php echo $class_id; ?>&section_id=<?php echo $section_id; ?>" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="entryModalLabel">Add Routine Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="routine_id" id="routine_id" value="0">
                    <div class="mb-3">
                        <label for="day_of_week" class="form-label">Day*</label>
                        <select name="day_of_week" id="day_of_week" class="form-select" required>
                            <?php foreach ($days as $day) echo "<option value='{$day}'>{$day}</option>"; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="subject_id_modal" class="form-label">Subject*</label>
                        <select name="subject_id" id="subject_id_modal" class="form-select" required>
                            <option value="">-- Select Subject --</option>
                            <?php foreach ($subjects as $subject) echo "<option value='{$subject['id']}'>" . htmlspecialchars($subject['name']) . "</option>"; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="teacher_id_modal" class="form-label">Teacher*</label>
                        <select name="teacher_id" id="teacher_id_modal" class="form-select" required>
                            <option value="">-- Select Teacher --</option>
                            <?php foreach ($teachers as $teacher) echo "<option value='{$teacher['id']}'>" . htmlspecialchars($teacher['full_name']) . "</option>"; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3"><label for="start_time" class="form-label">Start Time*</label><input type="time" name="start_time" id="start_time" class="form-control" required></div>
                        <div class="col-6 mb-3"><label for="end_time" class="form-label">End Time*</label><input type="time" name="end_time" id="end_time" class="form-control" required></div>
                    </div>
                    <div class="mb-3"><label for="room_no" class="form-label">Room No.</label><input type="text" name="room_no" id="room_no" class="form-control"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="save_routine" class="btn btn-primary">Save Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('class_id');
    const sectionSelect = document.getElementById('section_id');
    const currentSectionId = '<?php echo $section_id; ?>';

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

    // On page load, if a class is already selected (e.g., from GET params), fetch its sections
    if (classSelect.value) {
        fetchSections(classSelect.value, sectionSelect, currentSectionId);
    }

    // Handle modal for adding/editing
    const entryModal = document.getElementById('addEntryModal');
    entryModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const modalTitle = entryModal.querySelector('.modal-title');
        const form = entryModal.querySelector('form');
        
        // Reset form for adding new
        document.getElementById('routine_id').value = '0';
        modalTitle.textContent = 'Add Routine Entry';
        form.reset();

        // If editing, populate form
        const routineData = button.dataset.routine;
        if (routineData) {
            // This is an edit operation
            modalTitle.textContent = 'Edit Routine Entry';
            const data = JSON.parse(routineData);
            document.getElementById('routine_id').value = data.id;
            document.getElementById('day_of_week').value = data.day_of_week;
            document.getElementById('subject_id_modal').value = data.subject_id;
            document.getElementById('teacher_id_modal').value = data.teacher_id;
            document.getElementById('start_time').value = data.start_time;
            document.getElementById('end_time').value = data.end_time;
            document.getElementById('room_no').value = data.room_no;
        }
    });

});
</script>

<?php require_once '../../footer.php'; ?>