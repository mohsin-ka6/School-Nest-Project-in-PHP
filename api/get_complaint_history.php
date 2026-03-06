<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../functions.php';

check_api_auth('branchadmin');

$branch_id = $_SESSION['branch_id'];
$phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';

// Only search when a full 11-digit number is entered
if (strlen($phone) !== 11) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $db->prepare("
        SELECT complaint_no, complaint_date, description, status 
        FROM complaints 
        WHERE branch_id = ? AND phone = ? 
        ORDER BY complaint_date DESC
        LIMIT 5
    ");
    $stmt->execute([$branch_id, $phone]);
    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed.']);
}