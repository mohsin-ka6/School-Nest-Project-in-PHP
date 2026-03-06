<?php
require_once '../../config.php';
require_once '../../functions.php';

check_role('superadmin');

$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$student_id) {
    $_SESSION['error_message'] = "Invalid student ID.";
    redirect('manage_all_students.php');
}

// Fetch student data, including their enrollment in the CURRENT session
$sql = "
    SELECT 
        s.*, 
        u.full_name, u.email, u.username, u.status,
        p.father_name, p.father_phone, p.father_cnic,
        se.roll_no,
        c.name as class_name,
        sec.name as section_name,
        sess.name as session_name,
        b.name as branch_name
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN branches b ON s.branch_id = b.id
    LEFT JOIN student_enrollments se ON s.id = se.student_id
    LEFT JOIN academic_sessions sess ON se.session_id = sess.id AND sess.is_current = 1
    LEFT JOIN classes c ON se.class_id = c.id
    LEFT JOIN sections sec ON se.section_id = sec.id
    LEFT JOIN parents p ON s.parent_id = p.id
    WHERE s.id = :student_id
";

$stmt = $db->prepare($sql);
$stmt->execute([':student_id' => $student_id]);
$student = $stmt->fetch();

if (!$student) {
    $_SESSION['error_message'] = "Student not found.";
    redirect('manage_all_students.php');
}

