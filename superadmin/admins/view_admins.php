<?php
$page_title = "Manage Branch Admins";
require_once '../../config.php';
require_once '../../functions.php';

check_role('superadmin');

// Fetch all branch admins and their assigned branches
$stmt = $db->query("
    SELECT u.id, u.full_name, u.username, u.email, u.status, b.name as branch_name
    FROM users u
    LEFT JOIN branches b ON u.branch_id = b.id
    WHERE u.role = 'branchadmin'
    ORDER BY u.full_name ASC
");
$admins = $stmt->fetchAll();

require_once '../../header.php';
?>

<?php require_once '../../sidebar_superadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/superadmin/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Manage Admins</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-user-shield me-1"></i> All Branch Admins</span>
            <a href="add_admin.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Add New Admin</a>
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Assigned Branch</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($admins) > 0): ?>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                <td><?php echo htmlspecialchars($admin['branch_name'] ?? 'N/A'); ?></td>
                                <td><span class="badge bg-<?php echo $admin['status'] == 'active' ? 'success' : 'danger'; ?>"><?php echo ucfirst($admin['status']); ?></span></td>
                                <td>
                                    <a href="edit_admin.php?id=<?php echo $admin['id']; ?>" class="btn btn-success btn-sm"><i class="fas fa-edit"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">No branch admins found. Please add one.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>