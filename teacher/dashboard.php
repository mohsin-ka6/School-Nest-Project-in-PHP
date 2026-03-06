<?php
$page_title = "Teacher Dashboard";
require_once '../config.php';
require_once '../functions.php';

// Security check: Ensure user is logged in and is a teacher.
// We do this manually here to avoid the redirect loop from check_role().
if (!is_logged_in() || $_SESSION['role'] !== 'teacher') redirect(BASE_URL . '/auth/login.php');

$teacher_id = $_SESSION['user_id'];

// Fetch teacher's assignments
$stmt = $db->prepare("
    SELECT 
        c.name as class_name,
        sec.name as section_name,
        sub.name as subject_name
    FROM teacher_assignments ta
    JOIN sections sec ON ta.section_id = sec.id
    JOIN classes c ON sec.class_id = c.id
    JOIN subjects sub ON ta.subject_id = sub.id
    WHERE ta.teacher_id = ?
    ORDER BY c.numeric_name, sec.name, sub.name
");
$stmt->execute([$teacher_id]);
$assignments = $stmt->fetchAll();

// Group assignments by class and section for a cleaner display
$grouped_assignments = [];
foreach ($assignments as $assignment) {
    $key = $assignment['class_name'] . ' - ' . $assignment['section_name'];
    if (!isset($grouped_assignments[$key])) {
        $grouped_assignments[$key] = [];
    }
    $grouped_assignments[$key][] = $assignment['subject_name'];
}

require_once '../header.php';
?>

<?php require_once 'sidebar_teacher.php'; ?>

<?php require_once '../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Your Assignments</li>
    </ol>

    <div class="row">
        <?php if (empty($grouped_assignments)): ?>
            <div class="col-12">
                <div class="alert alert-info">You have not been assigned to any classes yet.</div>
            </div>
        <?php else: ?>
            <?php foreach ($grouped_assignments as $class_section => $subjects): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-chalkboard me-1"></i>
                            <?php echo htmlspecialchars($class_section); ?>
                        </div>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($subjects as $subject): ?>
                                <li class="list-group-item"><?php echo htmlspecialchars($subject); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../footer.php'; ?>