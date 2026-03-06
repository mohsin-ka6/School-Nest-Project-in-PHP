<?php
$page_title = "Database Backup";
require_once '../../config.php';
require_once '../../functions.php';

check_role('superadmin');

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

<?php require_once '../../sidebar_superadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Database Backup</li>
    </ol>

    <?php display_flash_messages(); ?>

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
</div>

<?php require_once '../../footer.php'; ?>