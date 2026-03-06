<?php
$page_title = "Visitor Book";
require_once dirname(__DIR__, 2) . '/config.php';
require_once ROOT_PATH . '/functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$user_id = $_SESSION['user_id'];
$errors = [];

// Handle marking a visitor's exit
if (isset($_GET['action']) && $_GET['action'] == 'exit' && isset($_GET['id'])) {
    $visitor_id = (int)$_GET['id'];
    try {
        $stmt = $db->prepare("UPDATE visitor_log SET exit_time = NOW() WHERE id = ? AND branch_id = ? AND exit_time IS NULL");
        $stmt->execute([$visitor_id, $branch_id]);
        $_SESSION['success_message'] = "Visitor exit time recorded.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database Error: " . $e->getMessage();
    }
    redirect('visitor_book.php');
}

// Handle form submission for adding a new visitor
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $visitor_name = trim($_POST['visitor_name']);
    $purpose = trim($_POST['purpose']);
    $entry_time = trim($_POST['entry_time']);

    if (empty($visitor_name) || empty($purpose) || empty($entry_time)) {
        $errors[] = "Visitor Name, Purpose, and Entry Time are required.";
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare(
                "INSERT INTO visitor_log (branch_id, visitor_name, purpose, person_to_meet, phone, id_card_details, entry_time, notes, created_by) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $branch_id,
                $visitor_name,
                $purpose,
                trim($_POST['person_to_meet']),
                trim($_POST['phone']),
                trim($_POST['id_card_details']),
                $entry_time,
                trim($_POST['notes']),
                $user_id
            ]);
            $_SESSION['success_message'] = "Visitor logged successfully!";
            redirect('visitor_book.php');
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch all visitor logs for today for this branch
$today = date('Y-m-d');
$stmt = $db->prepare("SELECT * FROM visitor_log WHERE branch_id = ? AND DATE(entry_time) = ? ORDER BY entry_time DESC");
$stmt->execute([$branch_id, $today]);
$visitors = $stmt->fetchAll();

require_once ROOT_PATH . '/header.php';
?>

<?php require_once ROOT_PATH . '/sidebar_branchadmin.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Visitor Book</li>
    </ol>

    <?php display_flash_messages(); ?>
    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <!-- Form to add new visitor -->
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-user-plus me-1"></i> Add New Visitor</div>
        <div class="card-body">
            <form action="" method="POST">
                <div class="row">
                    <div class="col-md-4 mb-3"><label>Visitor Name*</label><input type="text" name="visitor_name" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label>Purpose*</label><input type="text" name="purpose" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label>Person to Meet</label><input type="text" name="person_to_meet" class="form-control"></div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3"><label>Phone</label><input type="text" name="phone" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label>ID Card Details</label><input type="text" name="id_card_details" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label>Entry Time*</label><input type="datetime-local" name="entry_time" class="form-control" required value="<?php echo date('Y-m-d\TH:i'); ?>"></div>
                </div>
                <div class="mb-3"><label>Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                <button type="submit" class="btn btn-primary">Log Visitor</button>
            </form>
        </div>
    </div>

    <!-- Table of today's visitors -->
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-table me-1"></i> Today's Visitor List</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>Visitor Name</th><th>Purpose</th><th>Person to Meet</th><th>Entry Time</th><th>Exit Time</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($visitors)): ?>
                            <tr><td colspan="6" class="text-center">No visitors logged today.</td></tr>
                        <?php else: foreach ($visitors as $visitor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($visitor['visitor_name']); ?></td>
                                <td><?php echo htmlspecialchars($visitor['purpose']); ?></td>
                                <td><?php echo htmlspecialchars($visitor['person_to_meet']); ?></td>
                                <td><?php echo date('h:i A', strtotime($visitor['entry_time'])); ?></td>
                                <td><?php echo $visitor['exit_time'] ? date('h:i A', strtotime($visitor['exit_time'])) : '<span class="badge bg-warning text-dark">In</span>'; ?></td>
                                <td><?php if (!$visitor['exit_time']): ?><a href="visitor_book.php?action=exit&id=<?php echo $visitor['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Mark this visitor as exited?');">Exit</a><?php endif; ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/footer.php'; ?>