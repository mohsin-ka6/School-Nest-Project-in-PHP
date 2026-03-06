<?php
// This is a public page, so we don't check for login roles.
$page_title = "Student Inquiry";
require_once '../../../config.php'; // Go up three levels
require_once '../../../functions.php';

$errors = [];
$student_data = null;

// Fetch all branches for the dropdown
try {
    $stmt_branches = $db->query("SELECT id, name FROM branches ORDER BY name ASC");
    $branches = $stmt_branches->fetchAll();
} catch (PDOException $e) {
    $errors[] = "Could not load school branches. Please contact support.";
    $branches = [];
}

/**
 * Finds a student based on provided criteria.
 * @param PDO $db The database connection object.
 * @param int $branch_id
 * @param string $admission_no
 * @param string|null $first_name Optional first name for initial form search.
 * @return array|false The student data or false if not found.
 */
function findStudent($db, $branch_id, $admission_no, $first_name = null) {
    $sql = "
        SELECT
            u.full_name, s.photo, p.father_name, c.name as class_name,
            sec.name as section_name, u.email, p.father_phone
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN parents p ON s.parent_id = p.id
        JOIN student_enrollments se ON s.id = se.student_id
        JOIN academic_sessions sess ON se.session_id = sess.id AND sess.is_current = 1 AND sess.branch_id = s.branch_id
        JOIN classes c ON se.class_id = c.id
        JOIN sections sec ON se.section_id = sec.id
        WHERE s.branch_id = :branch_id AND s.admission_no = :admission_no
    ";
    $params = [':branch_id' => $branch_id, ':admission_no' => $admission_no];

    if ($first_name !== null) {
        $sql .= " AND u.full_name LIKE :first_name";
        $params[':first_name'] = $first_name . '%';
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

// Handle direct link access (GET request with params)
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['branch_id']) && isset($_GET['admission_no'])) {
    $branch_id = (int)$_GET['branch_id'];
    $admission_no = trim($_GET['admission_no']);
    if ($branch_id && !empty($admission_no)) {
        $student_data = findStudent($db, $branch_id, $admission_no);
        if (!$student_data) {
            $errors[] = "No active student record found for the provided link. Please try searching manually.";
        }
    }
}
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $branch_id = isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : 0;
    $admission_no = isset($_POST['admission_no']) ? trim($_POST['admission_no']) : '';
    $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';

    // Validation
    if (empty($branch_id)) $errors[] = "Please select a branch.";
    if (empty($admission_no)) $errors[] = "Please enter an admission number.";
    if (empty($first_name)) $errors[] = "Please enter the student's first name(s).";

    if (empty($errors)) {
        $student_found = findStudent($db, $branch_id, $admission_no, $first_name);

        if ($student_found) {
            // Redirect to a clean, shareable URL using the PRG pattern
            redirect("student.php?branch_id={$branch_id}&admission_no={$admission_no}");
        } else {
            $errors[] = "No active student record found matching the provided details. Please check the information and try again.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>

<div class="container my-5" style="min-height: 75vh;">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-search me-2"></i>Student Inquiry</h4>
                </div>
                <div class="card-body p-4">

                    <?php if ($student_data): ?>
                        <div id="student-results">
                            <h4 class="text-success mb-3"><i class="fas fa-check-circle me-2"></i>Student Record Found</h4>
                            <div class="row align-items-center">
                                <div class="col-md-3 text-center">
                                    <img src="<?php echo $student_data['photo'] ? BASE_URL . '/' . htmlspecialchars($student_data['photo']) : BASE_URL . '/assets/images/default_avatar.png'; ?>" 
                                         class="img-thumbnail rounded-circle mb-3" 
                                         alt="Student Photo" 
                                         style="width: 120px; height: 120px; object-fit: cover;">
                                </div>
                                <div class="col-md-9">
                                    <table class="table table-bordered table-striped">
                                        <tbody>
                                            <tr>
                                                <th width="30%">Student Name</th>
                                                <td><?php echo htmlspecialchars($student_data['full_name']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Father's Name</th>
                                                <td><?php echo htmlspecialchars($student_data['father_name'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Current Class</th>
                                                <td><?php echo htmlspecialchars($student_data['class_name'] . ' - ' . $student_data['section_name']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Email Address</th>
                                                <td><?php echo htmlspecialchars($student_data['email']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Emergency Number</th>
                                                <td><?php echo htmlspecialchars($student_data['father_phone'] ?? 'N/A'); ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="text-center mt-4">
                                <a href="student.php" class="btn btn-secondary">Search Another Student</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Enter the student's details to find their current information.</p>
                        
                        <?php if (!empty($errors) && $_SERVER['REQUEST_METHOD'] == 'POST'): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error) echo "<p class='mb-0'>$error</p>"; ?>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST">
                            <div class="mb-3">
                                <label for="branch_id" class="form-label">School Branch*</label>
                                <select name="branch_id" id="branch_id" class="form-select" required>
                                    <option value="">-- Select Branch --</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo $branch['id']; ?>" <?php echo (isset($_POST['branch_id']) && $_POST['branch_id'] == $branch['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($branch['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="admission_no" class="form-label">Admission Number*</label>
                                    <input type="text" name="admission_no" id="admission_no" class="form-control" value="<?php echo htmlspecialchars($_POST['admission_no'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">Student's First Name(s)*</label>
                                    <input type="text" name="first_name" id="first_name" class="form-control" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                                    <div class="form-text">e.g., if name is "Malik Mohsin Abbas", enter "Malik" or "Malik Mohsin".</div>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Search Student</button>
                            </div>
                        </form>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<footer class="py-3 my-4">
    <p class="text-center text-muted">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All Rights Reserved.</p>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>

</body>
</html>

```

This single file provides the complete functionality you requested. You can now access it via `http://localhost/ai_school/public/student/inq/student.php` to test it out.

<!--
[PROMPT_SUGGESTION]Let's create a dashboard for the Teacher role.[/PROMPT_SUGGESTION]
[PROMPT_SUGGESTION]Can you create a page for the Super Admin to manage site-wide settings, like the school name and logo?[/PROMPT_SUGGESTION]
-->
