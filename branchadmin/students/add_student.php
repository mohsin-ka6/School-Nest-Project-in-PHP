<?php
$page_title = "Add New Student";
require_once '../../config.php';
require_once '../../functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$user_id = $_SESSION['user_id'];
$errors = [];
$query_data = null;

// Check if we are converting an admission query
$query_id = isset($_GET['query_id']) ? (int)$_GET['query_id'] : 0;
if ($query_id) {
    $stmt_query = $db->prepare("SELECT * FROM admission_queries WHERE id = ? AND branch_id = ? AND status != 'enrolled'");
    $stmt_query->execute([$query_id, $branch_id]);
    $query_data = $stmt_query->fetch();
}

$source_query_id = isset($_POST['source_query_id']) ? (int)$_POST['source_query_id'] : 0;

// 1. Check for a current academic session for this branch
$stmt_session = $db->prepare("SELECT id FROM academic_sessions WHERE branch_id = ? AND is_current = 1");
$stmt_session->execute([$branch_id]);
$current_session = $stmt_session->fetch();

if (!$current_session) {
    $_SESSION['error_message'] = "No active academic session found for this branch. Please set a current session in the Super Admin panel before adding students.";
} else {
    $current_session_id = $current_session['id'];
}

// Fetch classes for dropdowns
$stmt_classes = $db->prepare("SELECT id, name FROM classes WHERE branch_id = ? ORDER BY numeric_name ASC, name ASC");
$stmt_classes->execute([$branch_id]);
$classes = $stmt_classes->fetchAll();

