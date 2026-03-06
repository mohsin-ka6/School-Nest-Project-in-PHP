<?php
require_once '../../config.php';
require_once '../../functions.php';

check_role('superadmin');

$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

if (!$session_id || !$exam_id || !$student_id) {
    die("Error: Missing required parameters (Session, Exam, or Student ID).");
}

// 1. Fetch Student, Branch, and Enrollment Details
$stmt_student = $db->prepare("
    SELECT
        s.id as student_id, u.full_name as student_name, s.photo, s.admission_no,
        b.id as branch_id, b.name as branch_name, b.logo as branch_logo, b.address as branch_address,
        p.father_name,
        c.name as class_name,
        sec.name as section_name,
        se.roll_no,
        se.class_id,
        se.section_id
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN branches b ON s.branch_id = b.id
    LEFT JOIN parents p ON s.parent_id = p.id
    JOIN student_enrollments se ON s.id = se.student_id AND se.session_id = :session_id
    JOIN classes c ON se.class_id = c.id
    JOIN sections sec ON se.section_id = sec.id
    WHERE s.id = :student_id
");
$stmt_student->execute([':session_id' => $session_id, ':student_id' => $student_id]);
$student_info = $stmt_student->fetch();

if (!$student_info) {
    die("Error: Student enrollment details not found for the selected session.");
}

$branch_id = $student_info['branch_id'];
$class_id = $student_info['class_id'];
$section_id = $student_info['section_id'];

// 2. Fetch Exam and Session Name
$stmt_exam = $db->prepare("
    SELECT et.name as exam_name, ac.name as session_name
    FROM exam_types et
    JOIN academic_sessions ac ON et.session_id = ac.id
    WHERE et.id = :exam_id AND et.session_id = :session_id AND et.branch_id = :branch_id
");
$stmt_exam->execute([':exam_id' => $exam_id, ':session_id' => $session_id, ':branch_id' => $branch_id]);
$exam_info = $stmt_exam->fetch();

if (!$exam_info) {
    die("Error: Exam details not found.");
}

// 3. Fetch Student's Marks for this Exam
$stmt_marks = $db->prepare("
    SELECT
        sub.name as subject_name,
        es.full_marks,
        es.pass_marks,
        em.marks_obtained,
        em.attendance_status
    FROM exam_marks em
    JOIN exam_schedule es ON em.exam_schedule_id = es.id
    JOIN subjects sub ON em.subject_id = sub.id
    WHERE em.student_id = :student_id
      AND em.session_id = :session_id
      AND es.exam_type_id = :exam_id
    ORDER BY sub.name
");
$stmt_marks->execute([':student_id' => $student_id, ':session_id' => $session_id, ':exam_id' => $exam_id]);
$marks_details = $stmt_marks->fetchAll();

// 4. Fetch Grading Scale
$stmt_grades = $db->prepare("SELECT * FROM marks_grades WHERE branch_id = ? ORDER BY percent_from DESC");
$stmt_grades->execute([$branch_id]);
$grades = $stmt_grades->fetchAll();

// 5. Calculate Totals, Percentage, and Grade
$total_marks_obtained = 0;
$total_full_marks = 0;
$is_fail = false;

foreach ($marks_details as $mark) {
    if ($mark['attendance_status'] == 'present') {
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
$result_status = $is_fail ? 'Fail' : 'Pass';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Card - <?php echo htmlspecialchars($student_info['student_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background: #f0f2f5; color: #333; }
        .container { width: 800px; margin: 30px auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 2px solid #1d3557; padding-bottom: 15px; margin-bottom: 20px; display: flex; align-items: center; }
        .header .logo { height: 70px; margin-right: 20px; }
        .header .school-info { text-align: left; }
        .header h2 { margin: 0; color: #1d3557; font-size: 28px; }
        .header p { margin: 2px 0; font-size: 14px; }
        .report-title { text-align: center; margin-bottom: 20px; }
        .report-title h3 { margin: 0; font-size: 22px; text-decoration: underline; }
        .student-info { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px; }
        .student-info .photo { width: 100px; height: 120px; border: 1px solid #ddd; object-fit: cover; }
        .student-info table { width: 100%; }
        .student-info table { flex-grow: 1; margin-right: 20px; }
        .student-info th, .student-info td { padding: 4px 8px; text-align: left; }
        .student-info th { width: 25%; font-weight: 600; }
        .marks-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .marks-table th, .marks-table td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        .marks-table th { background-color: #f2f7ff; font-weight: 600; }
        .marks-table .subject-col { text-align: left; }
        .summary-section { display: flex; justify-content: space-between; margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; }
        .summary-table { width: 48%; border-collapse: collapse; }
        .summary-table td { padding: 8px; border: 1px solid #ccc; }
        .summary-table .label { font-weight: 600; background-color: #f2f7ff; }
        .footer-signatures { margin-top: 60px; display: flex; justify-content: space-between; text-align: center; }
        .footer-signatures div { width: 30%; padding-top: 10px; border-top: 1px solid #555; }
        @media print {
            @page {
                size: A4;
                margin: 1cm;
            }
            body { background: #fff; }
            .container { margin: 0; box-shadow: none; border: none; width: 100%; border-radius: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container">
        <div class="header">
            <?php if (!empty($student_info['branch_logo']) && file_exists(ROOT_PATH . '/' . $student_info['branch_logo'])): ?>
                <img src="<?php echo BASE_URL . '/' . $student_info['branch_logo']; ?>" alt="Logo" class="logo">
            <?php endif; ?>
            <div class="school-info">
                <h2><?php echo htmlspecialchars(SITE_NAME); ?></h2>
                <p><?php echo htmlspecialchars($student_info['branch_name']); ?></p>
                <p><?php echo htmlspecialchars($student_info['branch_address']); ?></p>
            </div>
        </div>

        <div class="report-title">
            <h3><?php echo htmlspecialchars($exam_info['exam_name']); ?> Report Card</h3>
            <p>Academic Session: <?php echo htmlspecialchars($exam_info['session_name']); ?></p>
        </div>

        <div class="student-info">
            <table>
                <tr>
                    <th>Student Name</th><td><?php echo htmlspecialchars($student_info['student_name']); ?></td>
                    <th>Father's Name</th><td><?php echo htmlspecialchars($student_info['father_name']); ?></td>
                </tr>
                <tr>
                    <th>Admission No</th><td><?php echo htmlspecialchars($student_info['admission_no']); ?></td>
                    <th>Roll No</th><td><?php echo htmlspecialchars($student_info['roll_no']); ?></td>
                </tr>
                <tr>
                    <th>Class</th><td><?php echo htmlspecialchars($student_info['class_name']); ?></td>
                    <th>Section</th><td><?php echo htmlspecialchars($student_info['section_name']); ?></td>
                </tr>
            </table>
            <img src="<?php echo $student_info['photo'] ? BASE_URL . '/' . htmlspecialchars($student_info['photo']) : BASE_URL . '/assets/images/default_avatar.png'; ?>" alt="Photo" class="photo">
        </div>

        <table class="marks-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th class="subject-col">Subject</th>
                    <th>Full Marks</th>
                    <th>Pass Marks</th>
                    <th>Marks Obtained</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($marks_details)): ?>
                    <tr><td colspan="6">No marks have been entered for this exam yet.</td></tr>
                <?php else: $i = 1; foreach ($marks_details as $mark): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td class="subject-col"><?php echo htmlspecialchars($mark['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($mark['full_marks']); ?></td>
                        <td><?php echo htmlspecialchars($mark['pass_marks']); ?></td>
                        <td>
                            <?php
                            if ($mark['attendance_status'] == 'absent') {
                                echo '<span style="color: red; font-weight: bold;">Absent</span>';
                            } else {
                                echo htmlspecialchars($mark['marks_obtained']);
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if ($mark['attendance_status'] == 'present' && $mark['marks_obtained'] < $mark['pass_marks']) {
                                echo '<span style="color: red;">Fail</span>';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <div class="summary-section">
            <table class="summary-table">
                <tr><td class="label">Total Marks</td><td><?php echo htmlspecialchars($total_full_marks); ?></td></tr>
                <tr><td class="label">Marks Obtained</td><td><?php echo htmlspecialchars($total_marks_obtained); ?></td></tr>
                <tr><td class="label">Percentage</td><td><?php echo round($percentage, 2); ?>%</td></tr>
            </table>
            <table class="summary-table">
                <tr><td class="label">Grade</td><td><?php echo htmlspecialchars($final_grade); ?></td></tr>
                <tr><td class="label">Result</td><td>
                    <strong style="color: <?php echo $result_status == 'Pass' ? 'green' : 'red'; ?>;">
                        <?php echo $result_status; ?>
                    </strong>
                </td></tr>
                <tr><td class="label">Position</td><td>N/A</td></tr>
            </table>
        </div>

        <div class="footer-signatures">
            <div>Class Teacher</div>
            <div>Principal</div>
            <div>Parent's Signature</div>
        </div>
    </div>
</body>
</html>
