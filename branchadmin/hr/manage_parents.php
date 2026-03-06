still<?php
$page_title = "Manage Parents";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "
    SELECT 
        p.id, p.father_name, p.father_phone, p.father_cnic, 
        COUNT(s.id) as child_count
    FROM parents p
    LEFT JOIN students s ON p.id = s.parent_id AND s.branch_id = p.branch_id
    WHERE p.branch_id = :branch_id
";

$params = [':branch_id' => $branch_id];

if (!empty($search_term)) {
    $sql .= " AND (p.father_name LIKE :search OR p.father_phone LIKE :search OR p.father_cnic LIKE :search)";
    $params[':search'] = '%' . $search_term . '%';
}

$sql .= " GROUP BY p.id ORDER BY p.father_name ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$parents = $stmt->fetchAll();

require_once '../../header.php';
?>

<?php require_once ROOT_PATH . '/sidebar_branchadmin.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">

    <?php display_flash_messages(); ?>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-search me-1"></i> Search Parents</div>
        <div class="card-body">
            <form action="" method="GET">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search by Name, Phone, or CNIC..." value="<?php echo htmlspecialchars($search_term); ?>">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-user-friends me-1"></i> Parent List</span>
            <a href="add_parent.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Add New Parent</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Father's Name</th>
                            <th>Phone</th>
                            <th>CNIC</th>
                            <th>Children in School</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parents as $parent): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($parent['father_name']); ?></td>
                            <td><?php echo htmlspecialchars($parent['father_phone']); ?></td>
                            <td><?php echo htmlspecialchars($parent['father_cnic']); ?></td>
                            <td><?php echo $parent['child_count']; ?></td>
                            <td>
                                <a href="view_parent.php?id=<?php echo $parent['id']; ?>" class="btn btn-sm btn-info" title="View Details"><i class="fas fa-eye"></i></a>
                                <a href="edit_parent.php?id=<?php echo $parent['id']; ?>" class="btn btn-sm btn-success" title="Edit"><i class="fas fa-edit"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($parents)) echo '<tr><td colspan="5" class="text-center">No parents found.</td></tr>'; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>