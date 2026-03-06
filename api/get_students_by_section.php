<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../functions.php';

check_api_auth('branchadmin');

$branch_id = $_SESSION['branch_id'];
$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;

if (!$section_id) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $db->prepare("
        SELECT s.id, u.full_name
        FROM students s
        JOIN users u ON s.user_id = u.id
        JOIN student_enrollments se ON s.id = se.student_id
        JOIN academic_sessions sess ON se.session_id = sess.id AND sess.is_current = 1
        WHERE s.branch_id = ? AND se.section_id = ?
        ORDER BY u.full_name ASC
    ");
    $stmt->execute([$branch_id, $section_id]);
    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed.']);
}