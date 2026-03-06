<?php
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$staff_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$staff_id) {
    $_SESSION['error_message'] = "Invalid staff ID.";
    redirect('staff_directory.php');
}

// Fetch staff data
$sql = "
    SELECT 
        u.full_name, u.email, u.status,
        t.*,
        c.name as class_incharge_name,
        s.name as section_incharge_name
    FROM users u
    JOIN teachers t ON u.id = t.user_id
    LEFT JOIN classes c ON t.incharge_class_id = c.id
    LEFT JOIN sections s ON t.incharge_section_id = s.id
    WHERE u.id = :staff_id AND u.branch_id = :branch_id
";

$stmt = $db->prepare($sql);
$stmt->execute([':staff_id' => $staff_id, ':branch_id' => $branch_id]);
$staff = $stmt->fetch();

if (!$staff) {
    $_SESSION['error_message'] = "Staff member not found.";
    redirect('staff_directory.php');
}

$page_title = "Profile: " . htmlspecialchars($staff['full_name']);

require_once '../../header.php';
?>

<?php require_once ROOT_PATH . '/sidebar_branchadmin.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-chalkboard-teacher me-1"></i> Staff Details</span>
            <div>
                <a href="print_staff.php?id=<?php echo $staff_id; ?>" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-print me-1"></i> Print</a>
                <a href="edit_staff.php?id=<?php echo $staff_id; ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit me-1"></i> Edit Profile</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Left Column for Photo -->
                <div class="col-md-3 text-center">
                    <img src="<?php echo $staff['photo'] ? BASE_URL . '/' . htmlspecialchars($staff['photo']) : BASE_URL . '/assets/images/default_avatar.png'; ?>" 
                         class="img-thumbnail rounded-circle mb-3" 
                         alt="Staff Photo" 
                         style="width: 150px; height: 150px; object-fit: cover;">
                    <h4><?php echo htmlspecialchars($staff['full_name']); ?></h4>
                    <p class="text-muted">Teacher</p>
                    <span class="badge bg-<?php echo $staff['status'] == 'active' ? 'success' : 'danger'; ?>"><?php echo ucfirst($staff['status']); ?></span>
                </div>

                <!-- Right Column for Details -->
                <div class="col-md-9">
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">Profile</button>
                        </li>
                        <!-- Add other tabs like Payroll, Attendance later -->
                    </ul>
                    <div class="tab-content pt-3" id="myTabContent">
                        <div class="tab-pane fade show active" id="profile" role="tabpanel">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr><th width="30%">Full Name</th><td width="70%"><?php echo htmlspecialchars($staff['full_name']); ?></td></tr>
                                    <tr><th>Email (Login)</th><td><?php echo htmlspecialchars($staff['email']); ?></td></tr>
                                    <tr><th>Date of Birth</th><td><?php echo $staff['dob'] ? date('d M, Y', strtotime($staff['dob'])) : 'N/A'; ?></td></tr>
                                    <tr><th>Gender</th><td><?php echo ucfirst($staff['gender']); ?></td></tr>
                                    <tr><th>CNIC</th><td><?php echo htmlspecialchars($staff['cnic'] ?? 'N/A'); ?></td></tr>
                                    <tr><th>Joining Date</th><td><?php echo $staff['joining_date'] ? date('d M, Y', strtotime($staff['joining_date'])) : 'N/A'; ?></td></tr>
                                    <tr>
                                        <th>Class Incharge</th>
                                        <td>
                                            <?php 
                                            if ($staff['class_incharge_name']) {
                                                echo htmlspecialchars($staff['class_incharge_name']);
                                                if ($staff['section_incharge_name']) {
                                                    echo ' - ' . htmlspecialchars($staff['section_incharge_name']);
                                                }
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>