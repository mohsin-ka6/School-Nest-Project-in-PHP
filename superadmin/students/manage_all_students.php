<?php
$page_title = "Manage All Students";
require_once '../../config.php';
require_once '../../functions.php';

check_role('superadmin');

$students = [];

// --- FILTERS ---
$filter_branch_id = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;
$filter_class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// --- DATA FETCHING for FILTERS ---
// Fetch all branches
$stmt_branches = $db->query("SELECT id, name FROM branches ORDER BY name ASC");
$branches = $stmt_branches->fetchAll();

// Fetch classes, but only if a branch is selected
$classes = [];
if ($filter_branch_id) {
    $stmt_classes = $db->prepare("SELECT id, name FROM classes WHERE branch_id = ? ORDER BY numeric_name ASC, name ASC");
    $stmt_classes->execute([$filter_branch_id]);
    $classes = $stmt_classes->fetchAll();
}

// --- MAIN DATA QUERY ---
// Base query to fetch enrolled students
$sql = "
    SELECT 
        s.id, s.admission_no, s.photo, 
        u.full_name, 
        b.name as branch_name,
        c.name as class_name, 
        sec.name as section_name,
        sess.id as session_id
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN branches b ON s.branch_id = b.id
    LEFT JOIN student_enrollments se ON s.id = se.student_id
    LEFT JOIN academic_sessions sess ON se.session_id = sess.id AND sess.is_current = 1
    LEFT JOIN classes c ON se.class_id = c.id
    LEFT JOIN sections sec ON se.section_id = sec.id
";

$params = [];
$where_clauses = [];

if ($filter_branch_id) {
    $where_clauses[] = "s.branch_id = :branch_id";
    $params[':branch_id'] = $filter_branch_id;

    if ($filter_class_id) {
        $where_clauses[] = "se.class_id = :class_id";
        $params[':class_id'] = $filter_class_id;
    }
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " GROUP BY s.id ORDER BY b.name, c.numeric_name, sec.name, u.full_name ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

require_once '../../header.php';
?>

<?php require_once '../sidebar_superadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">All Students</li>
    </ol>

    <?php display_flash_messages(); ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-users me-1"></i> All Students List</div>
        <div class="card-body">
            <form action="" method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-4">
                        <label for="branch_id" class="form-label">Filter by Branch</label>
                        <select name="branch_id" id="branch_id" class="form-select" onchange="this.form.submit()">
                            <option value="">-- All Branches --</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>" <?php echo ($filter_branch_id == $branch['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($branch['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($filter_branch_id): ?>
                    <div class="col-md-4">
                        <label for="class_id" class="form-label">Filter by Class</label>
                        <select name="class_id" id="class_id" class="form-select" onchange="this.form.submit()">
                            <option value="">-- All Classes --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo ($filter_class_id == $class['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($class['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Student Name</th>
                            <th>Admission No</th>
                            <th>Branch</th>
                            <th>Class (Current Session)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr><td colspan="6" class="text-center">No students found matching the criteria.</td></tr>
                        <?php else: foreach ($students as $student): ?>
                            <tr>
                                <td><img src="<?php echo $student['photo'] ? BASE_URL . '/' . htmlspecialchars($student['photo']) : BASE_URL . '/assets/images/default_avatar.png'; ?>" alt="Photo" class="img-thumbnail" style="width: 50px; height: 50px;"></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                                <td><?php echo htmlspecialchars($student['branch_name']); ?></td>
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