// Fetch enrollment history
$stmt_history = $db->prepare("
    SELECT 
        se.roll_no,
        sess.name as session_name,
        sess.is_current,
        c.name as class_name,
        sec.name as section_name
    FROM student_enrollments se
    JOIN academic_sessions sess ON se.session_id = sess.id
    JOIN classes c ON se.class_id = c.id
    JOIN sections sec ON se.section_id = sec.id
    WHERE se.student_id = :student_id
    ORDER BY sess.start_date DESC
");
$stmt_history->execute([':student_id' => $student_id]);
$enrollment_history = $stmt_history->fetchAll();

// Fetch exam results history
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
    WHERE em.student_id = :student_id
    ORDER BY sess.start_date DESC, et.name ASC
");
$stmt_results->execute([':student_id' => $student_id]);
$exam_results_list = $stmt_results->fetchAll();

// Group results by session for display
$results_by_session = [];
foreach ($exam_results_list as $result) {
    $results_by_session[$result['session_name']][] = $result;
}

$page_title = "Profile: " . htmlspecialchars($student['full_name']);

require_once '../../header.php';
?>

<?php require_once '../sidebar_superadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="manage_all_students.php">All Students</a></li>
        <li class="breadcrumb-item active">Student Profile</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-user-graduate me-1"></i> Student Details</span>
            <a href="transfer_student.php?id=<?php echo $student_id; ?>" class="btn btn-sm btn-warning">
                <i class="fas fa-exchange-alt me-1"></i> Transfer Student
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Left Column for Photo -->
                <div class="col-md-3 text-center">
                    <img src="<?php echo $student['photo'] ? BASE_URL . '/' . htmlspecialchars($student['photo']) : BASE_URL . '/assets/images/default_avatar.png'; ?>" 
                         class="img-thumbnail rounded-circle mb-3" 
                         alt="Student Photo" 
                         style="width: 150px; height: 150px; object-fit: cover;">
                    <h4><?php echo htmlspecialchars($student['full_name']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($student['branch_name']); ?></p>
                    <?php if ($student['class_name']): ?>
                        <p class="text-muted"><?php echo htmlspecialchars($student['class_name'] . ' - ' . $student['section_name']); ?></p>
                        <span class="badge bg-success">Enrolled in Current Session</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">Not Enrolled in Current Session</span>
                    <?php endif; ?>
                </div>

                <!-- Right Column for Details -->
                <div class="col-md-9">
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">Profile</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">Enrollment History</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="results-tab" data-bs-toggle="tab" data-bs-target="#results" type="button" role="tab">Results</button>
                        </li>
                    </ul>
                    <div class="tab-content py-3 px-2" id="myTabContent">
                        <div class="tab-pane fade show active" id="profile" role="tabpanel">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr><th width="30%">Admission No</th><td width="70%"><?php echo htmlspecialchars($student['admission_no']); ?></td></tr>
                                    <tr><th>Roll No</th><td><?php echo htmlspecialchars($student['roll_no'] ?? 'N/A'); ?></td></tr>
                                    <tr><th>Admission Date</th><td><?php echo date('d M, Y', strtotime($student['admission_date'])); ?></td></tr>
                                    <tr><th>Date of Birth</th><td><?php echo $student['dob'] ? date('d M, Y', strtotime($student['dob'])) : 'N/A'; ?></td></tr>
                                    <tr><th>Gender</th><td><?php echo ucfirst($student['gender']); ?></td></tr>
                                    <tr><th>Email (Login)</th><td><?php echo htmlspecialchars($student['email']); ?></td></tr>
                                    <tr><th>Mobile Number</th><td><?php echo htmlspecialchars($student['mobile_no'] ?? 'N/A'); ?></td></tr>
                                    <tr><td colspan="2" class="bg-light fw-bold">Parent Details</td></tr>
                                    <tr><th>Father's Name</th><td><?php echo htmlspecialchars($student['father_name'] ?? 'N/A'); ?></td></tr>
                                    <tr><th>Father's Phone</th><td><?php echo htmlspecialchars($student['father_phone'] ?? 'N/A'); ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="history" role="tabpanel">
                            <h5 class="mb-3">Student Enrollment History</h5>
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Academic Session</th>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>Roll No</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($enrollment_history)): ?>
                                        <tr><td colspan="4" class="text-center">No enrollment history found.</td></tr>
                                    <?php else: foreach ($enrollment_history as $enrollment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($enrollment['session_name']); ?>
                                                <?php if ($enrollment['is_current']): ?><span class="badge bg-success ms-2">Current</span><?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($enrollment['class_name']); ?></td>
                                            <td><?php echo htmlspecialchars($enrollment['section_name']); ?></td>
                                            <td><?php echo htmlspecialchars($enrollment['roll_no'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="results" role="tabpanel">
                            <h5 class="mb-3">Exam Results History</h5>
                            <?php if (empty($results_by_session)): ?>
                                <div class="alert alert-info">No exam results found for this student.</div>
                            <?php else: ?>
                                <div class="accordion" id="resultsAccordion">
                                    <?php $first = true; foreach ($results_by_session as $session_name => $exams): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading-<?php echo $exams[0]['session_id']; ?>">
                                            <button class="accordion-button <?php echo !$first ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $exams[0]['session_id']; ?>" aria-expanded="<?php echo $first ? 'true' : 'false'; ?>">
                                                <?php echo htmlspecialchars($session_name); ?>
                                            </button>
                                        </h2>
                                        <div id="collapse-<?php echo $exams[0]['session_id']; ?>" class="accordion-collapse collapse <?php echo $first ? 'show' : ''; ?>" data-bs-parent="#resultsAccordion">
                                            <div class="accordion-body">
                                                <ul class="list-group">
                                                    <?php foreach ($exams as $exam): ?>
                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                            <?php echo htmlspecialchars($exam['exam_name']); ?>
                                                            <a href="<?php echo BASE_URL; ?>/superadmin/exams/print_report_card.php?session_id=<?php echo $exam['session_id']; ?>&exam_id=<?php echo $exam['exam_id']; ?>&student_id=<?php echo $student_id; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-id-card me-1"></i> View Report Card
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <?php $first = false; endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>

```

With these changes, the Super Admin can now seamlessly navigate from a high-level list of all students to a detailed profile for any individual student, providing a powerful tool for system-wide monitoring and management.

<!--
[PROMPT_SUGGESTION]Let's create a dashboard for the Teacher role.[/PROMPT_SUGGESTION]
[PROMPT_SUGGESTION]Can you add a feature to automatically delete backups older than the retention period?[/PROMPT_SUGGESTION]
