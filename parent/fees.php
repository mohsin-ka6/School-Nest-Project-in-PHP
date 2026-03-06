<?php
$page_title = "Fee Details";
require_once '../config.php';
require_once '../functions.php';

check_role('parent');

// Get the parent ID from the logged-in user
$stmt_parent = $db->prepare("SELECT id FROM parents WHERE user_id = ?");
$stmt_parent->execute([$_SESSION['user_id']]);
$parent_id = $stmt_parent->fetchColumn();

if (!$parent_id) {
    die("Could not identify parent record for this user.");
}

// Get all children of this parent
$stmt_children = $db->prepare("SELECT s.id, u.full_name FROM students s JOIN users u ON s.user_id = u.id WHERE s.parent_id = ?");
$stmt_children->execute([$parent_id]);
$children = $stmt_children->fetchAll();

if (empty($children)) {
    die("No children found for this parent.");
}

// Determine which child to show. Default to the first one.
$selected_child_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : $children[0]['id'];

// Fetch invoices for the selected child
$stmt_invoices = $db->prepare("SELECT * FROM fee_invoices WHERE student_id = ? ORDER BY invoice_month DESC");
$stmt_invoices->execute([$selected_child_id]);
$invoices = $stmt_invoices->fetchAll();

// Fetch payments for the selected child
$stmt_payments = $db->prepare("
    SELECT fp.*, fi.invoice_month 
    FROM fee_payments fp 
    JOIN fee_invoices fi ON fp.invoice_id = fi.id 
    WHERE fi.student_id = ? 
    ORDER BY fp.payment_date DESC
");
$stmt_payments->execute([$selected_child_id]);
$payments = $stmt_payments->fetchAll();

require_once '../header.php';
?>

<?php require_once '../sidebar_parent.php'; ?>
<?php require_once '../navbar.php'; ?>

<div class="container-fluid px-4">

    <?php if (count($children) > 1): ?>
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-child me-1"></i> Select Child</div>
        <div class="card-body">
            <form action="" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <select name="student_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($children as $child): ?>
                                <option value="<?php echo $child['id']; ?>" <?php echo ($selected_child_id == $child['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($child['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <nav>
        <div class="nav nav-tabs" id="nav-tab" role="tablist">
            <button class="nav-link active" id="nav-invoices-tab" data-bs-toggle="tab" data-bs-target="#nav-invoices" type="button" role="tab" aria-controls="nav-invoices" aria-selected="true">
                <i class="fas fa-file-invoice-dollar me-1"></i> Invoices
            </button>
            <button class="nav-link" id="nav-history-tab" data-bs-toggle="tab" data-bs-target="#nav-history" type="button" role="tab" aria-controls="nav-history" aria-selected="false">
                <i class="fas fa-history me-1"></i> Payment History
            </button>
        </div>
    </nav>

    <div class="tab-content" id="nav-tabContent">
        <!-- Invoices Tab -->
        <div class="tab-pane fade show active" id="nav-invoices" role="tabpanel" aria-labelledby="nav-invoices-tab">
            <div class="card card-body border-top-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Month</th>
                                <th>Net Payable</th>
                                <th>Amount Paid</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoices)): ?>
                                <tr><td colspan="6" class="text-center">No invoices found for this student.</td></tr>
                            <?php else: foreach ($invoices as $invoice): ?>
                            <tr>
                                <td><?php echo date('F, Y', strtotime($invoice['invoice_month'])); ?></td>
                                <td><?php echo number_format($invoice['total_amount'], 2); ?></td>
                                <td><?php echo number_format($invoice['amount_paid'], 2); ?></td>
                                <td><?php echo date('d M, Y', strtotime($invoice['due_date'])); ?></td>
                                <td>
                                    <?php 
                                    $status_badge = 'secondary';
                                    if ($invoice['status'] == 'paid') $status_badge = 'success';
                                    if ($invoice['status'] == 'unpaid') $status_badge = 'danger';
                                    if ($invoice['status'] == 'partially_paid') $status_badge = 'warning text-dark';
                                    echo '<span class="badge bg-' . $status_badge . '">' . ucfirst(str_replace('_', ' ', $invoice['status'])) . '</span>';
                                    ?>
                                </td>
                                <td>
                                    <a href="../branchadmin/fees/print_invoice.php?id=<?php echo $invoice['id']; ?>" target="_blank" class="btn btn-sm btn-info" title="View/Print Invoice"><i class="fas fa-print"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment History Tab -->
        <div class="tab-pane fade" id="nav-history" role="tabpanel" aria-labelledby="nav-history-tab">
            <div class="card card-body border-top-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Receipt #</th>
                                <th>Payment Date</th>
                                <th>Invoice Month</th>
                                <th>Method</th>
                                <th class="text-end">Amount Paid</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)): ?>
                                <tr><td colspan="6" class="text-center">No payment history found for this student.</td></tr>
                            <?php else: foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo $payment['id']; ?></td>
                                <td><?php echo date('d M, Y', strtotime($payment['payment_date'])); ?></td>
                                <td><?php echo date('F, Y', strtotime($payment['invoice_month'])); ?></td>
                                <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                <td class="text-end"><?php echo number_format($payment['amount'], 2); ?></td>
                                <td>
                                    <a href="../branchadmin/fees/print_receipt.php?id=<?php echo $payment['id']; ?>" target="_blank" class="btn btn-sm btn-info" title="View Receipt"><i class="fas fa-receipt"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>
