<?php
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];
$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$print_format = isset($_GET['print_format']) ? trim($_GET['print_format']) : null;

if (!$invoice_id) {
    $_SESSION['error_message'] = "Invalid Invoice ID.";
    redirect('manage_invoices.php');
}

// Fetch invoice details
$stmt = $db->prepare("
    SELECT 
        fi.*, 
        s.admission_no, 
        se.roll_no, 
        u.full_name as student_name, 
        c.name as class_name, 
        sec.name as section_name
    FROM fee_invoices fi
    JOIN students s ON fi.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN classes c ON fi.class_id = c.id
    JOIN student_enrollments se ON (s.id = se.student_id AND fi.session_id = se.session_id)
    JOIN sections sec ON se.section_id = sec.id
    WHERE fi.id = ? AND fi.branch_id = ?
");
$stmt->execute([$invoice_id, $branch_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    $_SESSION['error_message'] = "Invoice not found.";
    redirect('manage_invoices.php');
}

// Fetch invoice items
$stmt_items = $db->prepare("SELECT fid.amount, ft.name as fee_type_name FROM fee_invoice_details fid JOIN fee_types ft ON fid.fee_type_id = ft.id WHERE fid.invoice_id = ? ORDER BY ft.name");
$stmt_items->execute([$invoice_id]);
$invoice_items = $stmt_items->fetchAll();

// Re-usable render function (from print_bulk_invoices.php)
function render_invoice_content($invoice, $invoice_items, $copy_title = null) {
    $barcode_url = 'https://barcode.tec-it.com/barcode.ashx?data=' . urlencode($invoice['barcode']) . '&code=Code128&dpi=96';
    ?>
    <div class="invoice-part">
        <?php if ($copy_title): ?>
            <div class="copy-title"><?php echo $copy_title; ?></div>
        <?php endif; ?>
        <div class="invoice-header">
            <h5><?php echo htmlspecialchars(SITE_NAME); ?></h5>
            <p class="mb-1"><?php echo htmlspecialchars($_SESSION['branch_name']); ?> - Fee Invoice</p>
        </div>
        <div class="row mb-2">
            <div class="col-7">
                <strong>Student:</strong> <?php echo htmlspecialchars($invoice['student_name']); ?><br>
                <strong>Class:</strong> <?php echo htmlspecialchars($invoice['class_name'] . ' - ' . $invoice['section_name']); ?>
            </div>
            <div class="col-5 text-end">
                <strong>Invoice #:</strong> <?php echo $invoice['id']; ?><br>
                <strong>Month:</strong> <?php echo date('F, Y', strtotime($invoice['invoice_month'])); ?><br>
                <strong>Due Date:</strong> <?php echo date('d M, Y', strtotime($invoice['due_date'])); ?>
            </div>
        </div>
        <table class="table table-sm table-bordered invoice-table">
            <thead><tr><th>Particulars</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
                <?php foreach ($invoice_items as $item): ?>
                <tr><td><?php echo htmlspecialchars($item['fee_type_name']); ?></td><td class="text-end"><?php echo number_format($item['amount'], 2); ?></td></tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row"><td class="text-end">Gross Amount</td><td class="text-end">PKR <?php echo number_format($invoice['gross_amount'], 2); ?></td></tr>
                <?php if ($invoice['concession_amount'] > 0): ?>
                <tr><td class="text-end">Concession <small>(<?php echo htmlspecialchars($invoice['concession_details']); ?>)</small></td><td class="text-end">(PKR <?php echo number_format($invoice['concession_amount'], 2); ?>)</td></tr>
                <?php endif; ?>
                <tr class="total-row"><td class="text-end">Net Payable Amount</td><td class="text-end">PKR <?php echo number_format($invoice['total_amount'], 2); ?></td></tr>
            </tfoot>
        </table>
        <div class="text-center">
            <?php if($invoice['barcode']): ?>
                <img src="<?php echo $barcode_url; ?>" alt="Barcode" style="height: 50px;" class="barcode-img">
            <?php endif; ?>
        </div>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Invoice #<?php echo $invoice['id']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; }
        .page-container {
            width: 21cm;
            min-height: 29.7cm;
            padding: 1cm;
            margin: 1cm auto;
            border: 1px #D3D3D3 solid;
            border-radius: 5px;
            background: white;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }
        .invoice-part {
            border: 2px dashed #ccc;
            padding: 15px;
            margin-bottom: 20px;
            position: relative;
        }
        .copy-title {
            position: absolute;
            top: -15px;
            left: 20px;
            background: white;
            padding: 0 10px;
            font-weight: bold;
            color: #6c757d;
        }
        .invoice-header { text-align: center; margin-bottom: 15px; }
        .invoice-header h5, .invoice-header p { margin: 0; }
        .invoice-table th, .invoice-table td { padding: 0.5rem; font-size: 0.9rem; }
        .total-row td { font-weight: bold; }

        @media print {
            body { background-color: #fff; }
            .no-print { display: none; }
            .page-container {
                margin: 0;
                border: none;
                border-radius: 0;
                box-shadow: none;
                width: auto;
                min-height: auto;
                padding: 0;
            }
        }
    </style>
</head>
<body <?php if ($print_format) echo 'onload="window.print()"'; ?>>

<?php if (!$print_format): ?>
    <div class="container mt-5 no-print">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Print Invoice #<?php echo $invoice['id']; ?></h4>
                    </div>
                    <div class="card-body">
                        <p>Printing for student: <strong><?php echo htmlspecialchars($invoice['student_name']); ?></strong></p>
                        <form action="" method="GET">
                            <input type="hidden" name="id" value="<?php echo $invoice_id; ?>">
                            <div class="mb-3">
                                <label class="form-label">Select Print Format:</label>
                                <div class="form-check">
                                  <input class="form-check-input" type="radio" name="print_format" id="format1" value="3copy" checked>
                                  <label class="form-check-label" for="format1">
                                    <strong>1 Student per Page (3 Copies)</strong>
                                    <small class="d-block text-muted">School, Student, and Bank copies.</small>
                                  </label>
                                </div>
                                <div class="form-check mt-2">
                                  <input class="form-check-input" type="radio" name="print_format" id="format2" value="2copy">
                                  <label class="form-check-label" for="format2">
                                    <strong>1 Student per Page (2 Copies)</strong>
                                    <small class="d-block text-muted">School and Student copies.</small>
                                  </label>
                                </div>
                                <div class="form-check mt-2">
                                  <input class="form-check-input" type="radio" name="print_format" id="format3" value="1copy">
                                  <label class="form-check-label" for="format3">
                                    <strong>1 Student per Page (1 Copy)</strong>
                                    <small class="d-block text-muted">A single copy of the invoice.</small>
                                  </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-print me-1"></i> Print</button>
                            <a href="manage_invoices.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="page-container">
        <?php
        switch ($print_format) {
            case '2copy':
                render_invoice_content($invoice, $invoice_items, 'School Copy');
                render_invoice_content($invoice, $invoice_items, 'Student Copy');
                break;
            case '1copy':
                render_invoice_content($invoice, $invoice_items, 'Invoice');
                break;
            case '3copy':
            default:
                render_invoice_content($invoice, $invoice_items, 'School Copy');
                render_invoice_content($invoice, $invoice_items, 'Student Copy');
                render_invoice_content($invoice, $invoice_items, 'Bank Copy');
                break;
        }
        ?>
    </div>
    <div class="text-center my-4 no-print">
        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print me-1"></i> Print Again</button>
        <button onclick="window.close()" class="btn btn-secondary"><i class="fas fa-times me-1"></i> Close</button>
    </div>
<?php endif; ?>

</body>
</html>