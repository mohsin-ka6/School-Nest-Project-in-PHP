<?php
$page_title = "User Activity Log";
require_once '../../config.php';
require_once '../../functions.php';

check_role('superadmin');

// --- FILTERS ---
$search_user = isset($_GET['search_user']) ? trim($_GET['search_user']) : '';
$search_ip = isset($_GET['search_ip']) ? trim($_GET['search_ip']) : '';
$filter_action = isset($_GET['filter_action']) ? trim($_GET['filter_action']) : '';

// --- PAGINATION ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 25;
$offset = ($page - 1) * $limit;

// --- MAIN DATA QUERY ---
$sql = "
    SELECT 
        al.id, al.username_attempt, al.ip_address, al.user_agent, al.action, al.details, al.timestamp,
        u.full_name, u.role
    FROM activity_log al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE 1=1
";
$params = [];

if (!empty($search_user)) {
    $sql .= " AND (u.full_name LIKE :user OR al.username_attempt LIKE :user)";
    $params[':user'] = '%' . $search_user . '%';
}
if (!empty($search_ip)) {
    $sql .= " AND al.ip_address = :ip";
    $params[':ip'] = $search_ip;
}
if (!empty($filter_action)) {
    $sql .= " AND al.action = :action";
    $params[':action'] = $filter_action;
}

// Get total count for pagination
$count_stmt = $db->prepare(str_replace("SELECT al.*, u.full_name, u.role", "SELECT COUNT(*)", $sql));
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

$sql .= " ORDER BY al.timestamp DESC LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);

// Bind the filter parameters
foreach ($params as $key => &$val) {
    $stmt->bindParam($key, $val);
}

// Bind the pagination parameters as integers
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

require_once '../../header.php';
?>

<?php require_once ROOT_PATH . '/sidebar_superadmin.php'; ?>

<div class="container-fluid px-4">
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-search me-1"></i> Filter Activity Log</div>
        <div class="card-body">
            <form action="" method="GET">
                <div class="row">
                    <div class="col-md-3"><input type="text" name="search_user" class="form-control" placeholder="Search User/Username..." value="<?php echo htmlspecialchars($search_user); ?>"></div>
                    <div class="col-md-3"><input type="text" name="search_ip" class="form-control" placeholder="Filter by IP Address..." value="<?php echo htmlspecialchars($search_ip); ?>"></div>
                    <div class="col-md-3">
                        <select name="filter_action" class="form-select">
                            <option value="">-- All Actions --</option>
                            <option value="login_success" <?php echo ($filter_action == 'login_success' ? 'selected' : ''); ?>>Login Success</option>
                            <option value="login_fail" <?php echo ($filter_action == 'login_fail' ? 'selected' : ''); ?>>Login Fail</option>
                        </select>
                    </div>
                    <div class="col-md-3"><button class="btn btn-primary" type="submit">Filter</button> <a href="activity_log.php" class="btn btn-secondary">Reset</a></div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-shield-alt me-1"></i> User Activity</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>User</th><th>Action</th><th>IP Address</th><th>Timestamp</th><th>Details</th></tr></thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="5" class="text-center">No activity logs found.</td></tr>
                        <?php else: foreach ($logs as $log): ?>
                            <tr class="<?php echo $log['action'] == 'login_fail' ? 'table-danger' : ''; ?>">
                                <td><?php echo $log['full_name'] ? htmlspecialchars($log['full_name']) . ' (' . htmlspecialchars($log['role']) . ')' : '<i>' . htmlspecialchars($log['username_attempt']) . '</i>'; ?></td>
                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                <td><?php echo date('d M, Y h:i A', strtotime($log['timestamp'])); ?></td>
                                <td title="<?php echo htmlspecialchars($log['user_agent']); ?>"><?php echo htmlspecialchars($log['details']); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search_user=<?php echo urlencode($search_user); ?>&search_ip=<?php echo urlencode($search_ip); ?>&filter_action=<?php echo urlencode($filter_action); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>
