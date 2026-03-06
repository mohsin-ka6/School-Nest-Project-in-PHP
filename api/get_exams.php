<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../functions.php';

check_api_auth('branchadmin');

$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$branch_id = $_SESSION['branch_id'];

if (!$session_id) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $db->prepare("SELECT id, name FROM exam_types WHERE session_id = ? AND branch_id = ? ORDER BY name ASC");
    $stmt->execute([$session_id, $branch_id]);
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($exams);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed.']);
}