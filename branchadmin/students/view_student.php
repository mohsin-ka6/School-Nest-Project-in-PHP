<?php
require_once '../../config.php';
require_once '../../functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$user_id = $_SESSION['user_id'];
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$student_id) {
    $_SESSION['error_message'] = "Invalid student ID.";
    redirect('manage_students.php');
}

// First, get the current session for the branch
$stmt_session = $db->prepare("SELECT id FROM academic_sessions WHERE branch_id = ? AND is_current = 1");
$stmt_session->execute([$branch_id]);
$current_session_id = $stmt_session->fetchColumn();

// Fetch student data, including their enrollment in the CURRENT session
$sql = "
    SELECT 
        s.*, 
        u.full_name, u.email, u.username, u.status,
        p.father_name, p.father_phone, p.father_cnic, p.father_email,
        p.mother_name, p.mother_cnic, p.mother_phone, p.mother_email,
        se.roll_no,
        c.name as class_name,
        sec.name as section_name,
        sess.name as session_name
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN student_enrollments se ON s.id = se.student_id AND se.session_id = :session_id
    LEFT JOIN classes c ON se.class_id = c.id
    LEFT JOIN sections sec ON se.section_id = sec.id
    LEFT JOIN academic_sessions sess ON se.session_id = sess.id
    LEFT JOIN parents p ON s.parent_id = p.id
    WHERE s.id = :student_id AND s.branch_id = :branch_id
";

$stmt = $db->prepare($sql);
$stmt->execute([':student_id' => $student_id, ':branch_id' => $branch_id, ':session_id' => $current_session_id]);
$student = $stmt->fetch();

if (!$student) {
    $_SESSION['error_message'] = "Student not found.";
    redirect('manage_students.php');
}

