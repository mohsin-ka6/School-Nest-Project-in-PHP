<?php
$page_title = "Transfer Student";
require_once '../../config.php';
require_once '../../functions.php';

check_role('superadmin');

$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];
$success_message = '';

if (!$student_id) {
    $_SESSION['error_message'] = "Invalid student ID.";
    redirect('manage_all_students.php');
}

// Fetch student and parent data
$stmt_student = $db->prepare("
    SELECT s.id, s.parent_id, s.branch_id, u.full_name, b.name as current_branch_name
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN branches b ON s.branch_id = b.id
    WHERE s.id = ?
");
$stmt_student->execute([$student_id]);
$student = $stmt_student->fetch();

if (!$student) {
    $_SESSION['error_message'] = "Student not found.";
    redirect('manage_all_students.php');
}

// Fetch all other branches for the dropdown
$stmt_branches = $db->prepare("SELECT id, name FROM branches WHERE id != ? ORDER BY name");
$stmt_branches->execute([$student['branch_id']]);
$branches = $stmt_branches->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transfer_student'])) {
    $new_branch_id = isset($_POST['new_branch_id']) ? (int)$_POST['new_branch_id'] : 0;
    $new_class_id = isset($_POST['new_class_id']) ? (int)$_POST['new_class_id'] : 0;
    $new_section_id = isset($_POST['new_section_id']) ? (int)$_POST['new_section_id'] : 0;

    if (empty($new_branch_id)) {
        $errors[] = "Please select a new branch to transfer the student to.";
    }

    // Check if the new branch exists
    $stmt_check = $db->prepare("SELECT id FROM branches WHERE id = ?");
    $stmt_check->execute([$new_branch_id]);
    if ($stmt_check->fetch() === false) {
        $errors[] = "The selected branch is invalid.";
    }

    if (empty($new_class_id)) $errors[] = "Please select a class in the new branch.";
    if (empty($new_section_id)) $errors[] = "Please select a section in the new branch.";

    // Find the current academic session for the NEW branch
    $new_session_id = null;
    if ($new_branch_id) {
        $stmt_session = $db->prepare("SELECT id FROM academic_sessions WHERE branch_id = ? AND is_current = 1");
        $stmt_session->execute([$new_branch_id]);
        $new_session_id = $stmt_session->fetchColumn();
    }

    if (!$new_session_id) {
        $errors[] = "The destination branch does not have an active academic session. Please set one before transferring students.";
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // 1. Update student's branch
            $stmt_update_student = $db->prepare("UPDATE students SET branch_id = ? WHERE id = ?");
            $stmt_update_student->execute([$new_branch_id, $student_id]);

            // 2. Update parent's branch, if they have a parent record
            if ($student['parent_id']) {
                $stmt_update_parent = $db->prepare("UPDATE parents SET branch_id = ? WHERE id = ?");
                $stmt_update_parent->execute([$new_branch_id, $student['parent_id']]);
            }

            // 3. Update or create the enrollment record for the student in the new branch's current session.
            // This will either create a new enrollment or update an existing one for the student in the target session.
            $stmt_enroll = $db->prepare("
                INSERT INTO student_enrollments (student_id, session_id, class_id, section_id, roll_no)
                VALUES (?, ?, ?, ?, NULL)
                ON DUPLICATE KEY UPDATE class_id = VALUES(class_id), section_id = VALUES(section_id), roll_no = VALUES(roll_no)
            ");
            $stmt_enroll->execute([$student_id, $new_session_id, $new_class_id, $new_section_id]);

            $db->commit();

            $_SESSION['success_message'] = "Student " . htmlspecialchars($student['full_name']) . " has been successfully transferred and enrolled in their new class.";
            redirect('view_student.php?id=' . $student_id);

        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Database Error: Failed to transfer student. " . $e->getMessage();
        }
    }
}

require_once '../../header.php';
?>

<?php require_once '../sidebar_superadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?>: <?php echo htmlspecialchars($student['full_name']); ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="manage_all_students.php">All Students</a></li>
        <li class="breadcrumb-item"><a href="view_student.php?id=<?php echo $student_id; ?>">Student Profile</a></li>
        <li class="breadcrumb-item active">Transfer</li>
    </ol>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-exchange-alt me-1"></i> Transfer Student to a New Branch</div>
        <div class="card-body">
            <form action="transfer_student.php?id=<?php echo $student_id; ?>" method="POST">
                <div class="mb-3">
                    <label class="form-label">Student Name</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['full_name']); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Current Branch</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['current_branch_name']); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="new_branch_id" class="form-label">Select New Branch*</label>
                    <select name="new_branch_id" id="branch_id" class="form-select" required>
                        <option value="">-- Select a branch --</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="class_id" class="form-label">New Class*</label>
                        <select name="new_class_id" id="class_id" class="form-select" required disabled>
                            <option value="">-- Select Branch First --</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="section_id" class="form-label">New Section*</label>
                        <select name="new_section_id" id="section_id" class="form-select" required disabled>
                            <option value="">-- Select Class First --</option>
                        </select>
                    </div>
                </div>
                <div class="alert alert-warning">
                    <strong>Important:</strong> Transferring a student will also transfer their linked parent. The student will be automatically enrolled in the selected class for the new branch's current academic session.
                </div>
                <button type="submit" name="transfer_student" class="btn btn-primary">Confirm Transfer</button>
                <a href="view_student.php?id=<?php echo $student_id; ?>" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const branchSelect = document.getElementById('branch_id');
    const classSelect = document.getElementById('class_id');
    const sectionSelect = document.getElementById('section_id');

    branchSelect.addEventListener('change', function() {
        const branchId = this.value;
        classSelect.innerHTML = '<option value="">Loading...</option>';
        sectionSelect.innerHTML = '<option value="">-- Select Class First --</option>';
        classSelect.disabled = true;
        sectionSelect.disabled = true;

        if (!branchId) {
            classSelect.innerHTML = '<option value="">-- Select Branch First --</option>';
            return;
        }

        fetch(`<?php echo BASE_URL; ?>/api/get_classes_by_branch.php?branch_id=${branchId}`)
            .then(response => response.json())
            .then(data => {
                let options = '<option value="">-- Select Class --</option>';
                data.forEach(cls => {
                    options += `<option value="${cls.id}">${cls.name}</option>`;
                });
                classSelect.innerHTML = options;
                classSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error fetching classes:', error);
                classSelect.innerHTML = '<option value="">-- Error Loading --</option>';
            });
    });

    classSelect.addEventListener('change', function() {
        const branchId = branchSelect.value;
        const classId = this.value;
        sectionSelect.innerHTML = '<option value="">Loading...</option>';
        sectionSelect.disabled = true;

        if (!classId || !branchId) {
            sectionSelect.innerHTML = '<option value="">-- Select Branch and Class First --</option>';
            return;
        }

        fetch(`<?php echo BASE_URL; ?>/api/get_sections.php?class_id=${classId}&branch_id=${branchId}`)
            .then(response => response.json())
            .then(data => {
                let options = '<option value="">-- Select Section --</option>';
                data.forEach(section => {
                    options += `<option value="${section.id}">${section.name}</option>`;
                });
                sectionSelect.innerHTML = options;
                sectionSelect.disabled = false;
            });
    });
});
</script>
<?php require_once '../../footer.php'; ?>