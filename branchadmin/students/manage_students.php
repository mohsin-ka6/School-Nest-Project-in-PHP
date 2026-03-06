<?php
$page_title = "Manage Students";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$students = [];

// --- FILTERS ---
$filter_class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// --- DATA FETCHING for FILTERS ---
// Fetch classes for the current branch
$stmt_classes = $db->prepare("SELECT id, name FROM classes WHERE branch_id = ? ORDER BY numeric_name ASC, name ASC");
$stmt_classes->execute([$branch_id]);
$classes = $stmt_classes->fetchAll();

// --- MAIN DATA QUERY ---
// Base query to fetch all students in the branch.
// Using LEFT JOIN on enrollments ensures that students who have been transferred
// but not yet enrolled in a new class will still appear in the list.
$sql = "
    SELECT 
        s.id, s.admission_no, s.photo, 
        u.full_name, 
        c.name as class_name, 
        sec.name as section_name
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN student_enrollments se ON s.id = se.student_id
    LEFT JOIN academic_sessions sess ON se.session_id = sess.id AND sess.is_current = 1
    LEFT JOIN classes c ON se.class_id = c.id
    LEFT JOIN sections sec ON se.section_id = sec.id
    WHERE s.branch_id = :branch_id
";

$params = [':branch_id' => $branch_id];

if ($filter_class_id) {
    $sql .= " AND se.class_id = :class_id";
    $params[':class_id'] = $filter_class_id;
}

$sql .= " GROUP BY s.id ORDER BY u.full_name ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

require_once '../../header.php';
?>

<?php require_once ROOT_PATH . '/sidebar_branchadmin.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">

    <?php display_flash_messages(); ?>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-users me-1"></i> Students in <?php echo htmlspecialchars($_SESSION['branch_name'] ?? 'Your Branch'); ?></span>
            <a href="add_student.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Add New Student</a>
        </div>
        <div class="card-body">
            <form action="" method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-4">
                        <label for="class_id" class="form-label">Filter by Class</label>
                        <select name="class_id" id="class_id" class="form-select" onchange="this.form.submit()">
                            <option value="">-- All Classes --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo ($filter_class_id == $class['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($class['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Student Name</th>
                            <th>Admission No</th>
                            <th>Class (Current Session)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr><td colspan="5" class="text-center">No students found in this branch.</td></tr>
                        <?php else: foreach ($students as $student): ?>
                            <tr>
                                <td><img src="<?php echo $student['photo'] ? BASE_URL . '/' . htmlspecialchars($student['photo']) : BASE_URL . '/assets/images/default_avatar.png'; ?>" alt="Photo" class="img-thumbnail" style="width: 50px; height: 50px;"></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                                <td><?php echo $student['class_name'] ? htmlspecialchars($student['class_name'] . ' - ' . $student['section_name']) : '<span class="text-muted">Not Enrolled</span>'; ?></td>
                                <td>
                                    <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-info" title="View Profile"><i class="fas fa-eye"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>