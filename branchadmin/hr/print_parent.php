<?php
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$parent_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$parent_id) {
    die("Invalid parent ID.");
}

// Fetch parent details
$stmt_parent = $db->prepare("SELECT * FROM parents WHERE id = ? AND branch_id = ?");
$stmt_parent->execute([$parent_id, $branch_id]);
$parent = $stmt_parent->fetch();

if (!$parent) {
    die("Parent not found.");
}

// Fetch Branch Name
$stmt_branch = $db->prepare("SELECT name FROM branches WHERE id = ?");
$stmt_branch->execute([$branch_id]);
$branch_name = $stmt_branch->fetchColumn();

// Fetch linked children for the current session
$stmt_children = $db->prepare("
    SELECT s.id, u.full_name, c.name as class_name, sec.name as section_name
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN student_enrollments se ON s.id = se.student_id
    LEFT JOIN academic_sessions sess ON se.session_id = sess.id AND sess.is_current = 1
    LEFT JOIN classes c ON se.class_id = c.id
    LEFT JOIN sections sec ON se.section_id = sec.id
    WHERE s.parent_id = ? AND s.branch_id = ?
    GROUP BY s.id
    ORDER BY u.full_name
");
$stmt_children->execute([$parent_id, $branch_id]);
$children = $stmt_children->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Parent Login Details - <?php echo htmlspecialchars($parent['father_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f7fb;
        }
        .container {
            max-width: 850px;
        }
        .profile-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
            border: 1px solid #e1e5ee;
        }
        .profile-header {
            background: #1d3557;
            color: #fff;
            padding: 20px 25px;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            text-align: center;
            position: relative;
        }
        .profile-header .logo {
            position: absolute;
            left: 25px;
            top: 50%;
            transform: translateY(-50%);
            height: 50px;
        }
        .profile-header h2 {
            margin: 0;
            font-size: 24px;
        }
        .profile-header h4 {
            margin: 4px 0 0;
            font-weight: normal;
            font-size: 16px;
            opacity: 0.9;
        }
        .section {
            padding: 25px;
            border-bottom: 1px solid #e9edf5;
        }
        .section:last-child {
            border-bottom: none;
        }
        .section-title {
            margin: 0 0 15px;
            font-size: 16px;
            color: #1d3557;
            border-left: 4px solid #1d3557;
            padding-left: 10px;
            font-weight: 600;
        }
        .details-table {
            width: 100%;
        }
        .details-table th, .details-table td {
            padding: 10px 8px;
            vertical-align: top;
            border-bottom: 1px solid #f1f1f1;
        }
        .details-table th {
            font-weight: 600;
            color: #555;
            width: 30%;
        }
        @media print {
            .no-print { display: none; }
            body { background: #fff; margin: 0; }
            .container { max-width: 100%; margin: 0; padding: 0; }
            .profile-card { box-shadow: none; border: none; border-radius: 0; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="container mt-5">
    <div class="profile-card">
        <div class="profile-header">
            <?php if (SITE_LOGO && file_exists(ROOT_PATH . '/' . SITE_LOGO)): ?>
                <img src="<?php echo BASE_URL . '/' . SITE_LOGO; ?>" alt="School Logo" class="logo">
            <?php endif; ?>
            <h2><?php echo htmlspecialchars(SITE_NAME); ?></h2>
            <h4><?php echo htmlspecialchars($branch_name); ?></h4>
            <h4 style="font-size: 18px; margin-top: 10px;">Parent Profile & Login Details</h4>
        </div>

        <div class="section">
            <div class="section-title">Father's Information</div>
            <table class="details-table">
                <tr><th>Name</th><td><?php echo htmlspecialchars($parent['father_name']); ?></td></tr>
                <tr><th>Phone (Username)</th><td><?php echo htmlspecialchars($parent['father_phone']); ?></td></tr>
                <tr><th>CNIC</th><td><?php echo htmlspecialchars($parent['father_cnic']); ?></td></tr>
                <tr><th>Email</th><td><?php echo htmlspecialchars($parent['father_email'] ?? 'N/A'); ?></td></tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Mother's Information</div>
            <table class="details-table">
                <tr><th>Name</th><td><?php echo htmlspecialchars($parent['mother_name'] ?? 'N/A'); ?></td></tr>
                <tr><th>Phone</th><td><?php echo htmlspecialchars($parent['mother_phone'] ?? 'N/A'); ?></td></tr>
                <tr><th>CNIC</th><td><?php echo htmlspecialchars($parent['mother_cnic'] ?? 'N/A'); ?></td></tr>
                <tr><th>Email</th><td><?php echo htmlspecialchars($parent['mother_email'] ?? 'N/A'); ?></td></tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Children in this School</div>
            <table class="table table-sm table-bordered">
                <thead><tr><th>Student Name</th><th>Class</th><th>Section</th></tr></thead>
                <tbody>
                    <?php if (empty($children)): ?>
                        <tr><td colspan="3" class="text-center">No children linked to this parent found.</td></tr>
                    <?php else: foreach ($children as $child): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($child['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($child['class_name']); ?></td>
                            <td><?php echo htmlspecialchars($child['section_name']); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Parent Portal Login Credentials</div>
            <p>Please use the father's mobile number for both username and password.</p>
            <table class="details-table">
                <tr><th>Username</th><td><strong><?php echo htmlspecialchars($parent['father_phone']); ?></strong></td></tr>
                <tr><th>Password</th><td><strong><?php echo htmlspecialchars($parent['father_phone']); ?></strong></td></tr>
            </table>
            <p class="text-danger small mt-2">For security, it is highly recommended that you change your password after your first login.</p>
            
            <div class="d-flex justify-content-between align-items-center mt-4">
                <p class="mb-0">Scan the QR code to go directly to the login page.</p>
                <div class="text-center">
                    <?php
                    $login_url = BASE_URL . '/auth/login.php';
                    $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=' . urlencode($login_url);
                    ?>
                    <img src="<?php echo $qr_code_url; ?>" alt="QR Code for Login Page">
                </div>
            </div>
        </div>

    </div>
    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn btn-primary">Print</button>
    </div>
</div>

</body>
</html>