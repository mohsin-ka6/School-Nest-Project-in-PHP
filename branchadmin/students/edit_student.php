<?php
$page_title = "Edit Student";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$errors = [];

if (!$student_id || !$session_id) {
    $_SESSION['error_message'] = "Invalid student or session ID.";
    redirect('manage_students.php');
}

// Fetch student data to populate the form
// Core student data + parent data
$stmt = $db->prepare("
    SELECT s.*, u.full_name, u.email, u.username,
           p.id as parent_id, p.father_cnic, p.father_name, p.father_phone, p.father_email
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN parents p ON s.parent_id = p.id
    WHERE s.id = ? AND s.branch_id = ?
");
$stmt->execute([$student_id, $branch_id]);
$student = $stmt->fetch();

// Fetch enrollment data for the specific session
$stmt_enroll = $db->prepare("SELECT * FROM student_enrollments WHERE student_id = ? AND session_id = ?");
$stmt_enroll->execute([$student_id, $session_id]);
$enrollment = $stmt_enroll->fetch();

if (!$student) {
    $_SESSION['error_message'] = "Student not found.";
    redirect('manage_students.php');
}

// Fetch classes and sections for dropdowns
$stmt_classes = $db->prepare("SELECT id, name FROM classes WHERE branch_id = ? ORDER BY numeric_name ASC, name ASC");
$stmt_classes->execute([$branch_id]);
$classes = $stmt_classes->fetchAll();

$sections = [];
if ($enrollment && !empty($enrollment['class_id'])) {
    $stmt_sections = $db->prepare("SELECT id, name FROM sections WHERE class_id = ? ORDER BY name ASC");
    $stmt_sections->execute([$enrollment['class_id']]);
    $sections = $stmt_sections->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_student'])) {
    // Sanitize and validate inputs
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $class_id = (int)$_POST['class_id'];
    $section_id = (int)$_POST['section_id'];
    $roll_no = trim($_POST['roll_no']);
    $admission_no = trim($_POST['admission_no']);
    $admission_date = trim($_POST['admission_date']);
    $student_cnic = trim($_POST['student_cnic']);
    $father_cnic = trim($_POST['father_cnic']);
    $existing_parent_id = (int)($_POST['existing_parent_id'] ?? 0);
    $father_name = trim($_POST['father_name']);
    $father_phone = trim($_POST['father_phone']);

    // Basic Validation
    if (empty($full_name)) $errors[] = "Full Name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid Email is required.";
    if (!empty($password) && strlen($password) < 8) $errors[] = "New password must be at least 8 characters.";
    if (empty($class_id)) $errors[] = "Class is required.";
    if (empty($section_id)) $errors[] = "Section is required.";
    if (empty($admission_no)) $errors[] = "Admission Number is required.";
    if (empty($father_cnic)) $errors[] = "Father's CNIC is required.";

    // If it's a new parent, their name and phone are required
    if (empty($existing_parent_id)) {
        if (empty($father_name) || empty($father_phone)) $errors[] = "Father's Name and Phone are required for a new parent.";
    }

    // Check for unique email and admission number (excluding the current student)
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $student['user_id']]);
        if ($stmt->fetch()) $errors[] = "This email is already registered by another user.";

        $stmt = $db->prepare("SELECT id FROM students WHERE admission_no = ? AND branch_id = ? AND id != ?");
        $stmt->execute([$admission_no, $branch_id, $student_id]);
        if ($stmt->fetch()) $errors[] = "This Admission Number is already in use by another student.";
    }

    // Handle file upload
    $photo_db_path = $student['photo']; // Keep old photo by default
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['photo']['type'], $allowed_types)) {
            $errors[] = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
        } else {
            // Delete old photo if it exists
            if ($student['photo'] && file_exists('../../' . $student['photo'])) {
                unlink('../../' . $student['photo']);
            }

            $upload_dir = '../../assets/uploads/students/';
            $photo_name = uniqid() . '_' . basename($_FILES['photo']['name']);
            $photo_upload_path = $upload_dir . $photo_name;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $photo_upload_path)) {
                $photo_db_path = 'assets/uploads/students/' . $photo_name;
            } else {
                $errors[] = "Failed to upload the new photo.";
            }
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // 1. Update the user account
            $user_sql = "UPDATE users SET full_name = ?, email = ?, username = ?";
            $user_params = [$full_name, $email, $email];
            if (!empty($password)) {
                $user_sql .= ", password = ?";
                $user_params[] = password_hash($password, PASSWORD_DEFAULT);
            }
            $user_sql .= " WHERE id = ?";
            $user_params[] = $student['user_id'];
            $user_stmt = $db->prepare($user_sql);
            $user_stmt->execute($user_params);

            // 2. Update the student details record
            $student_stmt = $db->prepare(
                "UPDATE students SET admission_no=?, admission_date=?, dob=?, gender=?, cnic=?, mobile_no=?, photo=? WHERE id=?"
            );
            $student_stmt->execute([
                $admission_no,
                trim($_POST['admission_date']),
                empty($_POST['dob']) ? null : trim($_POST['dob']),
                $_POST['gender'], $student_cnic, trim($_POST['mobile_no']),
                $photo_db_path, $student_id
            ]);

            // 3. Update or Insert the enrollment record for this session.
            // This handles cases where a student might be edited but not yet enrolled in the current session.
            $enroll_stmt = $db->prepare(
                "INSERT INTO student_enrollments (student_id, session_id, class_id, section_id, roll_no)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE class_id = VALUES(class_id), section_id = VALUES(section_id), roll_no = VALUES(roll_no)"
            );

            $enroll_stmt->execute([$student_id, $session_id, $class_id, $section_id, $roll_no]);

            $db->commit();
            $_SESSION['success_message'] = "Student '{$full_name}' updated successfully!";
            redirect("manage_students.php");

        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Database Error: Could not update student. " . $e->getMessage();
        }
    }
}

