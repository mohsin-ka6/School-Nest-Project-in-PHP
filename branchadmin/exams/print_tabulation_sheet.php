<?php
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];

$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;

if (!$session_id || !$exam_id || !$class_id || !$section_id) {
    die("Required parameters (session, exam, class, section) are missing.");
}

// --- Start of copied data fetching logic ---
$scheduled_subjects = [];
$tabulation_data = [];

// Fetch session name
$stmt_info = $db->prepare("SELECT name FROM academic_sessions WHERE id = ?");
$stmt_info->execute([$session_id]);
$session_name = $stmt_info->fetchColumn();

// Fetch exam, class, and section names for the header
$stmt_info = $db->prepare("SELECT name FROM exam_types WHERE id = ?");
$stmt_info->execute([$exam_id]);
$exam_name = $stmt_info->fetchColumn();

$stmt_info = $db->prepare("SELECT name FROM branches WHERE id = ?");
$stmt_info->execute([$branch_id]);
$branch_name = $stmt_info->fetchColumn();

$stmt_info = $db->prepare("SELECT name FROM classes WHERE id = ?");
$stmt_info->execute([$class_id]);
$class_name = $stmt_info->fetchColumn();

$stmt_info = $db->prepare("SELECT name FROM sections WHERE id = ?");
$stmt_info->execute([$section_id]);
$section_name = $stmt_info->fetchColumn();

