<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../functions.php';

check_api_auth('branchadmin');

$branch_id = $_SESSION['branch_id'];

try {
    $stmt = $db->prepare("
        SELECT id, full_name, role 
        FROM users 
        WHERE branch_id = ? AND role IN ('teacher', 'branchadmin')
        ORDER BY full_name ASC
    ");
    $stmt->execute([$branch_id]);
    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed.']);
}