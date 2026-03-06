<?php
$page_title = "Collect Fees";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$collected_by_id = $_SESSION['user_id'];
$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

if (!$invoice_id) {
    $_SESSION['error_message'] = "Invalid Invoice ID.";
    redirect('manage_invoices.php');
}

// Fetch invoice, student, and parent details
$stmt = $db->prepare("
    SELECT fi.*, u.full_name as student_name, se.roll_no, s.photo, c.name as class_name, sec.name as section_name, p.father_name
    FROM fee_invoices fi
    JOIN students s ON fi.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN classes c ON fi.class_id = c.id
    JOIN student_enrollments se ON (s.id = se.student_id AND fi.session_id = se.session_id)
    JOIN sections sec ON se.section_id = sec.id
    LEFT JOIN parents p ON s.parent_id = p.id
    WHERE fi.id = ? AND fi.branch_id = ?
");
$stmt->execute([$invoice_id, $branch_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    $_SESSION['error_message'] = "Invoice not found.";
    redirect('manage_invoices.php');
}

// Fetch previous payments for this invoice
$stmt_payments = $db->prepare("SELECT * FROM fee_payments WHERE invoice_id = ? ORDER BY payment_date DESC");
$stmt_payments->execute([$invoice_id]);
$payments = $stmt_payments->fetchAll();

$due_amount = $invoice['total_amount'] - $invoice['amount_paid'];

// Handle form submission for collecting fees
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['collect_fee'])) {
    $amount_paid = trim($_POST['amount_paid']);
    $payment_date = trim($_POST['payment_date']);
    $payment_method = trim($_POST['payment_method']);
    $notes = trim($_POST['notes']);

    if (!is_numeric($amount_paid) || $amount_paid <= 0) {
        $errors[] = "Paid amount must be a positive number.";
    }
    if ($amount_paid > $due_amount) {
        $errors[] = "Paid amount cannot be greater than the due amount.";
    }
    if (empty($payment_date)) {
        $errors[] = "Payment date is required.";
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // 1. Record the payment
            $stmt_payment = $db->prepare("INSERT INTO fee_payments (invoice_id, branch_id, amount, payment_date, payment_method, notes, collected_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_payment->execute([$invoice_id, $branch_id, $amount_paid, $payment_date, $payment_method, $notes, $collected_by_id]);
            $payment_id = $db->lastInsertId();

            // 2. Update the main invoice
            $new_total_paid = $invoice['amount_paid'] + $amount_paid;
            $new_status = ($new_total_paid >= $invoice['total_amount']) ? 'paid' : 'partially_paid';

            $stmt_update_invoice = $db->prepare("UPDATE fee_invoices SET amount_paid = ?, status = ? WHERE id = ?");
            $stmt_update_invoice->execute([$new_total_paid, $new_status, $invoice_id]);

            $db->commit();
            $_SESSION['success_message'] = "Payment of " . number_format($amount_paid, 2) . " collected successfully!";
            redirect("print_receipt.php?id=" . $payment_id);

        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

require_once '../../header.php';
?>

<?php require_once '../../sidebar_branchadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="manage_invoices.php">Manage Invoices</a></li>
        <li class="breadcrumb-item active">Collect Fees</li>
    </ol>

    <?php if (!empty($errors)) echo '<div class="alert alert-danger"><ul>' . implode('', array_map(fn($e) => "<li>$e</li>", $errors)) . '</ul></div>'; ?>

    <div class="row">
        <div class="col-md-5">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-user-graduate me-1"></i> Student & Invoice Details</div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <img src="<?php echo $invoice['photo'] ? BASE_URL . '/' . htmlspecialchars($invoice['photo']) : BASE_URL . '/assets/images/default_avatar.png'; ?>" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                        <h5 class="mt-2 mb-0"><?php echo htmlspecialchars($invoice['student_name']); ?></h5>
                        <p class="text-muted small">Roll No: <?php echo htmlspecialchars($invoice['roll_no']); ?> | Class: <?php echo htmlspecialchars($invoice['class_name'] . ' - ' . $invoice['section_name']); ?></p>
                    </div>
                    <table class="table table-sm table-bordered">
                        <tr><th>Invoice #</th><td><?php echo $invoice['id']; ?></td></tr>
                        <tr><th>Invoice Month</th><td><?php echo date('F, Y', strtotime($invoice['invoice_month'])); ?></td></tr>
                        <tr><th>Gross Amount</th><td><?php echo number_format($invoice['gross_amount'], 2); ?></td></tr>
                        <tr><th>Concession</th><td>(<?php echo number_format($invoice['concession_amount'], 2); ?>) <small class="text-muted"><?php echo htmlspecialchars($invoice['concession_details']); ?></small></td></tr>
                        <tr><th>Net Payable</th><td><?php echo number_format($invoice['total_amount'], 2); ?></td></tr>
                        <tr><th>Amount Paid</th><td><?php echo number_format($invoice['amount_paid'], 2); ?></td></tr>
                        <tr><th class="table-danger">Amount Due</th><td class="table-danger fw-bold"><?php echo number_format($due_amount, 2); ?></td></tr>
                    </table>
                </div>
            </div>

            <?php if (!empty($payments)): ?>
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-history me-1"></i> Payment History</div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php foreach ($payments as $payment): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo number_format($payment['amount'], 2); ?></strong>
                                <small class="d-block text-muted">
                                    <?php echo date('d M, Y', strtotime($payment['payment_date'])); ?> | <?php echo htmlspecialchars($payment['payment_method']); ?>
                                </small>
                            </div>
                            <a href="print_receipt.php?id=<?php echo $payment['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-print"></i></a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-7">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-hand-holding-usd me-1"></i> Record New Payment</div>
                <div class="card-body">
                    <?php if ($due_amount > 0): ?>
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="amount_paid" class="form-label">Amount Paying Now*</label>
                            <input type="number" step="0.01" id="amount_paid" name="amount_paid" class="form-control form-control-lg" value="<?php echo $due_amount; ?>" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="payment_date" class="form-label">Payment Date*</label>
                                <input type="date" id="payment_date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="payment_method" class="form-label">Payment Method</label>
                                <select id="payment_method" name="payment_method" class="form-select">
                                    <option>Cash</option>
                                    <option>Bank Transfer</option>
                                    <option>Credit/Debit Card</option>
                                    <option>Online Payment</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (Optional)</label>
                            <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
                        </div>
                        <button type="submit" name="collect_fee" class="btn btn-success btn-lg w-100">Collect Payment & Print Receipt</button>
                    </form>
                    <?php else: ?>
                        <div class="alert alert-success text-center">
                            <i class="fas fa-check-circle fa-3x mb-3"></i>
                            <h4 class="alert-heading">Invoice Fully Paid!</h4>
                            <p>No payment is due for this invoice.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../footer.php'; ?>
