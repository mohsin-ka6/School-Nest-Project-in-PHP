<?php
require_once '../../config.php';
require_once '../../functions.php';

check_role('superadmin');

$action = $_GET['action'] ?? null;
$branch_id = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : null;

if (!$action || ($action === 'branch' && !$branch_id)) {
    die('Invalid backup action or missing branch ID.');
}

/**
 * Generates a backup of specified tables.
 *
 * @param PDO $db The database connection.
 * @param array $tables An array of table names to back up.
 * @param int|null $branch_id If specified, only data for this branch will be backed up.
 * @return string The SQL backup content.
 */
function generate_backup_sql($db, $tables, $branch_id = null) {
    $output = "-- SchoolNest SQL Dump\n";
    $output .= "-- Generation Time: " . date('Y-m-d H:i:s') . "\n";
    $output .= "--\n\n";
    $output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $output .= "START TRANSACTION;\n";
    $output .= "SET time_zone = \"+00:00\";\n\n";

    foreach ($tables as $table) {
        try {
            // Get table structure
            $stmt_create = $db->query("SHOW CREATE TABLE `$table`");
            $create_table_row = $stmt_create->fetch(PDO::FETCH_ASSOC);
            $output .= "\n-- --------------------------------------------------------\n\n";
            $output .= "--\n-- Table structure for table `$table`\n--\n\n";
            $output .= $create_table_row['Create Table'] . ";\n\n";

            // Get table data
            $sql = "SELECT * FROM `$table`";
            $params = [];

            // If branch-specific, filter data
            if ($branch_id !== null) {
                $stmt_cols = $db->query("SHOW COLUMNS FROM `$table`");
                $columns = $stmt_cols->fetchAll(PDO::FETCH_COLUMN);

                if (in_array('branch_id', $columns)) {
                    // Direct branch link
                    $sql .= " WHERE `branch_id` = :branch_id";
                    $params[':branch_id'] = $branch_id;
                } elseif ($table === 'branches') {
                    // The branches table itself
                    $sql .= " WHERE `id` = :branch_id";
                    $params[':branch_id'] = $branch_id;
                } elseif ($table === 'settings' || ($table === 'users' && $branch_id !== null)) {
                    // For branch backup, skip all settings and only include branch-specific users
                    $sql = "SELECT * FROM `users` WHERE `branch_id` = :branch_id AND `role` != 'superadmin'";
                    $params[':branch_id'] = $branch_id;
                } elseif ($table === 'users' && $branch_id === null) {
                    // For full backup, include all users
                    // No change to SQL
                } else {
                    // For tables without a branch_id, we assume they are not branch-specific
                    // and should only be in a full backup. So for a branch backup, we skip their data.
                    $sql = null;
                }
            }

            if ($sql) {
                $stmt_data = $db->prepare($sql);
                $stmt_data->execute($params);
                $num_rows = $stmt_data->rowCount();

                if ($num_rows > 0) {
                    $output .= "--\n-- Dumping data for table `$table`\n--\n\n";
                    $output .= "INSERT INTO `$table` (";
                    
                    $fields_info = $db->query("DESCRIBE `$table`");
                    $fields = [];
                    while($row = $fields_info->fetch(PDO::FETCH_ASSOC)) {
                        $fields[] = '`' . $row['Field'] . '`';
                    }
                    $output .= implode(', ', $fields) . ") VALUES\n";

                    $row_count = 0;
                    while ($row = $stmt_data->fetch(PDO::FETCH_ASSOC)) {
                        $row_count++;
                        $output .= "(";
                        $values = [];
                        foreach ($row as $value) {
                            if ($value === null) {
                                $values[] = "NULL";
                            } else {
                                // Escape special characters
                                $values[] = "'" . $db->quote($value) . "'";
                                // PDO quote adds extra quotes, so we need to remove them
                                $values[count($values)-1] = "'" . substr($values[count($values)-1], 1, -1) . "'";
                            }
                        }
                        $output .= implode(', ', $values) . ")";
                        if ($row_count < $num_rows) {
                            $output .= ",\n";
                        } else {
                            $output .= ";\n";
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            // Log error or handle it, but don't stop the backup of other tables
            $output .= "\n-- ERROR: Could not back up table `$table`. " . $e->getMessage() . "\n";
        }
    }

    $output .= "\nCOMMIT;\n";
    return $output;
}

// --- Main Logic ---

$filename = "backup-full-system-" . date('Y-m-d_H-i-s') . ".sql";
$tables_to_backup = [];

try {
    $stmt = $db->query('SHOW TABLES');
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables_to_backup[] = $row[0];
    }
} catch (PDOException $e) {
    die("Error fetching tables: " . $e->getMessage());
}

if ($action === 'branch') {
    $stmt_branch = $db->prepare("SELECT name FROM branches WHERE id = ?");
    $stmt_branch->execute([$branch_id]);
    $branch_name = $stmt_branch->fetchColumn();
    if (!$branch_name) {
        die("Branch not found.");
    }
    $safe_branch_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $branch_name);
    $filename = "backup-branch-{$safe_branch_name}-" . date('Y-m-d_H-i-s') . ".sql";
    
    // For branch backup, we need to be selective about tables.
    // The logic inside generate_backup_sql will handle filtering.
    // We will pass all tables and let the function decide.
    $backup_content = generate_backup_sql($db, $tables_to_backup, $branch_id);

} elseif ($action === 'full') {
    // For full backup, we pass null for branch_id
    $backup_content = generate_backup_sql($db, $tables_to_backup, null);
} else {
    die("Invalid action.");
}

// Set headers to force download
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . strlen($backup_content));

echo $backup_content;
exit;

?>