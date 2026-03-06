<?php
$page_title = "Staff Directory";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];

// For now, we will only manage teachers. This can be expanded later.
$role_to_manage = 'teacher';

// Handle form submission for adding a new teacher
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_teacher'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $dob = trim($_POST['dob']);

    $errors = [];
    if (empty($full_name)) $errors[] = "Full Name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid Email is required.";

    // Password Logic
    if (empty($password)) {
        if (empty($dob)) {
            $errors[] = "Password is required if Date of Birth is not provided.";
        } else {
            $password = date('dmY', strtotime($dob));
        }
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters if manually entered.";
    }

    // Check for unique email
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = "This email is already registered.";
    }
    
    // Handle photo upload
    $photo_db_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['photo']['type'], $allowed_types)) {
            $errors[] = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
        } else {
            $upload_dir = '../../assets/uploads/teachers/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $photo_name = uniqid() . '_' . basename($_FILES['photo']['name']);
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo_name)) {
                $photo_db_path = 'assets/uploads/teachers/' . $photo_name;
            } else {
                $errors[] = "Failed to upload photo.";
            }
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // 1. Create user account
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (branch_id, username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$branch_id, $email, $email, $hashed_password, $full_name, $role_to_manage]);
            $user_id = $db->lastInsertId();

            // 2. Create teacher details record
            $teacher_stmt = $db->prepare("INSERT INTO teachers (user_id, branch_id, dob, gender, cnic, joining_date, photo) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $teacher_stmt->execute([$user_id, $branch_id, empty($dob) ? null : $dob, $_POST['gender'], trim($_POST['cnic']), empty($_POST['joining_date']) ? null : $_POST['joining_date'], $photo_db_path]);

            $db->commit();
            $_SESSION['success_message'] = "Teacher '{$full_name}' added successfully!";
            redirect('staff_directory.php');
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch all teachers for this branch
$stmt = $db->prepare("
    SELECT u.id, u.full_name, u.email, u.status, t.photo 
    FROM users u 
    JOIN teachers t ON u.id = t.user_id
    WHERE u.branch_id = ? AND u.role = ? ORDER BY u.full_name ASC
");
$stmt->execute([$branch_id, $role_to_manage]);
$teachers = $stmt->fetchAll();

require_once '../../header.php';
?>

<?php require_once ROOT_PATH . '/sidebar_branchadmin.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">

    <?php if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $_SESSION['success_message'] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['success_message']); } ?>
    <?php if (!empty($errors)) { echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; } ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-chalkboard-teacher me-1"></i> Add New Teacher</div>
        <div class="card-body">
            <form action="" method="POST" enctype="multipart/form-data">
                <h5 class="text-primary">Personal & Login Details</h5><hr>
                <div class="row">
                    <div class="col-md-4 mb-3"><label>Full Name*</label><input type="text" name="full_name" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label>Email (for login)*</label><input type="email" name="email" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label>Password</label><input type="password" name="password" class="form-control"><small class="text-muted">Leave blank to use DOB (ddmmyyyy)</small></div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3"><label>Date of Birth*</label><input type="date" name="dob" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label>Gender</label><select name="gender" class="form-select"><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option></select></div>
                    <div class="col-md-4 mb-3"><label>CNIC</label><input type="text" name="cnic" class="form-control"></div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3"><label>Joining Date</label><input type="date" name="joining_date" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label>Photo (Optional)</label><input type="file" name="photo" class="form-control"></div>
                </div>
                <button type="submit" name="add_teacher" class="btn btn-primary">Add Teacher</button>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-table me-1"></i> Teacher List</div>
        <div class="card-body">
            <table class="table table-bordered align-middle">
                <thead><tr><th>Photo</th><th>Name</th><th>Email</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($teachers as $teacher): ?>
                    <tr>
                        <td><img src="<?php echo $teacher['photo'] ? BASE_URL . '/' . htmlspecialchars($teacher['photo']) : BASE_URL . '/assets/images/default_avatar.png'; ?>" alt="Photo" class="img-thumbnail" style="width: 50px; height: 50px;"></td>
                        <td><?php echo htmlspecialchars($teacher['full_name']); ?></td><td><?php echo htmlspecialchars($teacher['email']); ?></td><td><span class="badge bg-<?php echo $teacher['status'] == 'active' ? 'success' : 'danger'; ?>"><?php echo ucfirst($teacher['status']); ?></span></td>
                        <td>
                            <a href="view_staff.php?id=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-info" title="View"><i class="fas fa-eye"></i></a>
                            <a href="edit_staff.php?id=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-success" title="Edit"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($teachers)) echo '<tr><td colspan="5" class="text-center">No teachers found.</td></tr>'; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>