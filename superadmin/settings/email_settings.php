<?php
$page_title = "Email Settings";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once '../../config.php';
require_once '../../functions.php';

check_role('superadmin');

$errors = [];

// Fetch current settings
$stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp_%'");
$current_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test_email') {
    // Handle sending a test email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = trim($_POST['smtp_host']);
        $mail->SMTPAuth   = true;
        $mail->Username   = trim($_POST['smtp_user']);
        $mail->Password   = trim($_POST['smtp_pass']); // Use the plain-text password from the form
        $mail->SMTPSecure = trim($_POST['smtp_secure']);
        $mail->Port       = (int)$_POST['smtp_port'];

        $mail->setFrom(trim($_POST['smtp_from_email']), trim($_POST['smtp_from_name']));
        $mail->addAddress($_SESSION['email'], $_SESSION['full_name']); // Send to the current admin

        $mail->isHTML(true);
        $mail->Subject = 'SMTP Test Email from ' . SITE_NAME;
        $mail->Body    = 'This is a test email to verify your SMTP settings. If you received this, your configuration is correct.';
        $mail->AltBody = 'This is a test email to verify your SMTP settings. If you received this, your configuration is correct.';

        $mail->send();
        $_SESSION['success_message'] = 'Test email sent successfully to ' . htmlspecialchars($_SESSION['email']) . '!';
    } catch (Exception $e) {
        // Provide a detailed error message for debugging
        $_SESSION['error_message'] = "Test email could not be sent. Mailer Error: " . nl2br(htmlspecialchars($mail->ErrorInfo));
    }
    // We don't redirect here, so the user can see the result and adjust settings
    // To preserve form state, we'll just re-populate from POST
    $current_settings = $_POST;

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle saving the settings
    $settings_to_update = [
        'smtp_host' => trim($_POST['smtp_host']),
        'smtp_user' => trim($_POST['smtp_user']),
        'smtp_port' => (int)$_POST['smtp_port'],
        'smtp_secure' => trim($_POST['smtp_secure']),
        'smtp_from_email' => trim($_POST['smtp_from_email']),
        'smtp_from_name' => trim($_POST['smtp_from_name']),
    ];

    // Only update password if a new one is provided
    if (!empty($_POST['smtp_pass'])) {
        $settings_to_update['smtp_pass'] = trim($_POST['smtp_pass']);
    }

    try {
        $db->beginTransaction();
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = :value");
        foreach ($settings_to_update as $key => $value) {
            $stmt->execute(['key' => $key, 'value' => $value]);
        }
        $db->commit();
        $_SESSION['success_message'] = "Global email settings updated successfully.";
        redirect('email_settings.php');
    } catch (PDOException $e) {
        $db->rollBack();
        $errors[] = "Database error: " . $e->getMessage();
    }
}

require_once '../../header.php';
?>

<?php require_once '../../sidebar_superadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Email Settings</li>
    </ol>

    <?php display_flash_messages(); ?>
    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <div class="card">
        <div class="card-header"><i class="fas fa-envelope me-1"></i> Global SMTP Configuration</div>
        <div class="card-body">
            <p class="text-muted">These settings will be used by default for all branches and for system-critical notifications.</p>
            <form action="email_settings.php" method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="smtp_host" class="form-label">SMTP Host</label>
                        <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($current_settings['smtp_host'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="smtp_port" class="form-label">SMTP Port</label>
                        <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($current_settings['smtp_port'] ?? '587'); ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="smtp_secure" class="form-label">Encryption</label>
                        <select id="smtp_secure" name="smtp_secure" class="form-select">
                            <option value="tls" <?php echo (($current_settings['smtp_secure'] ?? '') == 'tls') ? 'selected' : ''; ?>>TLS</option>
                            <option value="ssl" <?php echo (($current_settings['smtp_secure'] ?? '') == 'ssl') ? 'selected' : ''; ?>>SSL</option>
                            <option value="" <?php echo (($current_settings['smtp_secure'] ?? '') == '') ? 'selected' : ''; ?>>None</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="smtp_user" class="form-label">SMTP Username / Email</label>
                        <input type="text" class="form-control" id="smtp_user" name="smtp_user" value="<?php echo htmlspecialchars($current_settings['smtp_user'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="smtp_pass" class="form-label">SMTP App Password</label>
                        <input type="password" class="form-control" id="smtp_pass" name="smtp_pass" placeholder="Leave blank to keep current password">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="smtp_from_email" class="form-label">From Email</label>
                        <input type="email" class="form-control" id="smtp_from_email" name="smtp_from_email" value="<?php echo htmlspecialchars($current_settings['smtp_from_email'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="smtp_from_name" class="form-label">From Name</label>
                        <input type="text" class="form-control" id="smtp_from_name" name="smtp_from_name" value="<?php echo htmlspecialchars($current_settings['smtp_from_name'] ?? ''); ?>">
                    </div>
                </div>
                <button type="submit" name="action" value="save_settings" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Settings</button>
                <button type="submit" name="action" value="test_email" class="btn btn-secondary" formnovalidate><i class="fas fa-paper-plane me-1"></i> Send Test Email</button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>