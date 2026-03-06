<?php
$page_title = "Manage Attendance";
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

$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
$attendance_date = isset($_GET['attendance_date']) ? $_GET['attendance_date'] : date('Y-m-d');

$students = [];
if ($section_id && $attendance_date) {
    // Get class_id from section_id
    $stmt_class = $db->prepare("SELECT class_id FROM sections WHERE id = ?");
    $stmt_class->execute([$section_id]);
    $class_id = $stmt_class->fetchColumn();

    // Fetch students and their attendance status for the selected date
    $stmt_students = $db->prepare("
        SELECT s.id as student_id, u.full_name, s.roll_no, sa.status, sa.remark
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN student_attendance sa ON s.id = sa.student_id AND sa.attendance_date = ?
        WHERE s.section_id = ?
        ORDER BY s.roll_no, u.full_name
    ");
    $stmt_students->execute([$attendance_date, $section_id]);
    $students = $stmt_students->fetchAll();
}

// Handle form submission for saving attendance
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_attendance'])) {
    $status_data = $_POST['status'];
    $remark_data = $_POST['remark'];
    $post_section_id = (int)$_POST['section_id'];
    $post_class_id = (int)$_POST['class_id'];
    $post_date = $_POST['attendance_date'];

    try {
        $db->beginTransaction();
        $stmt = $db->prepare("
            INSERT INTO student_attendance (branch_id, student_id, class_id, section_id, teacher_id, status, attendance_date, remark)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status), remark = VALUES(remark), teacher_id = VALUES(teacher_id)
        ");

        foreach ($status_data as $student_id => $status) {
            $remark = $remark_data[$student_id] ?? '';
            $stmt->execute([$branch_id, $student_id, $post_class_id, $post_section_id, $teacher_id, $status, $post_date, $remark]);
        }

        $db->commit();
        $_SESSION['success_message'] = "Attendance saved successfully for " . date('d M, Y', strtotime($post_date));
        redirect("manage_attendance.php?section_id={$post_section_id}&attendance_date={$post_date}");
    } catch (PDOException $e) {
        $db->rollBack();
        $errors[] = "Database Error: " . $e->getMessage();
    }
}

require_once '../header.php';
?>

<?php require_once 'sidebar_teacher.php'; ?>

<div id="page-content-wrapper">
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
        <div class="container-fluid"><button class="btn btn-success" id="menu-toggle"><i class="fas fa-bars"></i></button></div>
    </nav>

    <div class="container-fluid px-4">
        <h1 class="mt-4"><?php echo $page_title; ?></h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Manage Attendance</li>
        </ol>

        <?php if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $_SESSION['success_message'] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['success_message']); } ?>
        <?php if (!empty($errors)) { echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; } ?>

        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-search me-1"></i> Select Criteria</div>
            <div class="card-body">
                <form action="" method="GET">
                    <div class="row align-items-end">
                        <div class="col-md-5"><label>Class - Section</label>
                            <select name="section_id" class="form-select" required>
                                <option value="">-- Select Section --</option>
                                <?php foreach ($assigned_sections as $sec) echo "<option value='{$sec['section_id']}' " . ($section_id == $sec['section_id'] ? 'selected' : '') . ">" . htmlspecialchars($sec['class_name'] . ' - ' . $sec['section_name']) . "</option>"; ?>
                            </select>
                        </div>
                        <div class="col-md-5"><label>Attendance Date</label><input type="date" name="attendance_date" class="form-control" value="<?php echo $attendance_date; ?>" required></div>
                        <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Manage</button></div>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($students)): ?>
        <form action="" method="POST">
            <input type="hidden" name="section_id" value="<?php echo $section_id; ?>">
            <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
            <input type="hidden" name="attendance_date" value="<?php echo $attendance_date; ?>">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-list me-1"></i> Student List</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead><tr><th>Roll No</th><th>Student Name</th><th>Status</th><th>Remark</th></tr></thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['roll_no']); ?></td>
                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                    <td>
                                        <select name="status[<?php echo $student['student_id']; ?>]" class="form-select form-select-sm">
                                            <option value="present" <?php echo ($student['status'] ?? 'present') == 'present' ? 'selected' : ''; ?>>Present</option>
                                            <option value="late" <?php echo ($student['status'] ?? '') == 'late' ? 'selected' : ''; ?>>Late</option>
                                            <option value="absent" <?php echo ($student['status'] ?? '') == 'absent' ? 'selected' : ''; ?>>Absent</option>
                                            <option value="half_day" <?php echo ($student['status'] ?? '') == 'half_day' ? 'selected' : ''; ?>>Half Day</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="remark[<?php echo $student['student_id']; ?>]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($student['remark'] ?? ''); ?>"></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" name="save_attendance" class="btn btn-primary">Save Attendance</button>
                </div>
            </div>
        </form>
        <?php elseif($section_id && $attendance_date): ?>
            <div class="alert alert-warning">No students found for the selected section.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../footer.php'; ?>
