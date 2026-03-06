<?php
$page_title = "Payment Receipt";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$payment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$payment_id) {
    $_SESSION['error_message'] = "Invalid Payment ID.";
    redirect('manage_invoices.php');
}

// Fetch payment, invoice, and student details
$stmt = $db->prepare("
    SELECT 
        fp.id as payment_id, fp.amount as paid_amount, fp.payment_date, fp.payment_method,
        fi.id as invoice_id, fi.invoice_month, fi.total_amount as invoice_total, fi.amount_paid as invoice_total_paid, fi.barcode,
        se.roll_no, u.full_name as student_name, c.name as class_name, sec.name as section_name,
        collector.full_name as collected_by_name
    FROM fee_payments fp
    JOIN fee_invoices fi ON fp.invoice_id = fi.id
    JOIN students s ON fi.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN classes c ON fi.class_id = c.id
    JOIN student_enrollments se ON (fi.student_id = se.student_id AND fi.session_id = se.session_id)
    JOIN sections sec ON se.section_id = sec.id
    JOIN users collector ON fp.collected_by = collector.id
    WHERE fp.id = ? AND fp.branch_id = ?
");
$stmt->execute([$payment_id, $branch_id]);
$receipt = $stmt->fetch();

if (!$receipt) {
    $_SESSION['error_message'] = "Payment receipt not found.";
    redirect('manage_invoices.php');
}

$amount_due_after_payment = $receipt['invoice_total'] - $receipt['invoice_total_paid'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt #<?php echo $receipt['payment_id']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .receipt-container { max-width: 600px; margin: 20px auto; background: #fff; border: 1px solid #dee2e6; padding: 30px; }
        .receipt-header { text-align: center; margin-bottom: 30px; }
        .receipt-header h4 { margin: 0; }
        .receipt-footer { text-align: center; margin-top: 30px; font-style: italic; color: #6c757d; }
        .table th { width: 40%; }
        @media print {
            body { background-color: #fff; }
            .no-print { display: none; }
            .receipt-container { margin: 0; border: none; max-width: 100%; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="receipt-container">
    <div class="receipt-header">
        <?php if (!empty($_SESSION['branch_logo']) && file_exists(ROOT_PATH . '/' . $_SESSION['branch_logo'])): ?>
            <img src="<?php echo BASE_URL . '/' . $_SESSION['branch_logo']; ?>" alt="Logo" style="height: 60px; margin-bottom: 10px;">
        <?php endif; ?>
        <h5><?php echo htmlspecialchars(SITE_NAME); ?></h5>
        <p class="mb-1"><?php echo htmlspecialchars($_SESSION['branch_name']); ?></p>
        <p class="mb-0">Official Payment Receipt</p>
    </div>

    <div class="d-flex justify-content-between mb-3">
        <span><strong>Receipt #:</strong> <?php echo $receipt['payment_id']; ?></span>
        <span><strong>Date:</strong> <?php echo date('d M, Y', strtotime($receipt['payment_date'])); ?></span>
    </div>

    <div class="card mb-3">
        <div class="card-header">Student Information</div>
        <div class="card-body">
            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($receipt['student_name']); ?></p>
            <p class="mb-0"><strong>Class:</strong> <?php echo htmlspecialchars($receipt['class_name'] . ' - ' . $receipt['section_name']); ?></p>
        </div>
    </div>

    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th colspan="2">Payment Details</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th>Invoice #</th>
                <td><?php echo $receipt['invoice_id']; ?> (For <?php echo date('F, Y', strtotime($receipt['invoice_month'])); ?>)</td>
            </tr>
            <tr>
                <th>Payment Method</th>
                <td><?php echo htmlspecialchars($receipt['payment_method']); ?></td>
            </tr>
            <tr class="table-success">
                <th>Amount Paid</th>
                <td class="fw-bold fs-5">PKR <?php echo number_format($receipt['paid_amount'], 2); ?></td>
            </tr>
            <tr>
                <th>Remaining Due on Invoice</th>
                <td>PKR <?php echo number_format($amount_due_after_payment, 2); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="receipt-footer">
        <p>Thank you for your payment!</p>
        <p class="small mt-2">This is a computer-generated receipt and does not require a signature. <br>
        Collected by: <?php echo htmlspecialchars($receipt['collected_by_name']); ?></p>
    </div>
</div>

<div class="text-center my-4 no-print">
    <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print me-1"></i> Print Again</button>
    <a href="manage_invoices.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Manage Invoices</a>
</div>

</body>
</html>