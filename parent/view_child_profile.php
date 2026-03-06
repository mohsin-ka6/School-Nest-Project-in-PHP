<?php
$page_title = "Child Profile";
require_once '../config.php';
require_once '../functions.php';

check_role('parent');

$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$student_id) {
    $_SESSION['error_message'] = "Invalid student ID.";
    redirect('my_children.php');
}

// --- Security Check: Ensure parent is viewing their own child ---
$stmt_parent = $db->prepare("SELECT id FROM parents WHERE user_id = ?");
$stmt_parent->execute([$_SESSION['user_id']]);
$parent_id = $stmt_parent->fetchColumn();

// Fetch student details with security check
$stmt_student = $db->prepare("
    SELECT 
        s.*, 
        u.full_name, u.email, u.username,
        c.name as class_name, 
        sec.name as section_name, 
        b.name as branch_name,
        se.roll_no,
        sess.name as session_name
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN branches b ON s.branch_id = b.id
    LEFT JOIN student_enrollments se ON s.id = se.student_id
    LEFT JOIN academic_sessions sess ON se.session_id = sess.id AND sess.is_current = 1
    LEFT JOIN classes c ON se.class_id = c.id
    LEFT JOIN sections sec ON se.section_id = sec.id
    WHERE s.id = ? AND s.parent_id = ?
");
$stmt_student->execute([$student_id, $parent_id]);
$student = $stmt_student->fetch();

if (!$student) {
    $_SESSION['error_message'] = "You do not have permission to view this profile.";
    redirect('my_children.php');
}

require_once '../header.php';
?>

<?php require_once '../sidebar_parent.php'; ?>
<?php require_once '../navbar.php'; ?>

<div class="container-fluid px-4">

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <img src="<?php echo $student['photo'] ? BASE_URL . '/' . htmlspecialchars($student['photo']) : BASE_URL . '/assets/images/default_avatar.png'; ?>" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    <h4 class="mb-1"><?php echo htmlspecialchars($student['full_name']); ?></h4>
                    <p class="text-muted mb-0">Student</p>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-user-graduate me-1"></i> Academic Details (Current Session)</div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr><th width="30%">Branch</th><td><?php echo htmlspecialchars($student['branch_name']); ?></td></tr>
                        <tr><th>Session</th><td><?php echo htmlspecialchars($student['session_name'] ?? 'N/A'); ?></td></tr>
                        <tr><th>Class</th><td><?php echo htmlspecialchars($student['class_name'] ?? 'N/A'); ?></td></tr>
                        <tr><th>Section</th><td><?php echo htmlspecialchars($student['section_name'] ?? 'N/A'); ?></td></tr>
                        <tr><th>Roll Number</th><td><?php echo htmlspecialchars($student['roll_no'] ?? 'N/A'); ?></td></tr>
                    </table>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-info-circle me-1"></i> Personal Details</div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr><th width="30%">Full Name</th><td><?php echo htmlspecialchars($student['full_name']); ?></td></tr>
                        <tr><th>Admission No</th><td><?php echo htmlspecialchars($student['admission_no']); ?></td></tr>
                        <tr><th>Date of Birth</th><td><?php echo date('d M, Y', strtotime($student['dob'])); ?></td></tr>
                        <tr><th>Gender</th><td><?php echo ucfirst($student['gender']); ?></td></tr>
                        <tr><th>Phone</th><td><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></td></tr>
                        <tr><th>Email</th><td><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></td></tr>
                        <tr><th>Address</th><td><?php echo htmlspecialchars($student['address'] ?? 'N/A'); ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>