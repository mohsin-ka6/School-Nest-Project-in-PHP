<?php
require_once '../config.php';
require_once '../functions.php';

// API authentication check
check_api_auth('branchadmin');

$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$branch_id = $_SESSION['branch_id'];

if (!$session_id || !$class_id) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Session ID and Class ID are required.']);
    exit();
}

try {
    $stmt = $db->prepare("
        SELECT 
            cfs.fee_type_id, 
            cfs.amount, 
            ft.name as fee_type_name, 
            ft.is_default
        FROM class_fee_structure cfs
        JOIN fee_types ft ON cfs.fee_type_id = ft.id
        WHERE cfs.session_id = ? AND cfs.class_id = ? AND cfs.branch_id = ?
        ORDER BY ft.name
    ");
    $stmt->execute([$session_id, $class_id, $branch_id]);
    $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($fees);

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
