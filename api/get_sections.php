<?php
require_once '../config.php';
require_once '../functions.php';

check_api_auth(); // Use the new API authentication check

header('Content-Type: application/json');

$class_id = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
// Prioritize branch_id from GET param, fallback to session for other roles (e.g., branchadmin)
$branch_id = isset($_GET['branch_id']) ? (int) $_GET['branch_id'] : ($_SESSION['branch_id'] ?? 0);

try {
    $stmt = $db->prepare("SELECT id, name FROM sections WHERE class_id = ? AND branch_id = ? ORDER BY name ASC");
    $stmt->execute([$class_id, $branch_id]);
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($sections);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}