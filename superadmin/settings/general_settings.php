<?php
$page_title = "General Settings";
require_once '../../config.php';
require_once '../../functions.php';

check_role('superadmin');

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $site_name = trim($_POST['site_name']);
    $sms_gateway_host = trim($_POST['sms_gateway_host'] ?? '');
    $sms_gateway_token = trim($_POST['sms_gateway_token'] ?? '');
    $backup_time = trim($_POST['backup_time'] ?? '02:00');
    $backup_retention_days = (int)($_POST['backup_retention_days'] ?? 30);
    $login_notifications = isset($_POST['login_notifications']) ? '1' : '0';

    if (empty($site_name)) {
        $errors[] = "Site Name cannot be empty.";
    }

    if (empty($errors)) {
        $db->beginTransaction();
        try {
            $settings_to_update = [
                'site_name' => $site_name,
                'sms_gateway_host' => $sms_gateway_host,
                'sms_gateway_token' => $sms_gateway_token,
                'backup_time' => $backup_time,
                'backup_retention_days' => $backup_retention_days,
                'login_notifications' => $login_notifications,
            ];

            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = :value");

            foreach ($settings_to_update as $key => $value) {
                $stmt->execute([':key' => $key, ':value' => $value]);
            }

            $db->commit();
            $_SESSION['success_message'] = "Settings updated successfully!";
            redirect('general_settings.php');
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
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
        <li class="breadcrumb-item active">General Settings</li>
    </ol>

    <?php if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $_SESSION['success_message'] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['success_message']); } ?>
    <?php if (!empty($errors)) { echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; } ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-cogs me-1"></i> Site Configuration</div>
        <div class="card-body">
            <form action="" method="POST">
                <div class="mb-3">
                    <label for="site_name" class="form-label">School Name / Site Title</label>
                    <input type="text" id="site_name" name="site_name" class="form-control" value="<?php echo htmlspecialchars($settings_array['site_name'] ?? ''); ?>" required>
                </div>
                <hr>
                <h5>SMS Gateway Settings</h5>
                <div class="mb-3">
                    <label for="sms_gateway_host" class="form-label">SMS Gateway Host</label>
                    <input type="text" id="sms_gateway_host" name="sms_gateway_host" class="form-control" value="<?php echo htmlspecialchars($settings_array['sms_gateway_host'] ?? ''); ?>">
                    <div class="form-text">The base URL of your SMS gateway provider. E.g., <code>https://api.sms-provider.com</code></div>
                </div>
                <div class="mb-3">
                    <label for="sms_gateway_token" class="form-label">SMS Gateway Token/API Key</label>
                    <input type="text" id="sms_gateway_token" name="sms_gateway_token" class="form-control" value="<?php echo htmlspecialchars($settings_array['sms_gateway_token'] ?? ''); ?>">
                </div>
                <hr>
                <h5>Automated Backup Settings</h5>
                <div class="mb-3">
                    <label for="backup_time" class="form-label">Daily Backup Time</label>
                    <input type="time" class="form-control" id="backup_time" name="backup_time" value="<?php echo htmlspecialchars($settings_array['backup_time'] ?? '02:00'); ?>">
                    <div class="form-text">The time of day (in server's timezone) when the automated backup should run.</div>
                </div>
                <div class="mb-3">
                    <label for="backup_retention_days" class="form-label">Backup Retention (Days)</label>
                    <input type="number" class="form-control" id="backup_retention_days" name="backup_retention_days" value="<?php echo htmlspecialchars($settings_array['backup_retention_days'] ?? 30); ?>" min="1">
                    <div class="form-text">Automatically delete backup files older than this many days.</div>
                </div>
                <hr>
                <h5>Security Settings</h5>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="login_notifications" name="login_notifications" value="1" <?php echo ($settings_array['login_notifications'] ?? 0) == 1 ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="login_notifications">Enable Login Email Notifications</label>
                    <div class="form-text">If enabled, users will receive an email for both successful and failed login attempts to their account.</div>
                </div>

                <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>
