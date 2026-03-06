<?php
require_once '../config.php';
require_once '../functions.php';

// This API endpoint is for superadmins only.
check_api_auth('superadmin');

header('Content-Type: application/json');

try {
    // Fetch login attempts from the last 30 days
    $stmt = $db->prepare("
        SELECT
            DATE(timestamp) as login_date,
            action,
            COUNT(*) as count
        FROM activity_log
        WHERE timestamp >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
          AND action IN ('login_success', 'login_fail')
        GROUP BY login_date, action
        ORDER BY login_date ASC
    ");
    $stmt->execute();
    $results = $stmt->fetchAll();

    // Initialize arrays for the last 30 days
    $labels = [];
    $success_data = [];
    $fail_data = [];

    for ($i = 29; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('M d', strtotime($date));
        $success_data[$date] = 0;
        $fail_data[$date] = 0;
    }

    // Populate data from the database results
    foreach ($results as $row) {
        if ($row['action'] === 'login_success') {
            $success_data[$row['login_date']] = (int)$row['count'];
        } elseif ($row['action'] === 'login_fail') {
            $fail_data[$row['login_date']] = (int)$row['count'];
        }
    }

    echo json_encode(['labels' => $labels, 'success_data' => array_values($success_data), 'fail_data' => array_values($fail_data)]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>