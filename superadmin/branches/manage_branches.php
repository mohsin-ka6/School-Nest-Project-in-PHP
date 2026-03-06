<?php
$page_title = "Manage Branches";
require_once '../../config.php';
require_once '../../functions.php';

check_role('superadmin');

// Fetch all branches
$stmt = $db->query("SELECT * FROM branches ORDER BY name ASC");
$branches = $stmt->fetchAll();

require_once '../../header.php';
?>

<?php require_once '../../sidebar_superadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Manage Branches</li>
    </ol>

    <?php display_flash_messages(); ?>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-table me-1"></i> Existing Branches</span>
            <a href="add_branch.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Add New Branch</a>
        </div>
        <div class="card-body">
            <table class="table table-bordered align-middle">
                <thead><tr><th>Logo</th><th>Branch Name</th><th>Phone</th><th>Email</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($branches)): ?>
                        <tr><td colspan="5" class="text-center">No branches found. Click 'Add New Branch' to get started.</td></tr>
                    <?php else: ?>
                        <?php foreach ($branches as $branch): ?>
                        <tr>
                            <td>
                                <?php if (!empty($branch['logo']) && file_exists(ROOT_PATH . '/' . $branch['logo'])): ?>
                                    <img src="<?php echo BASE_URL . '/' . $branch['logo']; ?>" alt="Logo" style="height: 40px;">
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($branch['name']); ?></td>
                            <td><?php echo htmlspecialchars($branch['phone']); ?></td>
                            <td><?php echo htmlspecialchars($branch['email']); ?></td>
                            <td>
                                <a href="edit_branch.php?id=<?php echo $branch['id']; ?>" class="btn btn-sm btn-success" title="Edit"><i class="fas fa-edit"></i></a>
                                <a href="login_as_branch.php?branch_id=<?php echo $branch['id']; ?>" class="btn btn-sm btn-info" title="Login as Branch Admin">
                                    <i class="fas fa-sign-in-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>