require_once '../../header.php';
?>

<?php require_once '../../sidebar_branchadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="manage_students.php">Manage Students</a></li>
        <li class="breadcrumb-item active">Edit Student</li>
    </ol>

    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-user-edit me-1"></i> Edit Student Details</div>
        <div class="card-body">
            <form action="edit_student.php?id=<?php echo $student_id; ?>&session_id=<?php echo $session_id; ?>" method="POST" enctype="multipart/form-data" id="editStudentForm">
                <!-- Login Details -->
                <h5 class="text-primary">Login Details</h5><hr>
                <div class="row">
                    <div class="col-md-4 mb-3"><label>Full Name*</label><input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($student['full_name']); ?>" required></div>
                    <div class="col-md-4 mb-3"><label>Email* (for login)</label><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($student['email']); ?>" required></div>
                    <div class="col-md-4 mb-3"><label>New Password</label><input type="password" name="password" class="form-control"><small class="text-muted">Leave blank to keep current password.</small></div>
                </div>

                <!-- Academic Details -->
                <h5 class="text-primary mt-4">Academic Details</h5><hr>
                <div class="row">
                    <div class="col-md-3 mb-3"><label>Class*</label>
                        <select name="class_id" id="class_id" class="form-select" required>
                            <?php foreach ($classes as $class) echo "<option value='{$class['id']}' " . (($enrollment['class_id'] ?? 0) == $class['id'] ? 'selected' : '') . ">" . htmlspecialchars($class['name']) . "</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3"><label>Section*</label>
                        <select name="section_id" id="section_id" class="form-select" required>
                            <?php foreach ($sections as $section) echo "<option value='{$section['id']}' " . (($enrollment['section_id'] ?? 0) == $section['id'] ? 'selected' : '') . ">" . htmlspecialchars($section['name']) . "</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3"><label>Admission No*</label><input type="text" name="admission_no" class="form-control" value="<?php echo htmlspecialchars($student['admission_no']); ?>" required></div>
                    <div class="col-md-3 mb-3"><label>Roll No</label><input type="text" name="roll_no" class="form-control" value="<?php echo htmlspecialchars($enrollment['roll_no'] ?? ''); ?>"></div>
                </div>

                <!-- Personal Details -->
                <h5 class="text-primary mt-4">Personal & Parent Details</h5><hr>
                <div class="row">
                    <div class="col-md-3 mb-3"><label>Admission Date*</label><input type="date" name="admission_date" class="form-control" value="<?php echo htmlspecialchars($student['admission_date']); ?>"></div>
                    <div class="col-md-3 mb-3"><label>Date of Birth</label><input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($student['dob']); ?>"></div>
                    <div class="col-md-3 mb-3"><label>Gender</label><select name="gender" class="form-select"><option value="male" <?php echo $student['gender'] == 'male' ? 'selected' : ''; ?>>Male</option><option value="female" <?php echo $student['gender'] == 'female' ? 'selected' : ''; ?>>Female</option><option value="other" <?php echo $student['gender'] == 'other' ? 'selected' : ''; ?>>Other</option></select></div>
                    <div class="col-md-3 mb-3"><label>Mobile Number</label><input type="text" name="mobile_no" class="form-control" value="<?php echo htmlspecialchars($student['mobile_no']); ?>"></div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3"><label>B-Form / CNIC Number</label><input type="text" name="student_cnic" class="form-control" value="<?php echo htmlspecialchars($student['cnic'] ?? ''); ?>"></div>
                    <div class="col-md-3 mb-3"><label>Father's CNIC*</label><input type="text" name="father_cnic" class="form-control" value="<?php echo htmlspecialchars($student['father_cnic'] ?? ''); ?>" required></div>
                    <div class="col-md-3 mb-3"><label>Father's Name</label><input type="text" name="father_name" class="form-control" value="<?php echo htmlspecialchars($student['father_name'] ?? ''); ?>"></div>
                    <div class="col-md-3 mb-3"><label>Father's Phone</label><input type="text" name="father_phone" class="form-control" value="<?php echo htmlspecialchars($student['father_phone'] ?? ''); ?>"></div>
                </div>
                <div class="row align-items-center">
                    <div class="col-md-4 mb-3"><label>Update Photo (Optional)</label><input type="file" name="photo" class="form-control"></div>
                    <div class="col-md-2 mb-3">
                        <img src="<?php echo $student['photo'] ? BASE_URL . '/' . htmlspecialchars($student['photo']) : BASE_URL . '/assets/images/default_avatar.png'; ?>" alt="Current Photo" class="img-thumbnail" style="width: 60px; height: 60px;">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" name="update_student" class="btn btn-primary">Update Student</button>
                    <a href="manage_students.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('class_id').addEventListener('change', function() {
    var classId = this.value;
    var sectionSelect = document.getElementById('section_id');
    sectionSelect.innerHTML = '<option value="">Loading...</option>';

    if (!classId) {
        sectionSelect.innerHTML = '<option value="">-- Select Class First --</option>';
        return;
    }

    fetch(`<?php echo BASE_URL; ?>/api/get_sections.php?class_id=${classId}`)
        .then(response => response.json())
        .then(data => {
            sectionSelect.innerHTML = '<option value="">-- Select Section --</option>';
            data.forEach(section => {
                sectionSelect.innerHTML += `<option value="${section.id}">${section.name}</option>`;
            });
        })
        .catch(error => {
            console.error('Error fetching sections:', error);
            sectionSelect.innerHTML = '<option value="">-- Error Loading Sections --</option>';
        });
});
</script>

<?php require_once '../../footer.php'; ?>