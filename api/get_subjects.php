<?php
require_once '../config.php';
require_once '../functions.php';

check_api_auth(); // Use the new API authentication check

$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$branch_id = $_SESSION['branch_id'] ?? 0;

try {
    // Fetch subjects assigned to the class
    $stmt = $db->prepare("
        SELECT s.id, s.name 
        FROM subjects s
        JOIN class_subjects cs ON s.id = cs.subject_id
        WHERE cs.class_id = ? AND s.branch_id = ?
        ORDER BY s.name ASC
    ");
    $stmt->execute([$class_id, $branch_id]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($subjects);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}