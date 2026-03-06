<?php
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$staff_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$staff_id) {
    die("Invalid staff ID.");
}

// Fetch staff data
$sql = "
    SELECT 
        u.full_name, u.email,
        t.*,
        b.name as branch_name, b.address as branch_address
    FROM users u
    JOIN teachers t ON u.id = t.user_id
    JOIN branches b ON u.branch_id = b.id
    WHERE u.id = :staff_id AND u.branch_id = :branch_id
";

$stmt = $db->prepare($sql);
$stmt->execute([':staff_id' => $staff_id, ':branch_id' => $branch_id]);
$staff = $stmt->fetch();

if (!$staff) {
    die("Staff member not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Profile - <?php echo htmlspecialchars($staff['full_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .profile-container {
            border: 2px solid #000;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .photo-box {
            border: 1px solid #ccc;
            width: 150px;
            height: 180px;
            text-align: center;
            line-height: 180px;
            color: #999;
            float: right;
        }
        .staff-photo {
            max-width: 100%;
            max-height: 100%;
        }
        table th {
            background-color: #f2f2f2;
        }
        @media print {
            .btn { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="container profile-container">
    <div class="header">
        <h2><?php echo htmlspecialchars(SITE_NAME); ?></h2>
        <p><?php echo htmlspecialchars($staff['branch_name']); ?></p>
        <h4 class="mt-3">Staff Profile</h4>
    </div>

    <div class="photo-box">
        <?php if ($staff['photo']): ?>
            <img src="<?php echo BASE_URL . '/' . htmlspecialchars($staff['photo']); ?>" alt="Staff Photo" class="staff-photo">
        <?php else: ?>
            <span>Photo</span>
        <?php endif; ?>
    </div>

    <table class="table table-bordered mt-3">
        <tbody>
            <tr><th width="30%">Name</th><td width="70%"><?php echo htmlspecialchars($staff['full_name']); ?></td></tr>
            <tr><th>Joining Date</th><td><?php echo $staff['joining_date'] ? date('d F, Y', strtotime($staff['joining_date'])) : 'N/A'; ?></td></tr>
            <tr><th>Date of Birth</th><td><?php echo $staff['dob'] ? date('d F, Y', strtotime($staff['dob'])) : 'N/A'; ?></td></tr>
            <tr><th>Gender</th><td><?php echo ucfirst($staff['gender']); ?></td></tr>
            <tr><th>CNIC</th><td><?php echo htmlspecialchars($staff['cnic'] ?? 'N/A'); ?></td></tr>
        </tbody>
    </table>

    <h5 class="mt-4">Portal Login Details</h5>
    <table class="table table-bordered">
        <tbody>
            <tr><th width="30%">Username</th><td width="70%"><?php echo htmlspecialchars($staff['email']); ?></td></tr>
            <tr><th>Default Password</th><td><?php echo $staff['dob'] ? date('dmY', strtotime($staff['dob'])) : '<i>Not set (DOB missing)</i>'; ?> <small class="text-muted">(Format: ddmmyyyy)</small></td></tr>
        </tbody>
    </table>
    <p class="text-muted small">It is highly recommended that the staff member changes this password upon first login.</p>

    <div class="text-center mt-4">
        <button onclick="window.print()" class="btn btn-primary">Print Profile</button>
    </div>
</div>

</body>
</html>