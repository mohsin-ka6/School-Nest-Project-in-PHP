<?php
require_once '../config.php';
require_once '../functions.php';

check_api_auth('branchadmin');

$branch_id = $_SESSION['branch_id'];

// Get the current academic session to ensure we only show relevant data
$stmt_session = $db->prepare("SELECT id FROM academic_sessions WHERE branch_id = ? AND is_current = 1");
$stmt_session->execute([$branch_id]);
$session_id = $stmt_session->fetchColumn();

if (!$session_id) {
    // If no current session, return empty data to avoid errors
    echo json_encode(['labels' => [], 'data' => []]);
    exit();
}

try {
    // Fetch fee collections for the last 6 months within the current session
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(fp.payment_date, '%Y-%m') as month,
            SUM(fp.amount) as total_collected
        FROM fee_payments fp
        JOIN fee_invoices fi ON fp.invoice_id = fi.id
        WHERE fp.branch_id = ? 
          AND fi.session_id = ?
          AND fp.payment_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
        GROUP BY DATE_FORMAT(fp.payment_date, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute([$branch_id, $session_id]);
    $results = $stmt->fetchAll();

    // Create a template for the last 6 months to ensure all months are shown, even with 0 collection
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $months[date('Y-m', strtotime("-$i months"))] = 0;
    }

    // Fill the template with actual data
    foreach ($results as $row) {
        $months[$row['month']] = (float)$row['total_collected'];
    }

    header('Content-Type: application/json');
    echo json_encode(['labels' => array_map(fn($m) => date('M Y', strtotime($m . '-01')), array_keys($months)), 'data' => array_values($months)]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