// Get the last admission number for the current branch to assist the user
$last_admission_no = null;
if ($branch_id) {
    // Using CAST to handle numeric admission numbers correctly even if they are stored as VARCHAR
    $stmt_last_adm = $db->prepare("SELECT MAX(CAST(admission_no AS UNSIGNED)) FROM students WHERE branch_id = ?");
    $stmt_last_adm->execute([$branch_id]);
    $last_admission_no = $stmt_last_adm->fetchColumn();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($current_session_id)) {
    // Sanitize and retrieve data
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $admission_no = trim($_POST['admission_no']);
    $admission_date = trim($_POST['admission_date']);
    $class_id = (int)$_POST['class_id'];
    $section_id = (int)$_POST['section_id'];
    $roll_no = trim($_POST['roll_no']);
    $gender = trim($_POST['gender']);
    $dob = trim($_POST['dob']);
    $mobile_no = trim($_POST['mobile_no']);
    $student_cnic = trim($_POST['student_cnic']);

    // Parent fields
    $father_cnic = trim($_POST['father_cnic']);
    $existing_parent_id = (int)($_POST['existing_parent_id'] ?? 0);
    $father_name = trim($_POST['father_name']);
    $father_phone = trim($_POST['father_phone']);
    $father_email = trim($_POST['father_email']);

    // Validation
    if (empty($full_name)) $errors[] = "Full Name is required.";
    if (empty($email)) $errors[] = "Email is required.";
    if (empty($dob)) $errors[] = "Date of Birth is required to generate the default password.";
    if (empty($admission_no)) $errors[] = "Admission Number is required.";
    if (empty($admission_date)) $errors[] = "Admission Date is required.";
    if (empty($class_id)) $errors[] = "Class is required.";
    if (empty($section_id)) $errors[] = "Section is required.";
    if (empty($father_cnic)) $errors[] = "Father's CNIC is required.";

    // If it's a new parent, their name and phone are required
    if (empty($existing_parent_id)) {
        if (empty($father_name) || empty($father_phone)) $errors[] = "Father's Name and Phone are required for a new parent.";
    }

    // Check for uniqueness if there are no other errors yet
    if (empty($errors)) {
        // Check student's email
        $stmt_check = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt_check->execute([$email]);
        if ($stmt_check->fetchColumn() > 0) $errors[] = "The student's email address is already in use.";

        // Check parent's email only if it's a new parent and an email is provided
        if (empty($existing_parent_id) && !empty($father_email)) {
            if ($father_email === $email) {
                $errors[] = "Parent's email cannot be the same as the student's email.";
            } else {
                $stmt_check->execute([$father_email]);
                if ($stmt_check->fetchColumn() > 0) $errors[] = "The parent's email address is already in use.";
            }
        }

        $stmt_check = $db->prepare("SELECT COUNT(*) FROM students WHERE admission_no = ? AND branch_id = ?");
        $stmt_check->execute([$admission_no, $branch_id]);
        if ($stmt_check->fetchColumn() > 0) $errors[] = "This admission number is already in use for this branch.";
    }
    // Handle file upload
    $photo_db_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../../assets/uploads/students/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $photo_name = uniqid() . '_' . basename($_FILES['photo']['name']);
        $photo_upload_path = $upload_dir . $photo_name;
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

        if (!in_array($_FILES['photo']['type'], $allowed_types)) {
            $errors[] = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
        } elseif ($_FILES['photo']['size'] > 2097152) { // 2MB limit
            $errors[] = "File size exceeds the 2MB limit.";
        } elseif (move_uploaded_file($_FILES['photo']['tmp_name'], $photo_upload_path)) {
            $photo_db_path = 'assets/uploads/students/' . $photo_name;
        } else {
            $errors[] = "Failed to upload the photo.";
        }
    }

    if (empty($errors)) {
        $db->beginTransaction();
        try {
            $parent_id_to_link = $existing_parent_id;

            // If a CNIC is provided but no existing parent is linked, create a new parent
            if (!empty($father_cnic) && empty($existing_parent_id)) {
                // Create parent user account
                $parent_username = $father_phone;
                $parent_password = password_hash($father_phone, PASSWORD_DEFAULT);
                if (empty($parent_email)) {
                    $parent_email = uniqid() . '_' . $parent_username . '@school.local'; // Unique placeholder email
                }
                $parent_user_stmt = $db->prepare("INSERT INTO users (branch_id, username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?, 'parent')");
                $parent_user_stmt->execute([$branch_id, $parent_username, $parent_email, $parent_password, $father_name]);
                $parent_user_id = $db->lastInsertId();

                // Create parent details record
                $parent_details_stmt = $db->prepare(
                    "INSERT INTO parents (user_id, branch_id, father_name, father_phone, father_cnic, father_email, mother_name, mother_cnic, mother_phone, mother_email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $parent_details_stmt->execute([
                    $parent_user_id, $branch_id, $father_name, $father_phone, $father_cnic,
                    $father_email, trim($_POST['mother_name']), trim($_POST['mother_cnic']), trim($_POST['mother_phone']), trim($_POST['mother_email'])
                ]);
                $parent_id_to_link = $db->lastInsertId();
            }

            // 1. Create User
            $default_password = date('dmY', strtotime($dob));
            $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
            $username = strtolower(str_replace(' ', '', $full_name)) . rand(10, 99); // Simple unique username
            $stmt_user = $db->prepare("INSERT INTO users (branch_id, username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?, 'student')");
            $stmt_user->execute([$branch_id, $username, $email, $hashed_password, $full_name]);
            $new_user_id = $db->lastInsertId();

            // 2. Create Student
            $stmt_student = $db->prepare("INSERT INTO students (user_id, branch_id, parent_id, admission_no, admission_date, dob, gender, cnic, mobile_no, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_student->execute([$new_user_id, $branch_id, $parent_id_to_link, $admission_no, $admission_date, $dob, $gender, $student_cnic, $mobile_no, $photo_db_path]);
            $new_student_id = $db->lastInsertId();

            // 3. Create Enrollment in current session
            $stmt_enroll = $db->prepare("INSERT INTO student_enrollments (session_id, student_id, class_id, section_id, roll_no) VALUES (?, ?, ?, ?, ?)");
            $stmt_enroll->execute([$current_session_id, $new_student_id, $class_id, $section_id, $roll_no]);

            // 4. If this admission came from a query, update the query status
            if ($source_query_id > 0) {
                $stmt_update_query = $db->prepare("UPDATE admission_queries SET status = 'enrolled' WHERE id = ? AND branch_id = ?");
                $stmt_update_query->execute([$source_query_id, $branch_id]);
            }

            $db->commit();

            // After commit, send welcome email
            $stmt_class_name = $db->prepare("SELECT name FROM classes WHERE id = ?");
            $stmt_class_name->execute([$class_id]);
            $class_name = $stmt_class_name->fetchColumn();

            $stmt_section_name = $db->prepare("SELECT name FROM sections WHERE id = ?");
            $stmt_section_name->execute([$section_id]);
            $section_name = $stmt_section_name->fetchColumn();

            $mail = new PHPMailer(true);
            try {
                //Server settings
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USER;
                $mail->Password   = SMTP_PASS;
                $mail->SMTPSecure = SMTP_SECURE;
                $mail->Port       = SMTP_PORT;

                //Recipients
                $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                $mail->addAddress($email, $full_name);

                //Content
                $mail->isHTML(true);
                $mail->Subject = 'Welcome to ' . SITE_NAME;
                $mail->Body    = "Dear {$full_name},<br><br>Welcome to " . SITE_NAME . "! We are thrilled to have you join us.<br><br>Your admission details are as follows:<br><b>Class:</b> {$class_name}<br><b>Section:</b> {$section_name}<br><br>You can now access the student portal using the following credentials:<br><b>Login URL:</b> <a href='" . BASE_URL . "/auth/login.php'>" . BASE_URL . "/auth/login.php</a><br><b>Username:</b> {$email}<br><b>Password:</b> {$default_password}<br><br>We strongly recommend that you change your password after your first login.<br><br>Best regards,<br>The " . SITE_NAME . " Team";

                $mail->send();
                $_SESSION['success_message'] = "Student added successfully! A welcome email has been sent.";
            } catch (Exception $e) {
                // If email fails, don't block the process. Just add a note to the success message.
                $_SESSION['success_message'] = "Student added successfully, but the welcome email could not be sent. Please check mailer configuration.";
            }

            redirect('manage_students.php');

        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Database Error: Could not add student. " . $e->getMessage();
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
        <li class="breadcrumb-item"><a href="manage_students.php">Students</a></li>
        <li class="breadcrumb-item active">Add Student</li>
    </ol>

    <?php display_flash_messages(); ?>
    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <?php if (!isset($current_session_id)): ?>
        <div class="alert alert-warning">
            <strong>Action Required:</strong> The form is disabled because no active academic session is set for your branch. A Super Admin must set a current session to enable student admissions.
        </div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" id="addStudentForm">
        <?php if ($query_data): ?>
            <input type="hidden" name="source_query_id" value="<?php echo $query_data['id']; ?>">
        <?php endif; ?>
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-user-graduate me-1"></i> Academic Information</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="admission_no" class="form-label">Admission No*</label>
                        <input type="text" class="form-control" id="admission_no" name="admission_no" required <?php if (!isset($current_session_id)) echo 'disabled'; ?>>
                        <?php if ($last_admission_no): ?>
                            <div class="form-text">Last admission no was: <strong><?php echo htmlspecialchars($last_admission_no); ?></strong></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="admission_date" class="form-label">Admission Date*</label>
                        <input type="date" class="form-control" id="admission_date" name="admission_date" required value="<?php echo date('Y-m-d'); ?>" <?php if (!isset($current_session_id)) echo 'disabled'; ?>>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="class_id" class="form-label">Class*</label>
                        <select class="form-select" id="class_id" name="class_id" required <?php if (!isset($current_session_id)) echo 'disabled'; ?>>
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $class) echo "<option value='{$class['id']}'>" . htmlspecialchars($class['name']) . "</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="section_id" class="form-label">Section*</label>
                        <select class="form-select" id="section_id" name="section_id" required <?php if (!isset($current_session_id)) echo 'disabled'; ?>>
                            <option value="">-- Select Class First --</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="roll_no" class="form-label">Roll No</label>
                        <input type="text" class="form-control" id="roll_no" name="roll_no" <?php if (!isset($current_session_id)) echo 'disabled'; ?>>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-user-circle me-1"></i> Student Information</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="full_name" class="form-label">Full Name*</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($query_data['student_name'] ?? ''); ?>" required <?php if (!isset($current_session_id)) echo 'disabled'; ?>>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="gender" class="form-label">Gender</label>
                        <select class="form-select" id="gender" name="gender" <?php if (!isset($current_session_id)) echo 'disabled'; ?>>
                            <option value="">-- Select Gender --</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="dob" class="form-label">Date of Birth*</label>
                        <input type="date" class="form-control" id="dob" name="dob" <?php if (!isset($current_session_id)) echo 'disabled'; ?>>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="mobile_no" class="form-label">Mobile Number</label>
                        <input type="text" class="form-control" id="mobile_no" name="mobile_no" <?php if (!isset($current_session_id)) echo 'disabled'; ?>>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="student_cnic" class="form-label">B-Form / CNIC Number</label>
                        <input type="text" class="form-control" id="student_cnic" name="student_cnic" <?php if (!isset($current_session_id)) echo 'disabled'; ?>>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="photo" class="form-label">Student Photo (Optional)</label>
                        <input type="file" class="form-control" id="photo" name="photo" <?php if (!isset($current_session_id)) echo 'disabled'; ?>>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-user-friends me-1"></i> Parent Information</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="father_cnic" class="form-label">Father's CNIC*</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="father_cnic" name="father_cnic" placeholder="e.g., 35202-1234567-1" required <?php if (!isset($current_session_id)) echo 'disabled'; ?>>
                            <button class="btn btn-secondary" type="button" id="search_parent_btn" <?php if (!isset($current_session_id)) echo 'disabled'; ?>>Search</button>
                        </div>
                        <div id="parent_status_msg" class="form-text"></div>
                    </div>
                </div>

                <div id="parent_details_container" style="display: none;">
                    <input type="hidden" name="existing_parent_id" id="existing_parent_id" value="0">
                    <h6 class="text-primary">Father's Details</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Father's Name*</label><input type="text" name="father_name" id="father_name" class="form-control" value="<?php echo htmlspecialchars($query_data['contact_person'] ?? ''); ?>"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Father's Mobile* (for login)</label><input type="text" name="father_phone" id="father_phone" class="form-control" value="<?php echo htmlspecialchars($query_data['contact_phone'] ?? ''); ?>"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Father's Email (Optional)</label><input type="email" name="father_email" id="father_email" class="form-control" value="<?php echo htmlspecialchars($query_data['contact_email'] ?? ''); ?>"></div>
                    </div>
                    <h6 class="text-primary mt-3">Mother's Details (Optional)</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Mother's Name</label><input type="text" name="mother_name" id="mother_name" class="form-control"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Mother's CNIC</label><input type="text" name="mother_cnic" id="mother_cnic" class="form-control"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Mother's Mobile</label><input type="text" name="mother_phone" id="mother_phone" class="form-control"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Mother's Email (Optional)</label><input type="email" name="mother_email" id="mother_email" class="form-control"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-lock me-1"></i> Login Information</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email*</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($query_data['contact_email'] ?? ''); ?>" required <?php if (!isset($current_session_id)) echo 'disabled'; ?>>
                        <div class="form-text">This will be used for login and communication.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <button type="submit" class="btn btn-primary btn-lg" <?php if (!isset($current_session_id)) echo 'disabled'; ?>>Add Student</button>
            <a href="manage_students.php" class="btn btn-secondary btn-lg">Cancel</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // If query data is present, automatically trigger the parent search
    if (<?php echo $query_id ? 'true' : 'false'; ?>) {
        document.getElementById('search_parent_btn').click();
    }

    const classSelect = document.getElementById('class_id');
    const sectionSelect = document.getElementById('section_id');

    classSelect.addEventListener('change', function() {
        const classId = this.value;
        sectionSelect.innerHTML = '<option value="">Loading...</option>';

        if (!classId) {
            sectionSelect.innerHTML = '<option value="">-- Select Class First --</option>';
            return;
        }

        fetch(`<?php echo BASE_URL; ?>/api/get_sections.php?class_id=${classId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    sectionSelect.innerHTML = `<option value="">Error: ${data.error}</option>`;
                    return;
                }
                let options = '<option value="">-- Select Section --</option>';
                data.forEach(section => {
                    options += `<option value="${section.id}">${section.name}</option>`;
                });
                sectionSelect.innerHTML = options;
            })
            .catch(error => {
                console.error('Error fetching sections:', error);
                sectionSelect.innerHTML = '<option value="">-- Error Loading --</option>';
            });
    });

    // Parent Search Logic
    const searchParentBtn = document.getElementById('search_parent_btn');
    const fatherCnicInput = document.getElementById('father_cnic');
    const parentDetailsContainer = document.getElementById('parent_details_container');
    const parentStatusMsg = document.getElementById('parent_status_msg');
    const existingParentId = document.getElementById('existing_parent_id');

    searchParentBtn.addEventListener('click', function() {
        const cnic = fatherCnicInput.value.trim();
        if (cnic.length < 13) {
            parentStatusMsg.innerHTML = '<span class="text-danger">Please enter a valid CNIC.</span>';
            return;
        }

        parentStatusMsg.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
        parentDetailsContainer.style.display = 'none';

        fetch(`<?php echo BASE_URL; ?>/api/get_parent_by_cnic.php?cnic=${cnic}`)
            .then(response => response.json())
            .then(data => {
                parentDetailsContainer.style.display = 'block';
                const fields = ['father_name', 'father_phone', 'father_email', 'mother_name', 'mother_cnic', 'mother_phone', 'mother_email'];

                if (data) { // Parent exists
                    parentStatusMsg.innerHTML = '<span class="text-success">Existing parent found. Details have been filled.</span>';
                    existingParentId.value = data.id;
                    
                    fields.forEach(field => {
                        const el = document.getElementById(field);
                        if(el) {
                            el.value = data[field] || '';
                            el.readOnly = true;
                        }
                    });
                } else { // New parent
                    parentStatusMsg.innerHTML = '<span class="text-info">New parent. Please fill in the details below.</span>';
                    existingParentId.value = '0';

                    fields.forEach(field => {
                        const el = document.getElementById(field);
                        if(el) {
                            el.value = '';
                            el.readOnly = false;
                        }
                    });
                }
            }).catch(error => {
                parentStatusMsg.innerHTML = '<span class="text-danger">An error occurred while searching.</span>';
            });
    });
});
</script>

<?php require_once '../../footer.php'; ?>