<?php
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

$branch_id = $_SESSION['branch_id'];

// --- FILTERS from GET ---
$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
$invoice_month = isset($_GET['invoice_month']) ? trim($_GET['invoice_month']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$print_format = isset($_GET['print_format']) ? trim($_GET['print_format']) : '3copy'; // Default to 3-copy format

// --- FETCH INVOICE IDs BASED ON FILTERS ---
$where_clauses = ["fi.branch_id = :branch_id"];
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
    $params[':section_id'] = $section_id; // This requires joining student_enrollments
}
if ($invoice_month) {
    $where_clauses[] = "fi.invoice_month = :invoice_month";
    $params[':invoice_month'] = $invoice_month;
}
if ($status) {
    $where_clauses[] = "fi.status = :status";
    $params[':status'] = $status;
}

$sql = "SELECT fi.id FROM fee_invoices fi
        JOIN students s ON fi.student_id = s.id
        JOIN student_enrollments se ON (s.id = se.student_id AND fi.session_id = se.session_id)
        WHERE " . implode(' AND ', $where_clauses) . "
         ORDER BY se.roll_no, s.id";
$stmt_invoices = $db->prepare($sql);
$stmt_invoices->execute($params);
$invoice_ids = $stmt_invoices->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulk Fee Invoices</title>
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
            page-break-after: always;
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

        /* 3-in-1 Layout */
        .format-3in1 .page-container {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .format-3in1 .invoice-container {
            height: 32%; /* Approx 1/3 of the page with padding */
            border: 1px solid #999;
            padding: 10px;
            margin-bottom: 1%;
            font-size: 0.8rem;
        }
        .format-3in1 .invoice-table th, .format-3in1 .invoice-table td { padding: 0.25rem; font-size: 0.75rem; }
        .format-3in1 .invoice-header h5 { font-size: 1rem; }
        .format-3in1 .barcode-img { height: 40px !important; }

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
<body onload="window.print()" class="format-<?php echo htmlspecialchars($print_format); ?>">

<?php
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

<?php if (empty($invoice_ids)): ?>
    <div class="alert alert-warning m-5">No invoices found for the selected criteria.</div>
<?php else: ?>
    <?php
        // Prepare the statement once
        $stmt_invoice_details = $db->prepare("
            SELECT fi.*, s.admission_no, se.roll_no, u.full_name as student_name, c.name as class_name, sec.name as section_name
            FROM fee_invoices fi
            JOIN students s ON fi.student_id = s.id
            JOIN users u ON s.user_id = u.id
            JOIN classes c ON fi.class_id = c.id
            JOIN student_enrollments se ON (s.id = se.student_id AND fi.session_id = se.session_id)
            JOIN sections sec ON se.section_id = sec.id
            WHERE fi.id = ? AND fi.branch_id = ?
        ");
        $stmt_items = $db->prepare("SELECT fid.amount, ft.name as fee_type_name FROM fee_invoice_details fid JOIN fee_types ft ON fid.fee_type_id = ft.id WHERE fid.invoice_id = ? ORDER BY ft.name");

        switch ($print_format) {
            case '3in1':
                $invoice_chunks = array_chunk($invoice_ids, 3);
                foreach ($invoice_chunks as $chunk) {
                    echo '<div class="page-container">';
                    foreach ($chunk as $invoice_id) {
                        $stmt_invoice_details->execute([$invoice_id, $branch_id]);
                        $invoice = $stmt_invoice_details->fetch();
                        if (!$invoice) continue;
                        $stmt_items->execute([$invoice_id]);
                        $invoice_items = $stmt_items->fetchAll();
                        echo '<div class="invoice-container">';
                        render_invoice_content($invoice, $invoice_items);
                        echo '</div>';
                    }
                    echo '</div>';
                }
                break;

            case '2copy':
            case '3copy':
            default:
                foreach ($invoice_ids as $invoice_id) {
                    $stmt_invoice_details->execute([$invoice_id, $branch_id]);
                    $invoice = $stmt_invoice_details->fetch();
                    if (!$invoice) continue;
                    $stmt_items->execute([$invoice_id]);
                    $invoice_items = $stmt_items->fetchAll();

                    echo '<div class="page-container">';
                    render_invoice_content($invoice, $invoice_items, 'School Copy');
                    render_invoice_content($invoice, $invoice_items, 'Student Copy');
                    if ($print_format === '3copy') {
                        render_invoice_content($invoice, $invoice_items, 'Bank Copy');
                    }
                    echo '</div>';
                }
                break;
        }
    ?>
<?php endif; ?>

<div class="text-center my-4 no-print">
    <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print me-1"></i> Print Again</button>
    <button onclick="window.close()" class="btn btn-secondary"><i class="fas fa-times me-1"></i> Close</button>
</div>

</body>
</html>