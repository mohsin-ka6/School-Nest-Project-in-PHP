<?php
$page_title = "Database Backup";
require_once '../../config.php';
require_once '../../functions.php';

check_role('superadmin');

$errors = [];

// Handle Restore from Backup
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['restore_backup'])) {
    $confirmation_text = trim($_POST['confirmation_text'] ?? '');

    if ($confirmation_text !== 'I understand this is irreversible') {
        $errors[] = "Confirmation text is incorrect. Please type the phrase exactly as shown.";
    }

    if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "File upload error. Please try again. Error code: " . ($_FILES['backup_file']['error'] ?? 'N/A');
    } else {
        $file_info = pathinfo($_FILES['backup_file']['name']);
        if (strtolower($file_info['extension']) !== 'sql') {
            $errors[] = "Invalid file type. Only .sql files are allowed.";
        }
    }

    if (empty($errors)) {
        $sql_file_path = $_FILES['backup_file']['tmp_name'];
        $sql_content = file_get_contents($sql_file_path);

        if ($sql_content === false) {
            $errors[] = "Could not read the uploaded SQL file.";
        } else {
            try {
                // Temporarily disable foreign key checks to avoid order issues during restore
                $db->exec("SET FOREIGN_KEY_CHECKS=0;");

                // Execute the SQL script
                $db->exec($sql_content);

                // Re-enable foreign key checks
                $db->exec("SET FOREIGN_KEY_CHECKS=1;");

                $_SESSION['success_message'] = "Database restored successfully from the backup file!";
                redirect('index.php');
            } catch (PDOException $e) {
                // Attempt to re-enable foreign keys even if an error occurs
                $db->exec("SET FOREIGN_KEY_CHECKS=1;");
                $errors[] = "A critical error occurred during the database restore process. The operation may have partially completed. Please check the database integrity. Error: " . $e->getMessage();
            }
        }
    }
}

// Fetch all branches for the dropdown
try {
    $stmt_branches = $db->query("SELECT id, name FROM branches ORDER BY name ASC");
    $branches = $stmt_branches->fetchAll();
} catch (PDOException $e) {
    $errors[] = "Could not load school branches: " . $e->getMessage();
    $branches = [];
}

require_once '../../header.php';
?>

<?php require_once '../sidebar_superadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Database Backup</li>
    </ol>

    <?php display_flash_messages(); ?>
    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <div class="card bg-light mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1"><i class="fas fa-cogs me-2"></i>Automated Backups</h5>
                <p class="mb-0 text-muted">View, download, restore, or delete backups created by the daily scheduled task.</p>
            </div>
            <a href="manage_automated.php" class="btn btn-info">
                <i class="fas fa-tasks me-2"></i>Manage Automated Backups
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Full System Backup -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-database me-1"></i>
                    Full System Backup
                </div>
                <div class="card-body">
                    <p>This will generate a complete backup of the entire database, including all branches, users, and settings.</p>
                    <p><strong>Recommended:</strong> Perform this backup regularly and store it in a secure, off-site location.</p>
                    <a href="handler.php?action=full" class="btn btn-primary"><i class="fas fa-download me-2"></i>Download Full Backup</a>
                </div>
            </div>
        </div>

        <!-- Branch Specific Backup -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-code-branch me-1"></i>
                    Branch Specific Backup
                </div>
                <div class="card-body">
                    <p>Select a branch to generate a backup containing only the data related to that specific branch.</p>
                    <form action="handler.php" method="GET">
                        <input type="hidden" name="action" value="branch">
                        <div class="mb-3">
                            <label for="branch_id" class="form-label">Select Branch</label>
                            <select name="branch_id" id="branch_id" class="form-select" required>
                                <option value="">-- Select a Branch --</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success"><i class="fas fa-download me-2"></i>Download Branch Backup</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Restore from Backup -->
    <div class="card mb-4 border-danger">
        <div class="card-header bg-danger text-white">
            <i class="fas fa-upload me-1"></i>
            Restore from Backup
        </div>
        <div class="card-body">
            <div class="alert alert-danger">
                <h4><i class="fas fa-exclamation-triangle"></i> WARNING: Destructive Action</h4>
                <p>Restoring from a backup will <strong>completely erase and overwrite</strong> all current data in the database with the data from the backup file. This action is <strong>irreversible</strong>.</p>
                <p>Only proceed if you are absolutely sure. It is highly recommended to take a <a href="handler.php?action=full">Full System Backup</a> before performing a restore.</p>
            </div>
            <form action="index.php" method="POST" enctype="multipart/form-data" id="restore-form">
                <div class="mb-3">
                    <label for="backup_file" class="form-label">Select Backup File (.sql)</label>
                    <input type="file" name="backup_file" id="backup_file" class="form-control" accept=".sql" required>
                </div>
                <div class="mb-3">
                    <label for="confirmation_text" class="form-label">To confirm, please type: <code class="text-danger">I understand this is irreversible</code></label>
                    <input type="text" name="confirmation_text" id="confirmation_text" class="form-control" autocomplete="off" required>
                </div>
                <button type="submit" name="restore_backup" class="btn btn-danger" id="restore-button" disabled>
                    <i class="fas fa-upload me-2"></i>Restore Database
                </button>
            </form>
        </div>
    </div>

</div>

<?php require_once '../../footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmationInput = document.getElementById('confirmation_text');
    const restoreButton = document.getElementById('restore-button');
    const requiredText = 'I understand this is irreversible';

    confirmationInput.addEventListener('input', function() {
        if (this.value === requiredText) {
            restoreButton.disabled = false;
        } else {
            restoreButton.disabled = true;
        }
    });

    document.getElementById('restore-form').addEventListener('submit', function(e) {
        if (confirmationInput.value !== requiredText) {
            alert('You must type the confirmation phrase correctly to proceed.');
            e.preventDefault();
            return;
        }
        if (!confirm('ARE YOU ABSOLUTELY SURE?\n\nThis will permanently delete all current data and replace it with the backup. This action cannot be undone.')) {
            e.preventDefault();
        }
    });
});
</script>
