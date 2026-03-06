<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../functions.php';

// Secure the API endpoint
check_api_auth('superadmin');

$branch_id = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;

if (!$branch_id) {
    echo json_encode([]);
    exit();
}

try {
    $stmt = $db->prepare("SELECT id, name FROM classes WHERE branch_id = ? ORDER BY numeric_name ASC, name ASC");
    $stmt->execute([$branch_id]);
    $classes = $stmt->fetchAll();
    echo json_encode($classes);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}