// Handle sending login details
if (isset($_GET['action']) && $_GET['action'] == 'send_login_details') {
    if (empty($student['email'])) {
        $_SESSION['error_message'] = "Cannot send details. Student does not have an email address on record.";
    } elseif (empty($student['dob'])) {
        $_SESSION['error_message'] = "Cannot send details. Student's Date of Birth is missing, which is required for the default password.";
    } else {
        $default_password = date('dmY', strtotime($student['dob']));
        $login_url = BASE_URL . '/auth/login.php';

        // Create email body using HEREDOC for readability
        $email_body = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
    .container { max-width: 600px; margin: auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #ddd; }
    .header { background-color: #1d3557; color: #ffffff; padding: 20px; text-align: center; }
    .header h1 { margin: 0; font-size: 24px; }
    .content { padding: 30px; }
    .content h2 { color: #1d3557; }
    .details-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px; }
    .details-table th, .details-table td { padding: 10px; border: 1px solid #e1e5ee; text-align: left; }
    .details-table th { background-color: #f2f7ff; width: 35%; }
    .login-box { background-color: #e8f5fd; border-left: 4px solid #3498db; padding: 15px; margin-top: 20px; }
    .footer { background-color: #f4f4f4; color: #777; padding: 15px; text-align: center; font-size: 12px; }
</style>
</head>
<body>
<div class="container">
    <div class="header"><h1>Your Student Account Information</h1></div>
    <div class="content">
        <h2>Dear {$student['full_name']},</h2>
        <p>Here is a summary of your details and login credentials for the student portal at <strong>{$_SESSION['branch_name']}</strong>.</p>
        <h3>Student & Parent Details</h3>
        <table class="details-table">
            <tr><th>Admission No</th><td>{$student['admission_no']}</td></tr>
            <tr><th>Roll No</th><td>{$student['roll_no']}</td></tr>
            <tr><th>Admission Date</th><td>{$student['admission_date']}</td></tr>
            <tr><th>Date of Birth</th><td>{$student['dob']}</td></tr>
            <tr><th>Gender</th><td>{$student['gender']}</td></tr>
            <tr><th>Email</th><td>{$student['email']}</td></tr>
            <tr><th>Mobile Number</th><td>{$student['mobile_no']}</td></tr>
            <tr><th>Father's Name</th><td>{$student['father_name']}</td></tr>
            <tr><th>Father's Phone</th><td>{$student['father_phone']}</td></tr>
        </table>
        <div class="login-box">
            <h3>Your Portal Login Details</h3>
            <table class="details-table">
                <tr><th>Login URL</th><td><a href="{$login_url}">{$login_url}</a></td></tr>
                <tr><th>Username</th><td><strong>{$student['email']}</strong></td></tr>
                <tr><th>Password</th><td><strong>{$default_password}</strong></td></tr>
            </table>
            <p><strong>Note:</strong> It is highly recommended that you change this password after your first login.</p>
        </div>
    </div>
    <div class="footer"><p>&copy; " . date('Y') . " " . SITE_NAME . ". All Rights Reserved.</p></div>
</div>
</body>
</html>
HTML;

        send_email($student['email'], $student['full_name'], 'Your Student Portal Login Details - ' . SITE_NAME, $email_body);
    }
    redirect("view_student.php?id={$student_id}");
}

// Handle document upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_document'])) {
    $document_title = trim($_POST['document_title']);
    if (empty($document_title)) {
        $_SESSION['error_message'] = "Document title is required.";
    } elseif (isset($_FILES['student_document']) && $_FILES['student_document']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../../assets/uploads/student_documents/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // --- New Filename Logic ---
        // Sanitize a string to be filesystem-safe
        $sanitize = function($string) {
            $string = strtolower($string);
            $string = preg_replace('/[^a-z0-9_\-.]+/i', '_', $string); // Replace non-alphanumeric with underscores
            $string = preg_replace('/_+/', '_', $string); // Replace multiple underscores with a single one
            return trim($string, '_');
        };

        $file_extension = pathinfo($_FILES['student_document']['name'], PATHINFO_EXTENSION);
        $base_name = $sanitize($document_title) . '_' . 
                     $sanitize($student['full_name']) . '_' . 
                     $sanitize($student['class_name'] ?? 'class') . '_' . 
                     $sanitize($student['section_name'] ?? 'section') . '_' . 
                     $sanitize($student['session_name'] ?? 'session') . '_' . 
                     $sanitize($_SESSION['branch_name'] ?? 'branch');
        
        $file_name = $base_name . '_' . uniqid() . '.' . $file_extension; // Add uniqid to prevent overwrites
        $file_upload_path = $upload_dir . $file_name;
        $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
        $file_type = $_FILES['student_document']['type'];

        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['error_message'] = "Invalid file type. Only PDF, JPG, and PNG are allowed.";
        } elseif ($_FILES['student_document']['size'] > 5242880) { // 5MB limit
            $_SESSION['error_message'] = "File size exceeds the 5MB limit.";
        } elseif (move_uploaded_file($_FILES['student_document']['tmp_name'], $file_upload_path)) {
            $file_db_path = 'assets/uploads/student_documents/' . $file_name;
            try {
                $stmt_doc = $db->prepare("INSERT INTO student_documents (student_id, branch_id, document_title, file_path, file_type, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_doc->execute([$student_id, $branch_id, $document_title, $file_db_path, $file_type, $user_id]);
                $_SESSION['success_message'] = "Document uploaded successfully.";
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Database error: Could not save document record. " . $e->getMessage();
                unlink($file_upload_path); // Clean up uploaded file
            }
        } else {
            $_SESSION['error_message'] = "Failed to upload the document.";
        }
    } else {
        $_SESSION['error_message'] = "Please select a file to upload.";
    }
    redirect("view_student.php?id={$student_id}");
}

// Handle document deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete_doc' && isset($_GET['doc_id'])) {
    $doc_id = (int)$_GET['doc_id'];
    try {
        // First, get the file path to delete the file from server
        $stmt_get_doc = $db->prepare("SELECT file_path FROM student_documents WHERE id = ? AND student_id = ? AND branch_id = ?");
        $stmt_get_doc->execute([$doc_id, $student_id, $branch_id]);
        $doc_to_delete = $stmt_get_doc->fetch();

        if ($doc_to_delete) {
            // Delete file from server
            if (file_exists('../../' . $doc_to_delete['file_path'])) {
                unlink('../../' . $doc_to_delete['file_path']);
            }
            // Delete record from database
            $stmt_del = $db->prepare("DELETE FROM student_documents WHERE id = ?");
            $stmt_del->execute([$doc_id]);
            $_SESSION['success_message'] = "Document deleted successfully.";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error deleting document: " . $e->getMessage();
    }
    redirect("view_student.php?id={$student_id}");
}

// Fetch enrollment history
$stmt_history = $db->prepare("
    SELECT 
        se.roll_no,
        sess.name as session_name,
        sess.is_current,
        c.name as class_name,
        sec.name as section_name
    FROM student_enrollments se
    JOIN academic_sessions sess ON se.session_id = sess.id
    JOIN classes c ON se.class_id = c.id
    JOIN sections sec ON se.section_id = sec.id
    WHERE se.student_id = :student_id
    ORDER BY sess.start_date DESC
");
$stmt_history->execute([':student_id' => $student_id]);
$enrollment_history = $stmt_history->fetchAll();

// Fetch sibling details if a parent is linked
$siblings = [];
if ($student['parent_id']) {
    $stmt_siblings = $db->prepare("
        SELECT 
            s.id as sibling_id,
            s.photo as sibling_photo,
            u.full_name as sibling_name,
            c.name as sibling_class,
            sec.name as sibling_section
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN student_enrollments se ON s.id = se.student_id
        LEFT JOIN academic_sessions sess ON se.session_id = sess.id AND sess.is_current = 1
        LEFT JOIN classes c ON se.class_id = c.id
        LEFT JOIN sections sec ON se.section_id = sec.id
        WHERE s.parent_id = :parent_id AND s.id != :student_id AND s.branch_id = :branch_id
        GROUP BY s.id
        ORDER BY s.dob ASC
    ");
    $stmt_siblings->execute([':parent_id' => $student['parent_id'], ':student_id' => $student_id, ':branch_id' => $branch_id]);
    $siblings = $stmt_siblings->fetchAll();
}

// Fetch student documents
$stmt_docs = $db->prepare("SELECT * FROM student_documents WHERE student_id = ? ORDER BY uploaded_at DESC");
$stmt_docs->execute([$student_id]);
$documents = $stmt_docs->fetchAll();

// Fetch exam results history
$stmt_results = $db->prepare("
    SELECT DISTINCT
        et.id as exam_id,
        et.name as exam_name,
        sess.id as session_id,
        sess.name as session_name
    FROM exam_marks em
    JOIN exam_schedule es ON em.exam_schedule_id = es.id
    JOIN exam_types et ON es.exam_type_id = et.id
    JOIN academic_sessions sess ON em.session_id = sess.id
    WHERE em.student_id = :student_id AND em.branch_id = :branch_id
    ORDER BY sess.start_date DESC, et.name ASC
");
$stmt_results->execute([':student_id' => $student_id, ':branch_id' => $branch_id]);
$exam_results_list = $stmt_results->fetchAll();

// Group results by session for display
$results_by_session = [];
foreach ($exam_results_list as $result) {
    $results_by_session[$result['session_name']][] = $result;
}

$page_title = "Profile: " . htmlspecialchars($student['full_name']);

require_once '../../header.php';
?>

<?php require_once '../../sidebar_branchadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="manage_students.php">Manage Students</a></li>
        <li class="breadcrumb-item active">Student Profile</li>
    </ol>
    <?php display_flash_messages(); ?>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-user-graduate me-1"></i> Student Details</span>
            <div>
                <a href="view_student.php?id=<?php echo $student_id; ?>&action=send_login_details" class="btn btn-info btn-sm" onclick="return confirm('Are you sure you want to send login details to the student?');"><i class="fas fa-paper-plane me-1"></i> Send Login Details</a>
                <a href="print_student.php?id=<?php echo $student['id']; ?>" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-print me-1"></i> Print</a>
                <a href="edit_student.php?id=<?php echo $student['id']; ?>&session_id=<?php echo $current_session_id; ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit me-1"></i> Edit Profile</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Left Column for Photo -->
                <div class="col-md-3 text-center">
                    <img src="<?php echo $student['photo'] ? BASE_URL . '/' . htmlspecialchars($student['photo']) : BASE_URL . '/assets/images/default_avatar.png'; ?>" 
                         class="img-thumbnail rounded-circle mb-3" 
                         alt="Student Photo" 
                         style="width: 150px; height: 150px; object-fit: cover;">
                    <h4><?php echo htmlspecialchars($student['full_name']); ?></h4>
                    <?php if ($student['class_name']): ?>
                        <p class="text-muted"><?php echo htmlspecialchars($student['class_name'] . ' - ' . $student['section_name']); ?></p>
                        <span class="badge bg-success">Enrolled in Current Session</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">Not Enrolled in Current Session</span>
                    <?php endif; ?>
                </div>

                <!-- Right Column for Details -->
                <div class="col-md-9">
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">Profile</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">Enrollment History</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="results-tab" data-bs-toggle="tab" data-bs-target="#results" type="button" role="tab">Results</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="parent-tab" data-bs-toggle="tab" data-bs-target="#parent" type="button" role="tab">Parent Details</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="siblings-tab" data-bs-toggle="tab" data-bs-target="#siblings" type="button" role="tab">Siblings</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab">Documents</button>
                        </li>
                        <!-- Add other tabs like Fees, Attendance later -->
                    </ul>
                    <div class="tab-content py-3 px-2" id="myTabContent">
                        <div class="tab-pane fade show active" id="profile" role="tabpanel">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th width="30%">Admission No</th>
                                        <td width="70%"><?php echo htmlspecialchars($student['admission_no']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Roll No</th>
                                        <td><?php echo htmlspecialchars($student['roll_no'] ?? 'N/A'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Admission Date</th>
                                        <td><?php echo date('d M, Y', strtotime($student['admission_date'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Date of Birth</th>
                                        <td><?php echo $student['dob'] ? date('d M, Y', strtotime($student['dob'])) : 'N/A'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Gender</th>
                                        <td><?php echo ucfirst($student['gender']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email (Login)</th>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Mobile Number</th>
                                        <td><?php echo htmlspecialchars($student['mobile_no'] ?? 'N/A'); ?></td>
                                    </tr>
                                    <tr><td colspan="2" class="bg-light fw-bold">Parent Details</td></tr>
                                    <tr>
                                        <th>Father's Name</th>
                                        <td><?php echo htmlspecialchars($student['father_name'] ?? 'N/A'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Father's Phone</th>
                                        <td><?php echo htmlspecialchars($student['father_phone'] ?? 'N/A'); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="history" role="tabpanel">
                            <h5 class="mb-3">Student Enrollment History</h5>
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Academic Session</th>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>Roll No</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($enrollment_history)): ?>
                                        <tr><td colspan="4" class="text-center">No enrollment history found.</td></tr>
                                    <?php else: foreach ($enrollment_history as $enrollment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($enrollment['session_name']); ?>
                                                <?php if ($enrollment['is_current']): ?><span class="badge bg-success ms-2">Current</span><?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($enrollment['class_name']); ?></td>
                                            <td><?php echo htmlspecialchars($enrollment['section_name']); ?></td>
                                            <td><?php echo htmlspecialchars($enrollment['roll_no'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="results" role="tabpanel">
                            <h5 class="mb-3">Exam Results History</h5>
                            <?php if (empty($results_by_session)): ?>
                                <div class="alert alert-info">No exam results found for this student.</div>
                            <?php else: ?>
                                <div class="accordion" id="resultsAccordion">
                                    <?php $first = true; foreach ($results_by_session as $session_name => $exams): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading-<?php echo $exams[0]['session_id']; ?>">
                                            <button class="accordion-button <?php echo !$first ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $exams[0]['session_id']; ?>" aria-expanded="<?php echo $first ? 'true' : 'false'; ?>">
                                                <?php echo htmlspecialchars($session_name); ?>
                                            </button>
                                        </h2>
                                        <div id="collapse-<?php echo $exams[0]['session_id']; ?>" class="accordion-collapse collapse <?php echo $first ? 'show' : ''; ?>" data-bs-parent="#resultsAccordion">
                                            <div class="accordion-body">
                                                <ul class="list-group">
                                                    <?php foreach ($exams as $exam): ?>
                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                            <?php echo htmlspecialchars($exam['exam_name']); ?>
                                                            <a href="../exams/print_report_card.php?session_id=<?php echo $exam['session_id']; ?>&exam_id=<?php echo $exam['exam_id']; ?>&student_id=<?php echo $student_id; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-id-card me-1"></i> View Report Card
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <?php $first = false; endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="tab-pane fade" id="parent" role="tabpanel">
                            <h5 class="mb-3">Parent Information</h5>
                            <?php if ($student['parent_id']): ?>
                                <table class="table table-bordered">
                                    <tbody>
                                        <tr><td colspan="2" class="bg-light fw-bold">Father's Details</td></tr>
                                        <tr>
                                            <th width="30%">Father's Name</th>
                                            <td width="70%"><?php echo htmlspecialchars($student['father_name'] ?? 'N/A'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Father's Phone</th>
                                            <td><?php echo htmlspecialchars($student['father_phone'] ?? 'N/A'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Father's CNIC</th>
                                            <td><?php echo htmlspecialchars($student['father_cnic'] ?? 'N/A'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Father's Email</th>
                                            <td><?php echo htmlspecialchars($student['father_email'] ?? 'N/A'); ?></td>
                                        </tr>
                                        <tr><td colspan="2" class="bg-light fw-bold">Mother's Details</td></tr>
                                        <tr>
                                            <th>Mother's Name</th>
                                            <td><?php echo htmlspecialchars($student['mother_name'] ?? 'N/A'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Mother's Phone</th>
                                            <td><?php echo htmlspecialchars($student['mother_phone'] ?? 'N/A'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Mother's CNIC</th>
                                            <td><?php echo htmlspecialchars($student['mother_cnic'] ?? 'N/A'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Mother's Email</th>
                                            <td><?php echo htmlspecialchars($student['mother_email'] ?? 'N/A'); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="alert alert-info">No parent information is linked to this student.</div>
                            <?php endif; ?>
                        </div>
                        <div class="tab-pane fade" id="siblings" role="tabpanel">
                            <h5 class="mb-3">Sibling Information</h5>
                            <?php if (empty($siblings)): ?>
                                <div class="alert alert-info">No siblings found for this student in this branch.</div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($siblings as $sibling): ?>
                                    <div class="col-md-4 col-lg-3 mb-3 text-center">
                                        <a href="view_student.php?id=<?php echo $sibling['sibling_id']; ?>" class="text-decoration-none text-dark">
                                            <img src="<?php echo $sibling['sibling_photo'] ? BASE_URL . '/' . htmlspecialchars($sibling['sibling_photo']) : BASE_URL . '/assets/images/default_avatar.png'; ?>" class="img-thumbnail rounded-circle mb-2" style="width: 100px; height: 100px; object-fit: cover;">
                                            <h6><?php echo htmlspecialchars($sibling['sibling_name']); ?></h6>
                                            <p class="text-muted small"><?php echo htmlspecialchars($sibling['sibling_class'] . ' - ' . $sibling['sibling_section']); ?></p>
                                        </a>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="tab-pane fade" id="documents" role="tabpanel">
                            <h5 class="mb-3">Student Documents</h5>
                            <div class="card bg-light mb-4">
                                <div class="card-body">
                                    <h6 class="card-title">Upload New Document</h6>
                                    <form action="view_student.php?id=<?php echo $student_id; ?>" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-5 mb-2"><input type="text" name="document_title" class="form-control" placeholder="Document Title (e.g., Birth Certificate)" required></div>
                                            <div class="col-md-5 mb-2"><input type="file" name="student_document" class="form-control" required accept=".pdf,.jpg,.jpeg,.png"></div>
                                            <div class="col-md-2 mb-2 d-grid"><button type="submit" name="upload_document" class="btn btn-primary">Upload</button></div>
                                        </div>
                                        <div class="form-text">Allowed file types: PDF, JPG, PNG. Max size: 5MB.</div>
                                    </form>
                                </div>
                            </div>

                            <?php if (empty($documents)): ?>
                                <div class="alert alert-info">No documents have been uploaded for this student.</div>
                            <?php else: ?>
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Document Title</th>
                                            <th>Uploaded On</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($documents as $doc): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($doc['document_title']); ?></td>
                                            <td><?php echo date('d M, Y', strtotime($doc['uploaded_at'])); ?></td>
                                            <td>
                                                <a href="<?php echo BASE_URL . '/' . $doc['file_path']; ?>" target="_blank" class="btn btn-sm btn-success"><i class="fas fa-eye me-1"></i> View/Print</a>
                                                <a href="view_student.php?id=<?php echo $student_id; ?>&action=delete_doc&doc_id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this document? This cannot be undone.');"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>