// 1. Get scheduled subjects for the header
$stmt_subjects = $db->prepare("
    SELECT es.subject_id, s.name as subject_name, es.full_marks, es.pass_marks
    FROM exam_schedule es
    JOIN subjects s ON es.subject_id = s.id
    WHERE es.session_id = ? AND es.exam_type_id = ? AND es.class_id = ? AND es.branch_id = ?
    ORDER BY s.name
");
$stmt_subjects->execute([$session_id, $exam_id, $class_id, $branch_id]);
$scheduled_subjects = $stmt_subjects->fetchAll(PDO::FETCH_ASSOC);

if (!empty($scheduled_subjects)) {
    // 2. Get students in the section
    $stmt_students = $db->prepare("
        SELECT s.id as student_id, u.full_name, se.roll_no
        FROM student_enrollments se
        JOIN students s ON se.student_id = s.id
        JOIN users u ON s.user_id = u.id
        WHERE se.session_id = ? AND se.section_id = ? AND s.branch_id = ?
        ORDER BY se.roll_no, u.full_name
    ");
    $stmt_students->execute([$session_id, $section_id, $branch_id]);
    $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    // 3. Get all marks for this exam/class/section
    $stmt_marks = $db->prepare("
        SELECT em.student_id, em.subject_id, em.marks_obtained, em.attendance_status
        FROM exam_marks em
        JOIN exam_schedule es ON em.exam_schedule_id = es.id
        WHERE es.session_id = ? AND es.exam_type_id = ? AND em.class_id = ? AND em.section_id = ? AND em.branch_id = ?
    ");
    $stmt_marks->execute([$session_id, $exam_id, $class_id, $section_id, $branch_id]);
    $all_marks = $stmt_marks->fetchAll(PDO::FETCH_ASSOC);

    // 4. Create a lookup array for marks
    $marks_lookup = [];
    foreach ($all_marks as $mark) {
        $marks_lookup[$mark['student_id']][$mark['subject_id']] = ['marks' => $mark['marks_obtained'], 'attendance' => $mark['attendance_status']];
    }

    // 5. Get grading scale
    $stmt_grades = $db->prepare("SELECT * FROM marks_grades WHERE branch_id = ? ORDER BY percent_from DESC");
    $stmt_grades->execute([$branch_id]);
    $grades = $stmt_grades->fetchAll(PDO::FETCH_ASSOC);

    // 6. Prepare data for tabulation
    foreach ($students as $student) {
        $total_marks_obtained = 0;
        $total_full_marks = 0;
        $is_fail = false;
        $student_marks = [];

        foreach ($scheduled_subjects as $subject) {
            $subject_id = $subject['subject_id'];
            $marks_info = $marks_lookup[$student['student_id']][$subject_id] ?? ['marks' => null, 'attendance' => 'present'];
            $student_marks[$subject_id] = $marks_info;

            if ($marks_info['attendance'] == 'present' && $marks_info['marks'] !== null) {
                $total_marks_obtained += $marks_info['marks'];
                if ($marks_info['marks'] < $subject['pass_marks']) $is_fail = true;
            }
            $total_full_marks += $subject['full_marks'];
        }

        $percentage = ($total_full_marks > 0) ? ($total_marks_obtained / $total_full_marks) * 100 : 0;
        $final_grade = 'N/A';
        foreach ($grades as $grade) {
            if ($percentage >= $grade['percent_from'] && $percentage <= $grade['percent_upto']) {
                $final_grade = $grade['grade_name'];
                break;
            }
        }

        $tabulation_data[$student['student_id']] = [
            'info' => $student, 'marks' => $student_marks, 'total_obtained' => $total_marks_obtained,
            'total_full' => $total_full_marks, 'percentage' => $percentage, 'grade' => $final_grade,
            'result' => ($is_fail) ? 'Fail' : 'Pass'
        ];
    }

    // Sort the data by total marks obtained in descending order to determine position
    uasort($tabulation_data, function($a, $b) {
        return $b['total_obtained'] <=> $a['total_obtained'];
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Tabulation Sheet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .table { font-size: 12px; }
        .table th, .table td { padding: 0.4rem; }
        @media print {
            .no-print { display: none; }
            @page { size: A4 landscape; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="container-fluid">
    <div class="header">
        <h2><?php echo htmlspecialchars(SITE_NAME); ?></h2>
        <p class="lead mb-1"><?php echo htmlspecialchars($branch_name); ?></p>
        <h4>Tabulation Sheet - <?php echo htmlspecialchars($exam_name); ?></h4>
        <p>Session: <?php echo htmlspecialchars($session_name); ?> | Class: <?php echo htmlspecialchars($class_name); ?> | Section: <?php echo htmlspecialchars($section_name); ?></p>
    </div>

    <?php if (!empty($tabulation_data)): ?>
    <table class="table table-bordered text-center">
        <thead>
            <tr class="bg-light">
                <th>Pos.</th>
                <th>Roll</th>
                <th class="text-start">Student Name</th>
                <?php foreach ($scheduled_subjects as $subject) echo "<th>" . htmlspecialchars($subject['subject_name']) . "<br><small>(" . $subject['full_marks'] . ")</small></th>"; ?>
                <th>Total</th>
                <th>%</th>
                <th>Grade</th>
                <th>Result</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $position = 0;
            $last_marks = -1;
            $counter = 1;
            ?>
            <?php foreach ($tabulation_data as $data): ?>
            <?php
            // Logic to handle ties in position
            if ($data['total_obtained'] != $last_marks) {
                $position = $counter;
                $last_marks = $data['total_obtained'];
            }
            ?>
            <tr>
                <td><?php echo $position; ?></td>
                <td><?php echo htmlspecialchars($data['info']['roll_no']); ?></td>
                <td class="text-start"><?php echo htmlspecialchars($data['info']['full_name']); ?></td>
                <?php foreach ($scheduled_subjects as $subject): ?>
                    <td>
                        <?php 
                        $marks_info = $data['marks'][$subject['subject_id']];
                        if ($marks_info['attendance'] == 'absent') echo 'AB';
                        else echo htmlspecialchars($marks_info['marks'] ?? '-');
                        ?>
                    </td>
                <?php endforeach; ?>
                <td><?php echo $data['total_obtained']; ?></td>
                <td><?php echo round($data['percentage'], 2); ?></td>
                <td><?php echo htmlspecialchars($data['grade']); ?></td>
                <td><?php echo $data['result']; ?></td>
            </tr>
            <?php $counter++; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <div class="alert alert-warning">No data available to print for the selected criteria.</div>
    <?php endif; ?>

    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn btn-primary">Print</button>
    </div>
</div>

</body>
</html>