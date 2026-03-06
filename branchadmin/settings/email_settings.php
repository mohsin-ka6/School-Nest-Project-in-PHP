<?php
$page_title = "Branch Email Settings";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$user_id = $_SESSION['user_id'];
$errors = [];

// Fetch or create branch SMTP settings
$stmt = $db->prepare("SELECT * FROM branch_smtp_settings WHERE branch_id = ?");
$stmt->execute([$branch_id]);
$current_settings = $stmt->fetch();

if (!$current_settings) {
    // No settings exist, create a default entry
    $db->prepare("INSERT INTO branch_smtp_settings (branch_id, use_custom) VALUES (?, 0)")->execute([$branch_id]);
    $current_settings = [
        'branch_id' => $branch_id, 'use_custom' => 0, 'smtp_host' => '', 'smtp_user' => '',
        'smtp_pass' => '', 'smtp_port' => 587, 'smtp_secure' => 'tls',
        'smtp_from_email' => '', 'smtp_from_name' => '',
    ];
}

// Fetch current admin's email for the test functionality
$stmt_user = $db->prepare("SELECT email, full_name FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$current_user = $stmt_user->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test_email') {
    // Handle sending a test email
    $use_custom = isset($_POST['use_custom']) ? (int)$_POST['use_custom'] : 0;

    if ($use_custom === 1) {
        // Test with custom settings from the form
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = trim($_POST['smtp_host']);
            $mail->SMTPAuth   = true;
            $mail->Username   = trim($_POST['smtp_user']);
            $mail->Password   = trim($_POST['smtp_pass']); // Use plain-text password from form
            $mail->SMTPSecure = trim($_POST['smtp_secure']);
            $mail->Port       = (int)$_POST['smtp_port'];

            $mail->setFrom(trim($_POST['smtp_from_email']), trim($_POST['smtp_from_name']) ?: $current_user['full_name']);
            $mail->addAddress($current_user['email'], $current_user['full_name']);

            $mail->isHTML(true);
            $mail->Subject = 'Custom SMTP Test Email from ' . SITE_NAME;
            $mail->Body    = 'This is a test email to verify your custom SMTP settings. If you received this, your configuration is correct.';
            
            $mail->send();
            $_SESSION['success_message'] = 'Test email sent successfully to ' . htmlspecialchars($current_user['email']) . '!';
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Test email could not be sent. Mailer Error: " . nl2br(htmlspecialchars($mail->ErrorInfo));
        }
    } else {
        // Test with global settings
        $subject = 'Global SMTP Test Email from ' . SITE_NAME;
        $body = 'This is a test email to verify the global SMTP settings. If you received this, the configuration is correct.';
        if (send_email($current_user['email'], $current_user['full_name'], $subject, $body, null, true)) {
            $_SESSION['success_message'] = 'Test email sent successfully using global settings to ' . htmlspecialchars($current_user['email']) . '!';
        }
        // The send_email function sets its own error message on failure
    }
    // Re-populate form to keep user's changes
    $current_settings = array_merge($current_settings, $_POST);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle saving the settings
    $use_custom = isset($_POST['use_custom']) ? (int)$_POST['use_custom'] : 0;

    try {
        if ($use_custom === 1) {
            // Using custom settings, validate and save them
            $smtp_host = trim($_POST['smtp_host']);
            $smtp_user = trim($_POST['smtp_user']);
            $smtp_port = (int)$_POST['smtp_port'];

            if (empty($smtp_host) || empty($smtp_user) || empty($smtp_port)) {
                throw new Exception("SMTP Host, User, and Port are required for custom settings.");
            }

            $sql = "UPDATE branch_smtp_settings SET use_custom = 1, smtp_host = ?, smtp_user = ?, smtp_port = ?, smtp_secure = ?, smtp_from_email = ?, smtp_from_name = ?";
            $params = [
                $smtp_host, $smtp_user, $smtp_port,
                trim($_POST['smtp_secure']), trim($_POST['smtp_from_email']), trim($_POST['smtp_from_name'])
            ];

            // Only update password if a new one is provided
            if (!empty($_POST['smtp_pass'])) {
                $sql .= ", smtp_pass = ?";
                $params[] = trim($_POST['smtp_pass']);
            }

            $sql .= " WHERE branch_id = ?";
            $params[] = $branch_id;

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

        } else {
            // Using global settings
            $stmt = $db->prepare("UPDATE branch_smtp_settings SET use_custom = 0 WHERE branch_id = ?");
            $stmt->execute([$branch_id]);
        }

        $_SESSION['success_message'] = "Email settings updated successfully.";
        redirect('email_settings.php');

    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

require_once '../../header.php';
?>

<?php require_once ROOT_PATH . '/sidebar_branchadmin.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Email Settings</li>
    </ol>

    <?php display_flash_messages(); ?>
    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <form action="email_settings.php" method="POST">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-cogs me-1"></i> Email Configuration Choice</div>
            <div class="card-body">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="use_custom" id="use_global" value="0" <?php echo ($current_settings['use_custom'] == 0) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="use_global">
                        <strong>Use Global Settings</strong> (Recommended)
                        <p class="text-muted small mb-0">Emails will be sent using the main system's email configuration.</p>
                    </label>
                </div>
                <hr>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="use_custom" id="use_custom" value="1" <?php echo ($current_settings['use_custom'] == 1) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="use_custom">
                        <strong>Use Custom SMTP Settings</strong>
                        <p class="text-muted small mb-0">Provide your own SMTP server details. Use this only if you have a dedicated email server for your branch.</p>
                    </label>
                </div>
            </div>
        </div>

        <div class="card" id="custom-smtp-container" style="display: <?php echo ($current_settings['use_custom'] == 1) ? 'block' : 'none'; ?>;">
            <div class="card-header"><i class="fas fa-server me-1"></i> Custom SMTP Details</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3"><label for="smtp_host" class="form-label">SMTP Host*</label><input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($current_settings['smtp_host'] ?? ''); ?>"></div>
                    <div class="col-md-3 mb-3"><label for="smtp_port" class="form-label">SMTP Port*</label><input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($current_settings['smtp_port'] ?? '587'); ?>"></div>
                    <div class="col-md-3 mb-3"><label for="smtp_secure" class="form-label">Encryption</label><select id="smtp_secure" name="smtp_secure" class="form-select"><option value="tls" <?php echo (($current_settings['smtp_secure'] ?? '') == 'tls') ? 'selected' : ''; ?>>TLS</option><option value="ssl" <?php echo (($current_settings['smtp_secure'] ?? '') == 'ssl') ? 'selected' : ''; ?>>SSL</option><option value="" <?php echo (($current_settings['smtp_secure'] ?? '') == '') ? 'selected' : ''; ?>>None</option></select></div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label for="smtp_user" class="form-label">SMTP Username / Email*</label><input type="text" class="form-control" id="smtp_user" name="smtp_user" value="<?php echo htmlspecialchars($current_settings['smtp_user'] ?? ''); ?>"></div>
                    <div class="col-md-6 mb-3"><label for="smtp_pass" class="form-label">SMTP App Password</label><input type="password" class="form-control" id="smtp_pass" name="smtp_pass" placeholder="Leave blank to keep current password"></div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label for="smtp_from_email" class="form-label">From Email</label><input type="email" class="form-control" id="smtp_from_email" name="smtp_from_email" value="<?php echo htmlspecialchars($current_settings['smtp_from_email'] ?? ''); ?>"></div>
                    <div class="col-md-6 mb-3"><label for="smtp_from_name" class="form-label">From Name</label><input type="text" class="form-control" id="smtp_from_name" name="smtp_from_name" value="<?php echo htmlspecialchars($current_settings['smtp_from_name'] ?? ''); ?>"></div>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" name="action" value="save_settings" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Settings</button>
            <button type="submit" name="action" value="test_email" class="btn btn-secondary" formnovalidate><i class="fas fa-paper-plane me-1"></i> Send Test Email</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const customSmtpContainer = document.getElementById('custom-smtp-container');
    document.querySelectorAll('input[name="use_custom"]').forEach(radio => {
        radio.addEventListener('change', function() {
            customSmtpContainer.style.display = (this.value === '1') ? 'block' : 'none';
        });
    });
});
</script>

<?php require_once '../../footer.php'; ?>