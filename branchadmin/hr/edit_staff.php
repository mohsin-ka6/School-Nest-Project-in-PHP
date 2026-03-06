<?php
$page_title = "Edit Staff";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$staff_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

if (!$staff_id) {
    $_SESSION['error_message'] = "Invalid staff ID.";
    redirect('staff_directory.php');
}

// Fetch staff data (for now, only teachers)
$stmt = $db->prepare("
    SELECT u.*, t.dob, t.gender, t.cnic, t.joining_date, t.photo, t.incharge_class_id, t.incharge_section_id
    FROM users u 
    JOIN teachers t ON u.id = t.user_id
    WHERE u.id = ? AND u.branch_id = ? AND u.role = 'teacher'
");
$stmt->execute([$staff_id, $branch_id]);
$staff = $stmt->fetch();

if (!$staff) {
    $_SESSION['error_message'] = "Staff member not found.";
    redirect('staff_directory.php');
}

// Fetch classes and sections for "Class Incharge" dropdowns
$stmt_classes = $db->prepare("SELECT id, name FROM classes WHERE branch_id = ? ORDER BY name");
$stmt_classes->execute([$branch_id]);
$classes = $stmt_classes->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_staff'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $status = $_POST['status'];
    $dob = trim($_POST['dob']);
    $cnic = trim($_POST['cnic']);
    $joining_date = trim($_POST['joining_date']);
    $gender = $_POST['gender'];
    $incharge_class_id = (int)$_POST['incharge_class_id'] ?: null;
    $incharge_section_id = (int)$_POST['incharge_section_id'] ?: null;

    if (empty($full_name)) $errors[] = "Full Name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid Email is required.";
    if (!in_array($status, ['active', 'inactive'])) $errors[] = "Invalid status.";
    if (!empty($password) && strlen($password) < 8) $errors[] = "New password must be at least 8 characters long.";

    // Check for unique email (excluding current staff member)
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $staff_id]);
        if ($stmt->fetch()) $errors[] = "This email is already registered by another user.";
    }

    // Handle photo upload
    $photo_db_path = $staff['photo'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['photo']['type'], $allowed_types)) {
            $errors[] = "Invalid file type for photo.";
        } else {
            if ($staff['photo'] && file_exists('../../' . $staff['photo'])) {
                unlink('../../' . $staff['photo']);
            }
            $upload_dir = '../../assets/uploads/teachers/';
            $photo_name = uniqid() . '_' . basename($_FILES['photo']['name']);
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo_name)) {
                $photo_db_path = 'assets/uploads/teachers/' . $photo_name;
            } else {
                $errors[] = "Failed to upload new photo.";
            }
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $sql = "UPDATE users SET full_name = ?, email = ?, username = ?, status = ?";
            $params = [$full_name, $email, $email, $status];

            if (!empty($password)) {
                $sql .= ", password = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id = ?";
            $params[] = $staff_id;
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            $teacher_stmt = $db->prepare("UPDATE teachers SET dob=?, gender=?, cnic=?, joining_date=?, photo=?, incharge_class_id=?, incharge_section_id=? WHERE user_id=?");
            $teacher_stmt->execute([
                empty($dob) ? null : $dob, $gender, $cnic, empty($joining_date) ? null : $joining_date, $photo_db_path, $incharge_class_id, $incharge_section_id, $staff_id
            ]);

            $db->commit();
            $_SESSION['success_message'] = "Staff member '{$full_name}' updated successfully!";
            redirect('staff_directory.php');
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

require_once '../../header.php';
?>

<?php require_once ROOT_PATH . '/sidebar_branchadmin.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">

    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-user-edit me-1"></i> Edit Teacher Details</div>
        <div class="card-body">
            <form action="edit_staff.php?id=<?php echo $staff_id; ?>" method="POST" enctype="multipart/form-data">
                <h5 class="text-primary">Personal & Login Details</h5><hr>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="full_name" class="form-label">Full Name*</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($staff['full_name']); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="email" class="form-label">Email (for login)*</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($staff['email']); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" id="password" name="password" class="form-control"><small class="text-muted">Leave blank to keep current.</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3"><label>Date of Birth</label><input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($staff['dob']); ?>"></div>
                    <div class="col-md-4 mb-3"><label>Gender</label><select name="gender" class="form-select"><option value="male" <?php echo ($staff['gender'] == 'male') ? 'selected' : ''; ?>>Male</option><option value="female" <?php echo ($staff['gender'] == 'female') ? 'selected' : ''; ?>>Female</option><option value="other" <?php echo ($staff['gender'] == 'other') ? 'selected' : ''; ?>>Other</option></select></div>
                    <div class="col-md-4 mb-3"><label>CNIC</label><input type="text" name="cnic" class="form-control" value="<?php echo htmlspecialchars($staff['cnic']); ?>"></div>
                </div>

                <h5 class="text-primary mt-3">Official Details</h5><hr>
                <div class="row">
                    <div class="col-md-4 mb-3"><label>Joining Date</label><input type="date" name="joining_date" class="form-control" value="<?php echo htmlspecialchars($staff['joining_date']); ?>"></div>
                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label">Status*</label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="active" <?php echo $staff['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $staff['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3"><label>Class Incharge (Optional)</label>
                        <select name="incharge_class_id" id="class_id" class="form-select">
                            <option value="">-- None --</option>
                            <?php foreach($classes as $class) echo "<option value='{$class['id']}' ".($staff['incharge_class_id'] == $class['id'] ? 'selected' : '').">".htmlspecialchars($class['name'])."</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3"><label>Section Incharge (Optional)</label><select name="incharge_section_id" id="section_id" class="form-select"><option value="">-- Select Class First --</option></select></div>
                </div>
                <div class="row align-items-center">
                    <div class="col-md-4 mb-3"><label>Update Photo</label><input type="file" name="photo" class="form-control"></div>
                    <div class="col-md-2 mb-3"><img src="<?php echo $staff['photo'] ? BASE_URL . '/' . htmlspecialchars($staff['photo']) : BASE_URL . '/assets/images/default_avatar.png'; ?>" alt="Photo" class="img-thumbnail" style="width: 60px; height: 60px;"></div>
                </div>
                <button type="submit" name="update_staff" class="btn btn-primary">Update Staff</button>
                <a href="staff_directory.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('class_id');
    const sectionSelect = document.getElementById('section_id');
    const inchargeSectionId = '<?php echo $staff['incharge_section_id']; ?>';

    function fetchSections(classId, targetSelect, selectedId) {
        if (!classId) {
            targetSelect.innerHTML = '<option value="">-- Select Class First --</option>';
            return;
        }
        fetch(`<?php echo BASE_URL; ?>/api/get_sections.php?class_id=${classId}`)
            .then(response => response.json())
            .then(data => {
                targetSelect.innerHTML = '<option value="">-- None --</option>';
                data.forEach(section => {
                    const selected = section.id == selectedId ? 'selected' : '';
                    targetSelect.innerHTML += `<option value="${section.id}" ${selected}>${section.name}</option>`;
                });
            });
    }
    classSelect.addEventListener('change', () => fetchSections(classSelect.value, sectionSelect, null));
    if (classSelect.value) fetchSections(classSelect.value, sectionSelect, inchargeSectionId);
});
</script>