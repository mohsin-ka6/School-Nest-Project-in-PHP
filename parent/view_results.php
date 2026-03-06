<?php
$page_title = "View Exam Results";
require_once '../config.php';
require_once '../functions.php';

check_role('parent');

$user_id = $_SESSION['user_id'];
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$student_id) {
    $_SESSION['error_message'] = "Invalid child ID.";
    redirect('dashboard.php');
}

// Find the parent ID from the user ID
$stmt_parent = $db->prepare("SELECT id FROM parents WHERE user_id = ?");
$stmt_parent->execute([$user_id]);
$parent_id = $stmt_parent->fetchColumn();

if (!$parent_id) {
    $_SESSION['error_message'] = "Parent profile not found.";
    redirect('dashboard.php');
}

// Fetch student details to verify ownership and get class/branch info
$stmt_student = $db->prepare("SELECT u.full_name, s.branch_id FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = :student_id AND s.parent_id = :parent_id");
$stmt_student->execute([':student_id' => $student_id, ':parent_id' => $parent_id]);
$student = $stmt_student->fetch();

if (!$student) {
    $_SESSION['error_message'] = "You do not have permission to view this profile.";
    redirect('my_children.php');
}

// Fetch exam results history for this student
$stmt_exams = $db->prepare("
    SELECT DISTINCT
        et.id as exam_id,
        et.name as exam_name,
        sess.id as session_id,
        sess.name as session_name
    FROM exam_marks em
    JOIN exam_schedule es ON em.exam_schedule_id = es.id
    JOIN exam_types et ON es.exam_type_id = et.id
    JOIN academic_sessions sess ON em.session_id = sess.id
    WHERE em.student_id = :student_id AND em.branch_id = :branch_id AND et.publish_date IS NOT NULL AND et.publish_date <= CURDATE()
    ORDER BY sess.start_date DESC, et.name ASC
");
$stmt_exams->execute([':student_id' => $student_id, ':branch_id' => $student['branch_id']]);
$exams = $stmt_exams->fetchAll();

// Group results by session for display
$results_by_session = [];
foreach ($exams as $exam) {
    $results_by_session[$exam['session_name']][] = $exam;
}

require_once '../header.php';
?>

<?php require_once 'sidebar_parent.php'; ?>
<?php require_once '../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Results for <?php echo htmlspecialchars($student['full_name']); ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="my_children.php">My Children</a></li>
        <li class="breadcrumb-item"><a href="view_child_profile.php?id=<?php echo $student_id; ?>">Child Profile</a></li>
        <li class="breadcrumb-item active">Select Exam</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-list-alt me-1"></i> Available Exam Reports</div>
        <div class="card-body">
            <?php if (empty($results_by_session)): ?>
                <div class="alert alert-info">No exam reports are available for this student yet.</div>
            <?php else: ?>
                <div class="accordion" id="resultsAccordion">
                    <?php $first = true; foreach ($results_by_session as $session_name => $session_exams): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-<?php echo $session_exams[0]['session_id']; ?>"><button class="accordion-button <?php echo !$first ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $session_exams[0]['session_id']; ?>" aria-expanded="<?php echo $first ? 'true' : 'false'; ?>"><?php echo htmlspecialchars($session_name); ?></button></h2>
                        <div id="collapse-<?php echo $session_exams[0]['session_id']; ?>" class="accordion-collapse collapse <?php echo $first ? 'show' : ''; ?>" data-bs-parent="#resultsAccordion">
                            <div class="accordion-body">
                                <div class="list-group">
                                    <?php foreach ($session_exams as $exam): ?>
                                        <a href="view_report_card.php?student_id=<?php echo $student_id; ?>&exam_id=<?php echo $exam['exam_id']; ?>&session_id=<?php echo $exam['session_id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"><?php echo htmlspecialchars($exam['exam_name']); ?><i class="fas fa-chevron-right"></i></a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php $first = false; endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>



