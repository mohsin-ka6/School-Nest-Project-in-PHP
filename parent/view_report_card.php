<?php
$page_title = "Report Card";
require_once '../config.php';
require_once '../functions.php';

check_role('parent');

$user_id = $_SESSION['user_id'];
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;

if (!$student_id || !$exam_id || !$session_id) {
    $_SESSION['error_message'] = "Invalid parameters.";
    redirect('dashboard.php');
}

// --- Security Check: Ensure parent is viewing their own child ---
$stmt_parent = $db->prepare("SELECT id FROM parents WHERE user_id = ?");
$stmt_parent->execute([$user_id]);
$parent_id = $stmt_parent->fetchColumn();

// --- Fetch all necessary data ---

// 1. Student and Branch Info (with security check)
$stmt_student_info = $db->prepare("
    SELECT u.full_name, se.roll_no, s.photo, c.id as class_id, c.name as class_name, sec.name as section_name, b.name as branch_name, b.address as branch_address, s.branch_id, sess.name as session_name
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN student_enrollments se ON s.id = se.student_id AND se.session_id = :session_id
    JOIN classes c ON se.class_id = c.id
    JOIN sections sec ON se.section_id = sec.id
    JOIN branches b ON s.branch_id = b.id
    JOIN academic_sessions sess ON se.session_id = sess.id
    WHERE s.id = :student_id AND s.parent_id = :parent_id
");
$stmt_student_info->execute([':student_id' => $student_id, ':parent_id' => $parent_id, ':session_id' => $session_id]);
$student_info = $stmt_student_info->fetch();

if (!$student_info) {
    $_SESSION['error_message'] = "You do not have permission to view this report card.";
    redirect('dashboard.php');
}

$class_id = $student_info['class_id'];
$branch_id = $student_info['branch_id'];

// 2. Exam Info
$stmt_exam_info = $db->prepare("SELECT name, publish_date FROM exam_types WHERE id = ?");
$stmt_exam_info->execute([$exam_id]);
$exam_info = $stmt_exam_info->fetch();

if (!$exam_info || ($exam_info['publish_date'] !== null && $exam_info['publish_date'] > date('Y-m-d'))) {
    $_SESSION['error_message'] = "This report card is not yet available for viewing.";
    redirect('view_child_profile.php?id=' . $student_id);
}

$exam_name = $exam_info['name'];

// 3. Scheduled Subjects and Marks for this student
$stmt_marks = $db->prepare("
    SELECT s.name as subject_name, es.full_marks, es.pass_marks, em.marks_obtained, em.attendance_status
    FROM exam_schedule es
    JOIN subjects s ON es.subject_id = s.id
    LEFT JOIN exam_marks em ON es.id = em.exam_schedule_id AND em.student_id = ?
    WHERE es.session_id = ? AND es.exam_type_id = ? AND es.class_id = ? AND es.branch_id = ?
    ORDER BY s.name
");
$stmt_marks->execute([$student_id, $session_id, $exam_id, $class_id, $branch_id]);
$marks_details = $stmt_marks->fetchAll();

// 4. Grading Scale
$stmt_grades = $db->prepare("SELECT * FROM marks_grades WHERE branch_id = ? ORDER BY percent_from DESC");
$stmt_grades->execute([$branch_id]);
$grades = $stmt_grades->fetchAll();

// --- Calculations ---
$total_marks_obtained = 0;
$total_full_marks = 0;
$is_fail = false;

foreach ($marks_details as $mark) {
    if ($mark['attendance_status'] == 'present' && $mark['marks_obtained'] !== null) {
        $total_marks_obtained += $mark['marks_obtained'];
        if ($mark['marks_obtained'] < $mark['pass_marks']) {
            $is_fail = true;
        }
    }
    $total_full_marks += $mark['full_marks'];
}

$percentage = ($total_full_marks > 0) ? ($total_marks_obtained / $total_full_marks) * 100 : 0;
$final_grade = 'N/A';
foreach ($grades as $grade) {
    if ($percentage >= $grade['percent_from'] && $percentage <= $grade['percent_upto']) {
        $final_grade = $grade['grade_name'];
        break;
    }
}
$result = $is_fail ? 'Fail' : 'Pass';

require_once '../header.php';
?>

<?php require_once ROOT_PATH . '/parent/sidebar_parent.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">

	<div class="d-flex justify-content-between align-items-center mb-3">
		<h4 class="m-0"><?php echo htmlspecialchars($exam_name); ?></h4>
		<div class="no-print">
			<button id="download-report-btn" class="btn btn-success btn-sm"><i class="fas fa-download me-1"></i> Download Report</button>
		</div>
	</div>

	<style>
		/* Styles for the printable/downloadable report card */
		.report-card-print {
			border: 5px double #000;
			padding: 20px;
			max-width: 800px; /* Max width for large screens */
            width: 100%;      /* Responsive width */
			background: #fff;
			color: #000;
            margin: auto;
		}
		.report-header-print { text-align: center; margin-bottom: 20px; }
		.student-photo-print { width: 100px; height: 120px; border: 1px solid #ccc; object-fit: cover; }

		/* Print styles */
		@media print {
			body * { visibility: hidden; }
			.print-area, .print-area * { visibility: visible; }
			.print-area { position: absolute; left: 0; top: 0; width: 100%; }
			.no-print { display: none; }
		}
	</style>

	<!-- Hidden Printable Area -->
	<div class="print-area" id="report-card-area">
		<div class="report-card-print" id="printable-report-card">
			<div class="report-header-print">
				<?php if (SITE_LOGO && file_exists(ROOT_PATH . '/' . SITE_LOGO)): ?>
					<img src="<?php echo BASE_URL . '/' . SITE_LOGO; ?>" alt="School Logo" style="width: 80px; margin-bottom: 10px;">
				<?php endif; ?>
				<h2><?php echo htmlspecialchars(SITE_NAME); ?></h2>
				<p><?php echo htmlspecialchars($student_info['branch_name']); ?></p>
				<h5><?php echo htmlspecialchars($exam_name); ?> Report Card</h5>
			</div>

			<table class="table table-bordered mb-4">
				<tbody>
					<tr>
						<td rowspan="3" class="text-center align-middle" style="width: 120px;"><img src="<?php echo $student_info['photo'] ? BASE_URL . '/' . htmlspecialchars($student_info['photo']) : BASE_URL . '/assets/images/default_avatar.png'; ?>" alt="Student Photo" class="student-photo-print"></td>
						<th width="20%">Student Name</th><td width="30%"><?php echo htmlspecialchars($student_info['full_name']); ?></td>
						<th width="20%">Roll No</th><td width="30%"><?php echo htmlspecialchars($student_info['roll_no']); ?></td>
					</tr>
					<tr>
						<th>Class</th><td><?php echo htmlspecialchars($student_info['class_name']); ?></td>
						<th>Section</th><td><?php echo htmlspecialchars($student_info['section_name']); ?></td>
					</tr>
                    <tr>
						<th>Session</th><td colspan="3"><?php echo htmlspecialchars($student_info['session_name']); ?></td>
					</tr>
				</tbody>
			</table>

			<h5 class="text-center">Academic Performance</h5>
			<table class="table table-bordered text-center">
				<thead class="table-light"><tr><th>Subject</th><th>Full Marks</th><th>Pass Marks</th><th>Marks Obtained</th></tr></thead>
				<tbody>
					<?php foreach ($marks_details as $mark): ?>
					<tr>
						<td class="text-start"><?php echo htmlspecialchars($mark['subject_name']); ?></td>
						<td><?php echo htmlspecialchars($mark['full_marks']); ?></td>
						<td><?php echo htmlspecialchars($mark['pass_marks']); ?></td>
						<td><?php echo $mark['attendance_status'] == 'absent' ? '<span class="badge bg-danger">Absent</span>' : htmlspecialchars($mark['marks_obtained'] ?? '-'); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<div class="row mt-4">
                <div class="col-8">
                    <table class="table table-bordered">
                        <thead class="table-light"><tr><th colspan="2">Result Summary</th></tr></thead>
                        <tbody>
                            <tr><td style="font-weight:bold;">Total Marks</td><td style="font-weight:bold;"><?php echo $total_marks_obtained . ' / ' . $total_full_marks; ?></td></tr>
                            <tr><td style="font-weight:bold;">Percentage</td><td style="font-weight:bold;"><?php echo round($percentage, 2); ?>%</td></tr>
                            <tr><td style="font-weight:bold;">Grade</td><td style="font-weight:bold;"><?php echo htmlspecialchars($final_grade); ?></td></tr>
                            <tr><td style="font-weight:bold;">Result</td><td style="font-weight:bold;"><span class="badge bg-<?php echo $result == 'Pass' ? 'success' : 'danger'; ?> fs-6"><?php echo $result; ?></span></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
			<div class="row mt-5 signature-row" id="signature-area"><div class="col-6 text-center"><hr><p>Class Teacher's Signature</p></div><div class="col-6 text-center"><hr><p>Principal's Signature</p></div></div>
		</div>
	</div>
</div>

<?php require_once '../footer.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
document.getElementById('download-report-btn').addEventListener('click', function() {
    const reportCardElement = document.getElementById('printable-report-card');
    const signatureElement = document.getElementById('signature-area');

    // Temporarily hide the signature area and set a fixed width for consistent capture
    signatureElement.style.display = 'none';
    reportCardElement.style.width = '800px'; // Force desktop width for capture

    html2canvas(reportCardElement, {
        scale: 2, // Increase scale for better quality
        useCORS: true // For external images if any
    }).then(canvas => {
        // Restore original styles after capture
        signatureElement.style.display = '';
        reportCardElement.style.width = ''; // Reset width to let CSS rule apply again

        // Create a link to download the image
        const link = document.createElement('a');
        link.download = 'report-card-<?php echo str_replace(' ', '_', $student_info['full_name']); ?>.jpg';
        link.href = canvas.toDataURL('image/jpeg', 0.9); // 0.9 is quality
        link.click();
    }).catch(err => {
        // In case of error, ensure original styles are restored
        signatureElement.style.display = '';
        reportCardElement.style.width = ''; // Reset width
        console.error('Oops, something went wrong!', err);
        alert('Could not download the report card. Please try again.');
    });
});
</script>
