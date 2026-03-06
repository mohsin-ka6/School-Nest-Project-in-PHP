<?php
$page_title = "Parent Dashboard";
require_once '../config.php';
require_once '../functions.php';

// Security check: Ensure user is logged in and is a parent.
// We do this manually here to avoid the redirect loop from check_role().
if (!is_logged_in() || $_SESSION['role'] !== 'parent') redirect(BASE_URL . '/auth/login.php');

// Fetch parent's children for the dashboard
$stmt_parent = $db->prepare("SELECT id FROM parents WHERE user_id = ?");
$stmt_parent->execute([$_SESSION['user_id']]);
$parent_id = $stmt_parent->fetchColumn();

$children = [];
if ($parent_id) {
    $stmt_children = $db->prepare("
        SELECT s.id as student_id, u.full_name, c.name as class_name, sec.name as section_name 
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN student_enrollments se ON s.id = se.student_id
        LEFT JOIN academic_sessions acs ON se.session_id = acs.id AND acs.is_current = 1
        LEFT JOIN classes c ON se.class_id = c.id
        LEFT JOIN sections sec ON se.section_id = sec.id
        WHERE s.parent_id = ?
        GROUP BY s.id
    ");
    $stmt_children->execute([$parent_id]);
    $children = $stmt_children->fetchAll();
}

require_once '../header.php';
?>

<?php require_once '../sidebar_parent.php'; ?>
<?php require_once '../navbar.php'; ?>

<div class="container-fluid px-4">

    <div class="row">
        <?php if (empty($children)): ?>
            <div class="col-12"><div class="alert alert-info">No children are currently associated with your profile. Please visit the school office to link your children.</div></div>
        <?php else: foreach ($children as $child): ?>
            <div class="col-xl-4 col-md-6">
                <div class="card bg-primary text-white mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($child['full_name']); ?></h5>
                        <p class="card-text">
                            Class: <?php echo $child['class_name'] ? htmlspecialchars($child['class_name'] . ' - ' . $child['section_name']) : '<span class="badge bg-warning text-dark">Not Enrolled</span>'; ?>
                        </p>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <a class="small text-white stretched-link" href="view_child_profile.php?id=<?php echo $child['student_id']; ?>">View Details</a>
                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<?php require_once '../footer.php'; ?>