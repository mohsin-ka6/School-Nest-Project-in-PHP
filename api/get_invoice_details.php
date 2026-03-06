<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../functions.php';

check_api_auth('branchadmin');

$branch_id = $_SESSION['branch_id'];
$search_term = isset($_GET['term']) ? trim($_GET['term']) : '';

if (empty($search_term)) {
    echo json_encode(['error' => 'Search term is required.']);
    exit;
}

try {
    // Determine if the search term is a barcode or an invoice ID
    if (is_numeric($search_term) && strlen($search_term) < 10) { // Assume it's an invoice ID
        $sql = "SELECT fi.id, fi.total_amount, fi.amount_paid, fi.status, fi.invoice_month, u.full_name as student_name, s.photo, c.name as class_name, sec.name as section_name
                FROM fee_invoices fi
                JOIN students s ON fi.student_id = s.id
                JOIN users u ON s.user_id = u.id
                JOIN classes c ON fi.class_id = c.id
                JOIN sections sec ON s.section_id = sec.id
                WHERE fi.id = :term AND fi.branch_id = :branch_id";
        $params = [':term' => $search_term, ':branch_id' => $branch_id];
    } else { // Assume it's a barcode
        $sql = "SELECT fi.id, fi.total_amount, fi.amount_paid, fi.status, fi.invoice_month, u.full_name as student_name, s.photo, c.name as class_name, sec.name as section_name
                FROM fee_invoices fi
                JOIN students s ON fi.student_id = s.id
                JOIN users u ON s.user_id = u.id
                JOIN classes c ON fi.class_id = c.id
                JOIN sections sec ON s.section_id = sec.id
                WHERE fi.barcode = :term AND fi.branch_id = :branch_id";
        $params = [':term' => $search_term, ':branch_id' => $branch_id];
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $invoice = $stmt->fetch();

    if ($invoice) {
        $invoice['due_amount'] = $invoice['total_amount'] - $invoice['amount_paid'];
        echo json_encode($invoice);
    } else {
        echo json_encode(null);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed.']);
}