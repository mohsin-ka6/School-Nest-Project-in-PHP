<?php
require_once '../config.php';
require_once '../functions.php';

// This API endpoint is for branch admins to see their own branch's data.
check_api_auth('branchadmin');

header('Content-Type: application/json');

$branch_id = $_SESSION['branch_id'] ?? 0;

if (!$branch_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Branch ID not found in session.']);
    exit;
}

try {
    $stmt = $db->prepare("
        SELECT gender, COUNT(*) as count 
        FROM students 
        WHERE branch_id = ? 
        GROUP BY gender
    ");
    $stmt->execute([$branch_id]);
    $results = $stmt->fetchAll();

    $labels = [];
    $data = [];

    foreach ($results as $row) {
        $labels[] = ucfirst($row['gender']);
        $data[] = (int)$row['count'];
    }

    echo json_encode(['labels' => $labels, 'data' => $data]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>