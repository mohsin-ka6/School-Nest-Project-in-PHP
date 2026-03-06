<?php
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];

$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

if (!$session_id || !$exam_id || !$student_id) {
    die("Required parameters (session, exam, student) are missing.");
}

// --- Fetch all necessary data ---

// 1. Student, Enrollment, and Branch Info
$stmt_student_info = $db->prepare("
    SELECT u.full_name, se.roll_no, s.photo, c.id as class_id, c.name as class_name, sec.name as section_name, b.name as branch_name, b.address as branch_address, sess.name as session_name
    FROM student_enrollments se
    JOIN students s ON se.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN classes c ON se.class_id = c.id
    JOIN sections sec ON se.section_id = sec.id
    JOIN branches b ON s.branch_id = b.id
    JOIN academic_sessions sess ON se.session_id = sess.id
    WHERE s.id = ? AND se.session_id = ? AND s.branch_id = ?
");
$stmt_student_info->execute([$student_id, $session_id, $branch_id]);
$student_info = $stmt_student_info->fetch();

if (!$student_info) die("Student not found.");

$class_id = $student_info['class_id'];

// 2. Exam Info (now includes publish date)
$stmt_exam_info = $db->prepare("SELECT name, publish_date FROM exam_types WHERE id = ?");
$stmt_exam_info->execute([$exam_id]);
$exam_info = $stmt_exam_info->fetch();

if (!$exam_info) die("Exam not found.");

$exam_name = $exam_info['name'];
$publish_date = $exam_info['publish_date'];

// --- Access Control based on Publish Date ---
// This check applies only to students and parents. Admins can always see the report.
$today = date('Y-m-d');
if (in_array($_SESSION['role'], ['student', 'parent']) && $publish_date && $today < $publish_date) {
    die("The results for this exam have not been published yet. Please check back on " . date('d F, Y', strtotime($publish_date)) . ".");
}

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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Card - <?php echo htmlspecialchars($student_info['full_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .report-card {
            width: 8.5in;
            min-height: 11in;
            margin: 0 auto;
            background-color: #fff;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 40px;
            box-sizing: border-box;
            position: relative;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header .logo {
            width: 80px;
            margin-bottom: 10px;
        }
        .header h1 {
            font-size: 24px;
            margin: 0;
            color: #333;
        }
        .header h2 {
            font-size: 18px;
            margin: 5px 0 15px;
            color: #555;
        }
        .header h3 {
            font-size: 20px;
            margin: 0;
            color: #222;
        }
        .student-info, .academic-performance, .result-summary {
            margin-bottom: 30px;
        }
        .student-info table, .academic-performance table, .result-summary table {
            width: 100%;
            border-collapse: collapse;
        }
        .student-info table th, .student-info table td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: left;
            font-size: 14px;
        }
        .student-info table th {
            background-color: #f2f2f2;
            font-weight: bold;
            width: 150px;
        }
        .student-info .photo {
            width: 100px;
            height: 120px;
            border: 1px solid #ccc;
            object-fit: cover;
            vertical-align: top;
            margin-right: 20px;
        }
        .academic-performance h3, .result-summary h3 {
            text-align: center;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
        }
        .academic-performance table th, .academic-performance table td {
            border: 1px solid #ccc;
            padding: 12px;
            text-align: center;
            font-size: 14px;
        }
        .academic-performance table th {
            background-color: #e6e6e6;
            font-weight: bold;
        }
        .result-summary table td {
            padding: 12px;
            font-size: 14px;
            border: none;
            border-bottom: 1px solid #ccc;
        }
        .result-summary table .total-marks {
            font-weight: bold;
        }
        .result-summary table .pass-status {
            font-weight: bold;
            color: white;
            padding: 5px 15px;
            border-radius: 5px;
            text-transform: uppercase;
        }
        .result-summary table .pass {
            background-color: #28a745;
        }
        .result-summary table .fail {
            background-color: #dc3545;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            position: absolute;
            bottom: 40px;
            left: 40px;
            right: 40px;
        }
        .signatures div {
            text-align: center;
            width: 45%;
        }
        .signatures .line {
            border-top: 1px solid #000;
            padding-top: 5px;
            font-size: 14px;
        }
        @media print {
            .no-print { display: none; }
            body { background-color: #fff; margin: 0; padding: 0; }
            .report-card { box-shadow: none; border: none; margin: 0; padding: 20px; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="report-card">
    <div class="header">
        <?php if (SITE_LOGO && file_exists(ROOT_PATH . '/' . SITE_LOGO)): ?>
            <img src="<?php echo BASE_URL . '/' . SITE_LOGO; ?>" alt="School Logo" class="logo">
        <?php endif; ?>
        <h1><?php echo htmlspecialchars(SITE_NAME); ?></h1>
        <h2><?php echo htmlspecialchars($student_info['branch_name']); ?></h2>
        <h3><?php echo htmlspecialchars($exam_name); ?> Report Card</h3>
    </div>

    <hr>

    <div class="student-info">
        <table>
            <tr>
                <td rowspan="3" style="border: none; vertical-align: top; padding: 0;">
                    <img src="<?php echo $student_info['photo'] ? BASE_URL . '/' . htmlspecialchars($student_info['photo']) : BASE_URL . '/assets/images/default_avatar.png'; ?>" alt="Student Photo" class="photo">
                </td>
                <th>Student Name</th>
                <td><?php echo htmlspecialchars($student_info['full_name']); ?></td>
                <th>Roll No</th>
                <td><?php echo htmlspecialchars($student_info['roll_no']); ?></td>
            </tr>
            <tr>
                <th>Class</th>
                <td><?php echo htmlspecialchars($student_info['class_name']); ?></td>
                <th>Section</th>
                <td><?php echo htmlspecialchars($student_info['section_name']); ?></td>
            </tr>
            <tr>
                <th>Session</th>
                <td colspan="3"><?php echo htmlspecialchars($student_info['session_name']); ?></td>
            </tr>
        </table>
    </div>

    <div class="academic-performance">
        <h3>Academic Performance</h3>
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Full Marks</th>
                    <th>Pass Marks</th>
                    <th>Marks Obtained</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($marks_details as $mark): ?>
                <tr>
                    <td style="text-align: left;"><?php echo htmlspecialchars($mark['subject_name']); ?></td>
                    <td><?php echo htmlspecialchars($mark['full_marks']); ?></td>
                    <td><?php echo htmlspecialchars($mark['pass_marks']); ?></td>
                    <td><?php echo $mark['attendance_status'] == 'absent' ? 'AB' : htmlspecialchars(number_format((float)$mark['marks_obtained'], 2, '.', '')); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="result-summary">
        <h3>Result Summary</h3>
        <table>
            <tr>
                <td class="total-marks">Total Marks</td>
                <td><?php echo number_format($total_marks_obtained, 2) . ' / ' . number_format($total_full_marks, 2); ?></td>
            </tr>
            <tr>
                <td>Percentage</td>
                <td><?php echo round($percentage, 2); ?>%</td>
            </tr>
            <tr>
                <td>Grade</td>
                <td><?php echo htmlspecialchars($final_grade); ?></td>
            </tr>
            <tr>
                <td>Result</td>
                <td><span class="pass-status <?php echo $result == 'Pass' ? 'pass' : 'fail'; ?>"><?php echo $result; ?></span></td>
            </tr>
        </table>
    </div>

    <div class="signatures">
        <div>
            <div class="line">Class Teacher's Signature</div>
        </div>
        <div>
            <div class="line">Principal's Signature</div>
        </div>
    </div>
</div>

<div class="text-center mt-4 no-print">
    <button onclick="window.print()" class="btn btn-primary">Print Report Card</button>
</div>

</body>
</html>