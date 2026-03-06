<?php
header('Content-Type: application/json');

require_once '../config.php';
require_once '../functions.php';

// Basic security: ensure the user is logged in as a teacher
if (!is_logged_in() || $_SESSION['role'] !== 'teacher') {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
$teacher_id = $_SESSION['user_id'];

if (!$section_id) {
    echo json_encode([]); // Return empty array if no section_id
    exit;
}

try {
    // Fetch subjects assigned to this teacher for the given section
    $stmt = $db->prepare("
        SELECT s.id, s.name FROM subjects s
        JOIN teacher_assignments ta ON s.id = ta.subject_id
        WHERE ta.teacher_id = ? AND ta.section_id = ? ORDER BY s.name ASC");
    $stmt->execute([$teacher_id, $section_id]);
    $subjects = $stmt->fetchAll();
    echo json_encode($subjects);
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database query failed.']);
}