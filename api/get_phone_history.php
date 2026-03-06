<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../functions.php';

check_api_auth('branchadmin');

$branch_id = $_SESSION['branch_id'];
$phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';

// Don't search for very short or empty numbers to avoid unnecessary queries
if (strlen($phone) < 4) {
    echo json_encode([]);
    exit;
}

try {
    // Search for logs with a similar phone number, showing the most recent 5
    $stmt = $db->prepare("
        SELECT name, call_date, description, call_type 
        FROM phone_log 
        WHERE branch_id = ? AND phone LIKE ? 
        ORDER BY call_date DESC
        LIMIT 5
    ");
    $stmt->execute([$branch_id, "%" . $phone . "%"]);
    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed.']);
}