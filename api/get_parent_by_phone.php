<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../functions.php';

check_api_auth('branchadmin');

$branch_id = $_SESSION['branch_id'];
$phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';

if (strlen($phone) !== 11) {
    echo json_encode(null);
    exit;
}

try {
    $stmt = $db->prepare("
        SELECT id, father_name 
        FROM parents 
        WHERE branch_id = ? AND father_phone = ?
    ");
    $stmt->execute([$branch_id, $phone]);
    echo json_encode($stmt->fetch() ?: null);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed.']);
}