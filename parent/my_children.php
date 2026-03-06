<?php
$page_title = "My Children";
require_once '../config.php';
require_once '../functions.php';

check_role('parent');

// Fetch parent's ID from the logged-in user
$stmt_parent = $db->prepare("SELECT id FROM parents WHERE user_id = ?");
$stmt_parent->execute([$_SESSION['user_id']]);
$parent_id = $stmt_parent->fetchColumn();

if (!$parent_id) {
    die("Could not identify parent record for this user.");
}

// Fetch all children of this parent with their current enrollment details
$stmt_children = $db->prepare("
    SELECT 
        s.id as student_id, 
        u.full_name, 
        s.photo,
        c.name as class_name, 
        sec.name as section_name,
        b.name as branch_name
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN branches b ON s.branch_id = b.id
    LEFT JOIN student_enrollments se ON s.id = se.student_id
    LEFT JOIN academic_sessions acs ON se.session_id = acs.id AND acs.is_current = 1
    LEFT JOIN classes c ON se.class_id = c.id
    LEFT JOIN sections sec ON se.section_id = sec.id
    WHERE s.parent_id = ?
    GROUP BY s.id
    ORDER BY u.full_name ASC
");
$stmt_children->execute([$parent_id]);
$children = $stmt_children->fetchAll();

require_once '../header.php';
?>

<?php require_once '../sidebar_parent.php'; ?>
<?php require_once '../navbar.php'; ?>

<div class="container-fluid px-4">

    <?php if (empty($children)): ?>
        <div class="alert alert-info">No children are currently associated with your profile.</div>
    <?php else: ?>
    <div class="row">
        <?php foreach ($children as $child): ?>
        <div class="col-xl-4 col-lg-6">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <img src="<?php echo $child['photo'] ? BASE_URL . '/' . htmlspecialchars($child['photo']) : BASE_URL . '/assets/images/default_avatar.png'; ?>" class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($child['full_name']); ?></h5>
                    <p class="text-muted mb-2">
                        <?php if ($child['class_name']): ?>
                            Class: <?php echo htmlspecialchars($child['class_name'] . ' - ' . $child['section_name']); ?>
                        <?php else: ?>
                            <span class="text-danger">Not Enrolled in Current Session</span>
                        <?php endif; ?>
                        <br>
                        <small><?php echo htmlspecialchars($child['branch_name']); ?></small>
                    </p>
                    <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                        <a href="view_child_profile.php?id=<?php echo $child['student_id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-user me-1"></i> Profile</a>
                        <a href="fees.php?student_id=<?php echo $child['student_id']; ?>" class="btn btn-success btn-sm"><i class="fas fa-money-check-alt me-1"></i> Fees</a>
                        <a href="view_results.php?id=<?php echo $child['student_id']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-poll me-1"></i> Results</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../footer.php'; ?>
