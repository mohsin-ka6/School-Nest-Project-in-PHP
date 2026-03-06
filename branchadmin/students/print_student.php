<?php
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$student_id) {
    die("Invalid student ID.");
}

// First, get the current session for the branch
$stmt_session = $db->prepare("SELECT id FROM academic_sessions WHERE branch_id = ? AND is_current = 1");
$stmt_session->execute([$branch_id]);
$current_session_id = $stmt_session->fetchColumn();

// Fetch student data, including their enrollment in the CURRENT session
$sql = "
    SELECT 
        s.*, 
        u.full_name, u.email,
        se.roll_no,
        c.name as class_name, 
        sec.name as section_name,
        b.name as branch_name, b.address as branch_address
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN student_enrollments se ON s.id = se.student_id AND se.session_id = :session_id
    LEFT JOIN classes c ON se.class_id = c.id
    LEFT JOIN sections sec ON se.section_id = sec.id
    JOIN branches b ON s.branch_id = b.id
    WHERE s.id = :student_id AND s.branch_id = :branch_id
";

$stmt = $db->prepare($sql);
$stmt->execute([':student_id' => $student_id, ':branch_id' => $branch_id, ':session_id' => $current_session_id]);
$student = $stmt->fetch();

if (!$student) {
    die("Student not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admission Form - <?php echo htmlspecialchars($student['full_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      background: #f4f7fb;
      color: #333;
    }
    .container {
      max-width: 850px;
      margin: 40px auto;
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 6px 20px rgba(0,0,0,0.1);
      border: 1px solid #e1e5ee;
    }
    .header {
      background: #1d3557;
      color: #fff;
      padding: 20px 25px;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      text-align: center;
    }
    .header img.logo {
      position: absolute;
      left: 25px;
      height: 60px;
      width: auto;
    }
    .header-content {
      flex: 1;
    }
    .header h2 {
      margin: 0;
      font-size: 24px;
      font-weight: bold;
    }
    .header h4 {
      margin: 4px 0;
      font-weight: normal;
      font-size: 14px;
      opacity: 0.9;
    }
    .header h3 {
      margin-top: 10px;
      font-size: 18px;
      font-weight: normal;
    }
    .profile-section {
      display: flex;
      justify-content: space-between;
      padding: 25px;
      border-bottom: 1px solid #e9edf5;
    }
    .details {
      flex: 1;
      margin-right: 20px;
    }
    .details table {
      width: 100%;
      border-collapse: collapse;
    }
    .details td {
      padding: 10px 8px;
      vertical-align: top;
    }
    .details td.label {
      font-weight: 600;
      color: #1d3557;
      width: 35%;
    }
    .photo {
      width: 140px;
      text-align: center;
    }
    .photo img {
      width: 130px;
      height: 160px;
      object-fit: cover;
      border-radius: 8px;
      border: 2px solid #ccc;
    }
    .section {
      padding: 20px 25px;
      border-top: 1px solid #e9edf5;
    }
    .section h4 {
      margin: 0 0 15px;
      font-size: 16px;
      color: #1d3557;
      border-left: 4px solid #1d3557;
      padding-left: 10px;
    }
    .section table {
      width: 100%;
      border-collapse: collapse;
    }
    .section td {
      padding: 10px 8px;
    }
    .section td.label {
      font-weight: 600;
      color: #1d3557;
      width: 35%;
    }
    .note {
      font-size: 12px;
      color: #666;
      margin-top: 8px;
    }
    .qr-section {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .qr-code img {
      width: 100px;
      border: 2px solid #ddd;
      border-radius: 8px;
    }
    @media print {
        body { background: #fff; margin: 0; }
        .container { margin: 0; box-shadow: none; border: none; border-radius: 0; }
        .no-print { display: none; }
    }
    </style>
</head>
<body onload="window.print()">

<div class="container">
  <!-- Header -->
  <div class="header">
    <?php if (SITE_LOGO && file_exists(ROOT_PATH . '/' . SITE_LOGO)): ?>
        <img src="<?php echo BASE_URL . '/' . SITE_LOGO; ?>" alt="School Logo" class="logo">
    <?php endif; ?>
    <div class="header-content">
      <h2><?php echo htmlspecialchars(SITE_NAME); ?></h2>
      <h4><?php echo htmlspecialchars($student['branch_name']); ?>, <?php echo htmlspecialchars($student['branch_address']); ?></h4>
      <h3>Student Admission Form</h3>
    </div>
  </div>

  <!-- Student Info -->
  <div class="profile-section">
    <div class="details">
      <table>
        <tr>
          <td class="label">Admission No</td>
          <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
        </tr>
        <tr>
          <td class="label">Admission Date</td>
          <td><?php echo date('d F, Y', strtotime($student['admission_date'])); ?></td>
        </tr>
        <tr>
          <td class="label">Student Name</td>
          <td><?php echo htmlspecialchars($student['full_name']); ?></td>
        </tr>
        <tr>
          <td class="label">Class</td>
          <td><?php echo htmlspecialchars($student['class_name'] . ' - ' . $student['section_name']); ?></td>
        </tr>
        <tr>
          <td class="label">Date of Birth</td>
          <td><?php echo $student['dob'] ? date('d F, Y', strtotime($student['dob'])) : 'N/A'; ?></td>
        </tr>
        <tr>
          <td class="label">Gender</td>
          <td><?php echo ucfirst($student['gender']); ?></td>
        </tr>
        <tr>
          <td class="label">Email</td>
          <td><?php echo htmlspecialchars($student['email']); ?></td>
        </tr>
        <tr>
          <td class="label">Mobile Number</td>

          <td>
            <?php echo htmlspecialchars($student['mobile_no'] ?? 'N/A'); ?>
            <?php if (!empty($student['mobile_no'])): ?>
                <a href="<?php echo generate_whatsapp_link($student['mobile_no'], 'Dear Parent of ' . htmlspecialchars($student['full_name']) . ','); ?>" target="_blank" class="no-print" title="Send WhatsApp Message" style="text-decoration: none;">
                    <i class="fab fa-whatsapp" style="color: #25D366; font-size: 1.3em; margin-left: 10px; vertical-align: middle;"></i>
                </a>
            <?php endif; ?>
          </td>
        </tr>
        <tr>
          <td class="label">Roll No</td>
          <td><?php echo htmlspecialchars($student['roll_no'] ?? 'N/A'); ?></td>
        </tr>
      </table>
    </div>
    <div class="photo">
        <img src="<?php echo $student['photo'] ? BASE_URL . '/' . htmlspecialchars($student['photo']) : BASE_URL . '/assets/images/default_avatar.png'; ?>" alt="Student Photo">
    </div>
  </div>

  <!-- Login Details -->
  <div class="section">
    <h4>Student Portal Login Details</h4>
    <table>
      <tr>
        <td class="label">Username</td>
        <td><?php echo htmlspecialchars($student['email']); ?></td>
      </tr>
      <tr>
        <td class="label">Default Password</td>
        <td><?php echo $student['dob'] ? date('dmY', strtotime($student['dob'])) : 'Not Set'; ?> (Format: ddmmyyyy of DOB)</td>
      </tr>
    </table>
    <p class="note">It is highly recommended that the student changes this password upon first login.</p>
  </div>

  <!-- Public Profile -->
  <div class="section">
    <h4>Public Profile Link</h4>
    <?php
    $inquiry_url = BASE_URL . '/public/student/inq/student.php?branch_id=' . $student['branch_id'] . '&admission_no=' . urlencode($student['admission_no']);
    $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' . urlencode($inquiry_url);
    ?>
    <div class="qr-section">
      <p>Scan the QR code to view the student's public profile information online.</p>
      <div class="qr-code">
        <img src="<?php echo $qr_code_url; ?>" alt="QR Code">
      </div>
    </div>
  </div>

  <div class="section text-center no-print">
    <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">Print Form</button>
  </div>
</div>

</body>
</html>