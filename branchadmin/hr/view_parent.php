<?php
$page_title = "Parent Profile";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$parent_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$parent_id) {
    $_SESSION['error_message'] = "Invalid parent ID.";
    redirect('manage_parents.php');
}

// Fetch parent details
$stmt_parent = $db->prepare("SELECT * FROM parents WHERE id = ? AND branch_id = ?");
$stmt_parent->execute([$parent_id, $branch_id]);
$parent = $stmt_parent->fetch();

if (!$parent) {
    $_SESSION['error_message'] = "Parent not found.";
    redirect('manage_parents.php');
}

// Fetch linked children
$stmt_children = $db->prepare("
    SELECT s.id, u.full_name, c.name as class_name, sec.name as section_name, se.class_id, se.section_id, se.session_id
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN student_enrollments se ON s.id = se.student_id
    LEFT JOIN academic_sessions sess ON se.session_id = sess.id AND sess.is_current = 1
    LEFT JOIN classes c ON se.class_id = c.id
    LEFT JOIN sections sec ON se.section_id = sec.id
    WHERE s.parent_id = ? AND s.branch_id = ?
    GROUP BY s.id
    ORDER BY u.full_name
");
$stmt_children->execute([$parent_id, $branch_id]);
$children = $stmt_children->fetchAll();

require_once '../../header.php';
?>

<?php require_once ROOT_PATH . '/sidebar_branchadmin.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-user-friends me-1"></i> Parent Details</span>
            <div>
                <a href="edit_parent.php?id=<?php echo $parent_id; ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit me-1"></i> Edit</a>
                <a href="print_parent.php?id=<?php echo $parent_id; ?>" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-print me-1"></i> Print Login Details</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Father's Information</h5>
                    <table class="table table-bordered">
                        <tr><th width="30%">Name</th><td><?php echo htmlspecialchars($parent['father_name']); ?></td></tr>
                        <tr><th>Phone</th><td><?php echo htmlspecialchars($parent['father_phone']); ?></td></tr>
                        <tr><th>CNIC</th><td><?php echo htmlspecialchars($parent['father_cnic']); ?></td></tr>
                        <tr><th>Email</th><td><?php echo htmlspecialchars($parent['father_email'] ?? 'N/A'); ?></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5>Mother's Information</h5>
                    <table class="table table-bordered">
                        <tr><th width="30%">Name</th><td><?php echo htmlspecialchars($parent['mother_name'] ?? 'N/A'); ?></td></tr>
                        <tr><th>Phone</th><td><?php echo htmlspecialchars($parent['mother_phone'] ?? 'N/A'); ?></td></tr>
                        <tr><th>CNIC</th><td><?php echo htmlspecialchars($parent['mother_cnic'] ?? 'N/A'); ?></td></tr>
                        <tr><th>Email</th><td><?php echo htmlspecialchars($parent['mother_email'] ?? 'N/A'); ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-child me-1"></i> Children in this School</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>Student Name</th><th>Class</th><th>Section</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($children)): ?>
                            <tr><td colspan="4" class="text-center">No children linked to this parent found in this branch.</td></tr>
                        <?php else: ?>
                            <?php foreach ($children as $child): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($child['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($child['class_name']); ?></td>
                                <td><?php echo htmlspecialchars($child['section_name']); ?></td>
                                <td>
                                    <a href="../students/view_student.php?id=<?php echo $child['id']; ?>" class="btn btn-sm btn-info" title="View Student Profile"><i class="fas fa-eye"></i></a>
                                    <a href="view_child_results.php?id=<?php echo $child['id']; ?>" class="btn btn-sm btn-warning" title="Check Results"><i class="fas fa-poll"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>