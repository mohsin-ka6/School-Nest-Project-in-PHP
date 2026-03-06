<?php
header('Content-Type: application/json');

require_once '../config.php';
require_once '../functions.php';

if (!is_logged_in()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$cnic = isset($_GET['cnic']) ? trim($_GET['cnic']) : '';
$branch_id = $_SESSION['branch_id'];

if (empty($cnic)) {
    echo json_encode(null);
    exit;
}

try {
    $stmt = $db->prepare("SELECT p.id, p.father_name, p.father_phone, p.father_email, p.mother_name, p.mother_cnic, p.mother_phone FROM parents p WHERE p.father_cnic = ? AND p.branch_id = ?");
    $stmt->execute([$cnic, $branch_id]);
    $parent = $stmt->fetch();
    echo json_encode($parent ?: null);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed.']);
}