<?php
$page_title = "Phone Call Log";
require_once dirname(__DIR__, 2) . '/config.php';
require_once ROOT_PATH . '/functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$user_id = $_SESSION['user_id'];
$errors = [];

// Handle delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    if (isset($_POST['log_id'])) {
        $log_id = (int)$_POST['log_id'];
        try {
            $stmt = $db->prepare("DELETE FROM phone_log WHERE id = ? AND branch_id = ?");
            $stmt->execute([$log_id, $branch_id]);
            $_SESSION['success_message'] = "Phone log entry deleted successfully.";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error deleting log entry: " . $e->getMessage();
        }
    }
    redirect('phone_log.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['action'])) { // Ensure this doesn't run for delete action
    $log_id = isset($_POST['log_id']) ? (int)$_POST['log_id'] : 0;
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $call_date = trim($_POST['call_date']);
    $call_type = trim($_POST['call_type']);

    // More specific validation
    if (empty($name)) $errors[] = "Name is required.";
    if (empty($phone)) $errors[] = "Phone Number is required.";
    if (empty($call_date)) $errors[] = "Call Date & Time is required.";
    if (empty($call_type)) $errors[] = "Call Type is required.";

    if (empty($errors)) {
        try {
            if ($log_id > 0) { // Update existing log
                $stmt = $db->prepare(
                    "UPDATE phone_log SET name=?, phone=?, call_date=?, description=?, next_follow_up_date=?, call_type=? 
                     WHERE id=? AND branch_id=?"
                );
                $stmt->execute([
                    $name, $phone, $call_date,
                    trim($_POST['description']),
                    empty($_POST['next_follow_up_date']) ? null : trim($_POST['next_follow_up_date']),
                    $call_type, $log_id, $branch_id
                ]);
                $_SESSION['success_message'] = "Phone call log updated successfully!";
            } else { // Insert new log
                $stmt = $db->prepare(
                    "INSERT INTO phone_log (branch_id, name, phone, call_date, description, next_follow_up_date, call_type, created_by) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $branch_id, $name, $phone, $call_date,
                    trim($_POST['description']),
                    empty($_POST['next_follow_up_date']) ? null : trim($_POST['next_follow_up_date']),
                    $call_type, $user_id
                ]);
                $_SESSION['success_message'] = "Phone call logged successfully!";
            }
            redirect('phone_log.php');
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch a specific log for editing
$edit_log = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $log_id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM phone_log WHERE id = ? AND branch_id = ?");
    $stmt->execute([$log_id, $branch_id]);
    $edit_log = $stmt->fetch();
}

// Fetch all phone logs for today for this branch
$today = date('Y-m-d');
$stmt = $db->prepare("SELECT * FROM phone_log WHERE branch_id = ? AND DATE(call_date) = ? ORDER BY call_date DESC");
$stmt->execute([$branch_id, $today]);
$logs = $stmt->fetchAll();

require_once ROOT_PATH . '/header.php';
?>

<?php require_once ROOT_PATH . '/sidebar_branchadmin.php'; ?>
<?php require_once ROOT_PATH . '/navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Phone Call Log</li>
    </ol>

    <?php display_flash_messages(); ?>
    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <!-- Form to add new log -->
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-phone-alt me-1"></i> <?php echo $edit_log ? 'Edit' : 'Add New'; ?> Phone Log</div>
        <div class="card-body">
            <form action="phone_log.php" method="POST">
                <input type="hidden" name="log_id" value="<?php echo $edit_log['id'] ?? 0; ?>">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="phone">Phone Number*</label>
                        <input type="text" name="phone" id="phone" class="form-control" value="<?php echo htmlspecialchars($edit_log['phone'] ?? ''); ?>" required>
                        <?php if (!$edit_log): // Only show history for new entries ?>
                            <div id="call-history" class="mt-2"></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 mb-3"><label for="name">Name*</label><input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($edit_log['name'] ?? ''); ?>" required></div>
                    <div class="col-md-4 mb-3"><label>Call Date & Time*</label><input type="datetime-local" name="call_date" class="form-control" required value="<?php echo htmlspecialchars($edit_log ? date('Y-m-d\TH:i', strtotime($edit_log['call_date'])) : date('Y-m-d\TH:i')); ?>"></div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3"><label>Call Type*</label><select name="call_type" class="form-select"><option value="incoming" <?php echo (($edit_log['call_type'] ?? '') == 'incoming') ? 'selected' : ''; ?>>Incoming</option><option value="outgoing" <?php echo (($edit_log['call_type'] ?? '') == 'outgoing') ? 'selected' : ''; ?>>Outgoing</option></select></div>
                    <div class="col-md-8 mb-3"><label>Description</label><input type="text" name="description" class="form-control" value="<?php echo htmlspecialchars($edit_log['description'] ?? ''); ?>"></div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3"><label>Next Follow-up Date</label><input type="date" name="next_follow_up_date" class="form-control" value="<?php echo htmlspecialchars($edit_log['next_follow_up_date'] ?? ''); ?>"></div>
                </div>
                <button type="submit" class="btn btn-primary"><?php echo $edit_log ? 'Update' : 'Log'; ?> Call</button>
                <?php if ($edit_log): ?><a href="phone_log.php" class="btn btn-secondary">Cancel Edit</a><?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Table of today's logs -->
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-table me-1"></i> Today's Call Log</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>Name</th><th>Phone</th><th>Time</th><th>Type</th><th>Description</th><th>Follow-up</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="7" class="text-center">No calls logged today.</td></tr>
                        <?php else: foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['name']); ?></td>
                                <td><?php echo htmlspecialchars($log['phone']); ?></td>
                                <td><?php echo date('h:i A', strtotime($log['call_date'])); ?></td>
                                <td><?php echo ucfirst($log['call_type']); ?></td>
                                <td><?php echo htmlspecialchars($log['description']); ?></td>
                                <td><?php echo $log['next_follow_up_date'] ? date('d M, Y', strtotime($log['next_follow_up_date'])) : 'N/A'; ?></td>
                                <td>
                                    <a href="phone_log.php?action=edit&id=<?php echo $log['id']; ?>" class="btn btn-sm btn-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                    <form action="phone_log.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this log?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="log_id" value="<?php echo $log['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                 </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Do not run history search if we are in edit mode
    if (<?php echo $edit_log ? 'true' : 'false'; ?>) {
        return;
    }

    const phoneInput = document.getElementById('phone');
    const nameInput = document.getElementById('name');
    const historyContainer = document.getElementById('call-history');
    let debounceTimer;

    phoneInput.addEventListener('keyup', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const phone = phoneInput.value.trim();

            // Only trigger search when exactly 11 digits are entered
            if (phone.length !== 11) {
                historyContainer.innerHTML = '';
                return;
            }

            historyContainer.innerHTML = '<div class="text-muted small"><i class="fas fa-spinner fa-spin"></i> Searching history...</div>';

            fetch(`<?php echo BASE_URL; ?>/api/get_phone_history.php?phone=${phone}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        historyContainer.innerHTML = `<div class="text-danger small">${data.error}</div>`;
                        return;
                    }

                    if (data.length > 0) {
                        // If the name field is empty, pre-fill it with the name from the most recent call
                        if (nameInput.value.trim() === '') {
                            nameInput.value = data[0].name;
                        }

                        let historyHtml = '<ul class="list-group list-group-flush small">';
                        historyHtml += '<li class="list-group-item list-group-item-secondary fw-bold">Recent Call History:</li>';
                        data.forEach(call => {
                            const callDate = new Date(call.call_date).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
                            historyHtml += `<li class="list-group-item"><strong>${callDate}</strong>: ${call.description || 'No description'} (${call.call_type})</li>`;
                        });
                        historyHtml += '</ul>';
                        historyContainer.innerHTML = historyHtml;
                    } else {
                        historyContainer.innerHTML = '<div class="text-muted small">No previous call history found for this number.</div>';
                    }
                });
        }, 300); // Debounce for 300ms to avoid excessive API calls
    });
});
</script>

<?php require_once ROOT_PATH . '/footer.php'; ?>