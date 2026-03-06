<?php
$page_title = "Payment History";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];

// --- FILTERS ---
$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$payment_method = isset($_GET['payment_method']) ? trim($_GET['payment_method']) : '';

// Fetch data for filter dropdowns
$stmt_sessions = $db->prepare("SELECT id, name FROM academic_sessions WHERE branch_id = ? ORDER BY start_date DESC");
$stmt_sessions->execute([$branch_id]);
$sessions = $stmt_sessions->fetchAll();

$stmt_classes = $db->prepare("SELECT id, name FROM classes WHERE branch_id = ? ORDER BY numeric_name ASC");
$stmt_classes->execute([$branch_id]);
$classes = $stmt_classes->fetchAll();

// Default to current session if none is selected
if (!$session_id && !empty($sessions)) {
    $stmt_current_session = $db->prepare("SELECT id FROM academic_sessions WHERE branch_id = ? AND is_current = 1");
    $stmt_current_session->execute([$branch_id]);
    $session_id = $stmt_current_session->fetchColumn();
}

// --- FETCH PAYMENTS BASED ON FILTERS ---
$payments = [];
$where_clauses = ["fp.branch_id = :branch_id"];
$params = [':branch_id' => $branch_id];

if ($session_id) {
    $where_clauses[] = "fi.session_id = :session_id";
    $params[':session_id'] = $session_id;
}
if ($class_id) {
    $where_clauses[] = "fi.class_id = :class_id";
    $params[':class_id'] = $class_id;
}
if ($section_id) {
    $where_clauses[] = "se.section_id = :section_id";
    $params[':section_id'] = $section_id;
}
if ($date_from) {
    $where_clauses[] = "fp.payment_date >= :date_from";
    $params[':date_from'] = $date_from;
}
if ($date_to) {
    $where_clauses[] = "fp.payment_date <= :date_to";
    $params[':date_to'] = $date_to;
}
if ($payment_method) {
    $where_clauses[] = "fp.payment_method = :payment_method";
    $params[':payment_method'] = $payment_method;
}

$sql = "SELECT 
            fp.id as payment_id, fp.amount, fp.payment_date, fp.payment_method,
            fi.id as invoice_id,
            student_user.full_name as student_name,
            c.name as class_name,
            collector_user.full_name as collected_by
        FROM fee_payments fp
        JOIN fee_invoices fi ON fp.invoice_id = fi.id
        JOIN students s ON fi.student_id = s.id
        JOIN users student_user ON s.user_id = student_user.id
        JOIN users collector_user ON fp.collected_by = collector_user.id
        JOIN student_enrollments se ON (s.id = se.student_id AND fi.session_id = se.session_id)
        JOIN classes c ON se.class_id = c.id
        WHERE " . implode(' AND ', $where_clauses) . "
        ORDER BY fp.payment_date DESC, fp.id DESC";

$stmt_payments = $db->prepare($sql);
$stmt_payments->execute($params);
$payments = $stmt_payments->fetchAll();

$total_collected = array_sum(array_column($payments, 'amount'));

require_once '../../header.php';
?>

<?php require_once '../../sidebar_branchadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Payment History</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-filter me-1"></i> Filter Transactions</div>
        <div class="card-body">
            <form action="" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-3 mb-2"><label>Session</label>
                        <select name="session_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($sessions as $session) echo "<option value='{$session['id']}' " . ($session_id == $session['id'] ? 'selected' : '') . ">" . htmlspecialchars($session['name']) . "</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2"><label>Class</label>
                        <select name="class_id" id="class_id" class="form-select">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $class) echo "<option value='{$class['id']}' " . ($class_id == $class['id'] ? 'selected' : '') . ">" . htmlspecialchars($class['name']) . "</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2"><label>Section</label>
                        <select name="section_id" id="section_id" class="form-select">
                            <option value="">All Sections</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2"><label>Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="">All</option>
                            <option value="Cash" <?php echo ($payment_method == 'Cash' ? 'selected' : ''); ?>>Cash</option>
                            <option value="Bank Transfer" <?php echo ($payment_method == 'Bank Transfer' ? 'selected' : ''); ?>>Bank Transfer</option>
                            <option value="Credit/Debit Card" <?php echo ($payment_method == 'Credit/Debit Card' ? 'selected' : ''); ?>>Credit/Debit Card</option>
                            <option value="Online Payment" <?php echo ($payment_method == 'Online Payment' ? 'selected' : ''); ?>>Online Payment</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2"><label>Date From</label><input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>"></div>
                    <div class="col-md-3 mb-2"><label>Date To</label><input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>"></div>
                    <div class="col-md-2 mb-2"><button type="submit" class="btn btn-primary w-100">Search</button></div>
                    <div class="col-md-2 mb-2"><a href="payment_history.php" class="btn btn-secondary w-100">Reset</a></div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-list-ul me-1"></i> Transaction List</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Receipt #</th>
                            <th>Payment Date</th>
                            <th>Invoice #</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Method</th>
                            <th>Collected By</th>
                            <th class="text-end">Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr><td colspan="9" class="text-center">No transactions found for the selected criteria.</td></tr>
                        <?php else: foreach ($payments as $payment): ?>
                        <tr>
                            <td><?php echo $payment['payment_id']; ?></td>
                            <td><?php echo date('d M, Y', strtotime($payment['payment_date'])); ?></td>
                            <td><a href="collect_fees.php?id=<?php echo $payment['invoice_id']; ?>"><?php echo $payment['invoice_id']; ?></a></td>
                            <td><?php echo htmlspecialchars($payment['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($payment['class_name']); ?></td>
                            <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                            <td><?php echo htmlspecialchars($payment['collected_by']); ?></td>
                            <td class="text-end"><?php echo number_format($payment['amount'], 2); ?></td>
                            <td>
                                <a href="print_receipt.php?id=<?php echo $payment['payment_id']; ?>" target="_blank" class="btn btn-sm btn-info" title="Print Receipt"><i class="fas fa-print"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold table-light">
                            <td colspan="7" class="text-end">Total Collected:</td>
                            <td class="text-end"><?php echo number_format($total_collected, 2); ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('class_id');
    const sectionSelect = document.getElementById('section_id');
    const currentSectionId = '<?php echo $section_id; ?>';

    function fetchSections(classId, targetSelect, selectedId) {
        if (!classId) {
            targetSelect.innerHTML = '<option value="">All Sections</option>';
            return;
        }
        targetSelect.innerHTML = '<option value="">Loading...</option>';
        fetch(`<?php echo BASE_URL; ?>/api/get_sections.php?class_id=${classId}`)
            .then(response => response.json())
            .then(data => {
                targetSelect.innerHTML = '<option value="">All Sections</option>';
                data.forEach(section => {
                    const selected = section.id == selectedId ? 'selected' : '';
                    targetSelect.innerHTML += `<option value="${section.id}" ${selected}>${section.name}</option>`;
                });
            });
    }

    classSelect.addEventListener('change', () => fetchSections(classSelect.value, sectionSelect, null));

    if (classSelect.value) {
        fetchSections(classSelect.value, sectionSelect, currentSectionId);
    }
});
</script>

<?php require_once '../../footer.php'; ?>

