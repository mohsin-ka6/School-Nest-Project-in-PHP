<?php
$page_title = "Child Exam Results";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$student_id) {
    $_SESSION['error_message'] = "Invalid student ID.";
    redirect('manage_parents.php');
}

// Security check and fetch student info
$stmt_student = $db->prepare("SELECT u.full_name, s.parent_id FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ? AND s.branch_id = ?");
$stmt_student->execute([$student_id, $branch_id]);
$student = $stmt_student->fetch();

if (!$student) {
    $_SESSION['error_message'] = "Student not found in this branch.";
    redirect('manage_parents.php');
}

$page_title = "Results: " . htmlspecialchars($student['full_name']);

// Fetch exam results history (same logic as view_student.php)
$stmt_results = $db->prepare("
    SELECT DISTINCT
        et.id as exam_id,
        et.name as exam_name,
        sess.id as session_id,
        sess.name as session_name
    FROM exam_marks em
    JOIN exam_schedule es ON em.exam_schedule_id = es.id
    JOIN exam_types et ON es.exam_type_id = et.id
    JOIN academic_sessions sess ON em.session_id = sess.id
    WHERE em.student_id = :student_id AND em.branch_id = :branch_id
    ORDER BY sess.start_date DESC, et.name ASC
");
$stmt_results->execute([':student_id' => $student_id, ':branch_id' => $branch_id]);
$exam_results_list = $stmt_results->fetchAll();

// Group results by session for display
$results_by_session = [];
foreach ($exam_results_list as $result) {
    $results_by_session[$result['session_name']][] = $result;
}

require_once '../../header.php';
?>

<?php require_once ROOT_PATH . '/sidebar_branchadmin.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">

    <div class="card">
        <div class="card-header"><i class="fas fa-poll me-1"></i>Exam Results History for <?php echo htmlspecialchars($student['full_name']); ?></div>
        <div class="card-body">
            <?php if (empty($results_by_session)): ?>
                <div class="alert alert-info">No exam results found for this student.</div>
            <?php else: ?>
                <div class="accordion" id="resultsAccordion">
                    <?php $first = true; foreach ($results_by_session as $session_name => $exams): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-<?php echo $exams[0]['session_id']; ?>"><button class="accordion-button <?php echo !$first ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $exams[0]['session_id']; ?>" aria-expanded="<?php echo $first ? 'true' : 'false'; ?>"><?php echo htmlspecialchars($session_name); ?></button></h2>
                        <div id="collapse-<?php echo $exams[0]['session_id']; ?>" class="accordion-collapse collapse <?php echo $first ? 'show' : ''; ?>" data-bs-parent="#resultsAccordion">
                            <div class="accordion-body"><ul class="list-group"><?php foreach ($exams as $exam): ?><li class="list-group-item d-flex justify-content-between align-items-center"><?php echo htmlspecialchars($exam['exam_name']); ?><a href="../exams/print_report_card.php?session_id=<?php echo $exam['session_id']; ?>&exam_id=<?php echo $exam['exam_id']; ?>&student_id=<?php echo $student_id; ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-id-card me-1"></i> View Report Card</a></li><?php endforeach; ?></ul></div>
                        </div>
                    </div>
                    <?php $first = false; endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>
