<?php
$page_title = "View Complaint";
require_once dirname(__DIR__, 2) . '/config.php';
require_once ROOT_PATH . '/functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$complaint_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$complaint_id) {
    $_SESSION['error_message'] = "Invalid complaint ID.";
    redirect('complaints.php');
}

// Fetch the main complaint details
$stmt = $db->prepare("SELECT * FROM complaints WHERE id = ? AND branch_id = ?");
$stmt->execute([$complaint_id, $branch_id]);
$complaint = $stmt->fetch();

if (!$complaint) {
    $_SESSION['error_message'] = "Complaint not found.";
    redirect('complaints.php');
}

// Fetch related names based on the complaint source
$source_details = [];
if ($complaint['complaint_source'] === 'student' && !empty($complaint['source_student_ids'])) {
    $student_ids = explode(',', $complaint['source_student_ids']);
    $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
    $stmt_students = $db->prepare("
        SELECT u.full_name 
        FROM students s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.id IN ($placeholders)
    ");
    $stmt_students->execute($student_ids);
    $students = $stmt_students->fetchAll(PDO::FETCH_COLUMN);
    $source_details['Students Involved'] = implode(', ', $students);
} elseif ($complaint['complaint_source'] === 'teacher' && !empty($complaint['source_person_id'])) {
    $stmt_teacher = $db->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt_teacher->execute([$complaint['source_person_id']]);
    $source_details['Staff Name'] = $stmt_teacher->fetchColumn();
} elseif ($complaint['complaint_source'] === 'parent' && !empty($complaint['source_person_id'])) {
    $stmt_parent = $db->prepare("SELECT father_name FROM parents WHERE id = ?");
    $stmt_parent->execute([$complaint['source_person_id']]);
    $source_details['Parent Name'] = $stmt_parent->fetchColumn();
}

require_once ROOT_PATH . '/header.php';
?>

<?php require_once ROOT_PATH . '/sidebar_branchadmin.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">
    <!-- This header is only for the screen -->
    <div class="d-print-none">
        <h1 class="mt-4">Complaint Details</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="complaints.php">Complaints</a></li>
            <li class="breadcrumb-item active">View Complaint</li>
        </ol>
    </div>

    <!-- This header is only for printing -->
    <div class="d-none d-print-block mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <?php if (SITE_LOGO && file_exists(ROOT_PATH . '/' . SITE_LOGO)): ?>
                    <img src="<?php echo BASE_URL . '/' . SITE_LOGO; ?>" alt="Logo" style="max-height: 50px;">
                <?php endif; ?>
                <h2 class="d-inline-block align-middle ms-2 mb-0"><?php echo SITE_NAME; ?></h2>
            </div>
            <h3 class="mb-0">Complaint Report</h3>
        </div>
        <hr>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-file-alt me-1"></i> Complaint #<?php echo htmlspecialchars($complaint['complaint_no']); ?></span>
            <div>
                <button onclick="window.print()" class="btn btn-sm btn-secondary"><i class="fas fa-print me-1"></i> Print</button>
                <a href="complaints.php?action=edit&id=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit me-1"></i> Edit</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Complaint Information</h5>
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th width="40%">Complaint No</th>
                                <td><?php echo htmlspecialchars($complaint['complaint_no']); ?></td>
                            </tr>
                            <tr>
                                <th>Complaint Date</th>
                                <td><?php echo date('d M, Y', strtotime($complaint['complaint_date'])); ?></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <?php 
                                    $status_class = ['pending' => 'warning', 'in_progress' => 'info', 'resolved' => 'success'];
                                    echo '<span class="badge bg-' . ($status_class[$complaint['status']] ?? 'light') . '">' . ucfirst(str_replace('_', ' ', $complaint['status'])) . '</span>';
                                    ?>
                                </td>
                            </tr>
                             <tr>
                                <th>Complaint Type</th>
                                <td><?php echo htmlspecialchars($complaint['complaint_type'] ?? 'N/A'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5>Source Details</h5>
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th width="40%">Complaint Source</th>
                                <td><?php echo ucfirst($complaint['complaint_source']); ?></td>
                            </tr>
                            <tr>
                                <th>Complaint By</th>
                                <td><?php echo htmlspecialchars($complaint['complaint_by']); ?></td>
                            </tr>
                            <tr>
                                <th>Phone</th>
                                <td><?php echo htmlspecialchars($complaint['phone'] ?? 'N/A'); ?></td>
                            </tr>
                            <?php foreach ($source_details as $label => $value): ?>
                            <tr>
                                <th><?php echo $label; ?></th>
                                <td><?php echo htmlspecialchars($value); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                <h5>Complaint Body</h5>
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th width="20%">Description</th>
                            <td><?php echo nl2br(htmlspecialchars($complaint['description'])); ?></td>
                        </tr>
                        <tr>
                            <th>Action Taken</th>
                            <td><?php echo nl2br(htmlspecialchars($complaint['action_taken'] ?? 'N/A')); ?></td>
                        </tr>
                        <tr>
                            <th>Notes</th>
                            <td><?php echo nl2br(htmlspecialchars($complaint['notes'] ?? 'N/A')); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/footer.php'; ?>

<style>
@media print {
    body {
        background-color: #fff !important;
        font-size: 12pt;
    }
    .sidebar, .navbar, .breadcrumb, .card-header .btn, .content-header, footer, .d-print-none {
        display: none !important;
    }
    .container-fluid, .card-body {
        padding: 0 !important;
        margin: 0 !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .col-md-6 {
        width: 50%;
        float: left;
    }
    .table-bordered {
        border: 1px solid #dee2e6 !important;
    }
    .table-bordered th, .table-bordered td {
        border: 1px solid #dee2e6 !important;
    }
    .badge {
        border: 1px solid #6c757d;
        color: #000;
        background-color: #fff !important;
    }
    a {
        text-decoration: none;
        color: #000;
    }
}
</style>
