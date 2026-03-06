<?php
/**
 * =============================================================================
 * Automated Daily Backup Script
 * =============================================================================
 * This script is designed to be run automatically by a cron job (Linux) or
 * a Scheduled Task (Windows). It is NOT meant to be accessed via a web browser.
 *
 * It generates a full SQL backup of the database and saves it to a local
 * directory on the server.
 * =============================================================================
 */

// Prevent direct web access for security
if (php_sapi_name() !== 'cli') {
    die("Access Denied. This script can only be run from the command line.");
}

// Set timezone to avoid warnings
date_default_timezone_set('Asia/Karachi');

// The script is in /cron, so we need to go up one level to the project root.
require_once dirname(__DIR__) . '/config.php';

// --- Configuration ---
// The path where backups will be stored.
// IMPORTANT: Ensure this directory exists and is writable by the user running the script.
$backup_path = 'E:/wamp64/www/ai_school/Private/locked/backup/';

// --- Backup Logic ---

echo "Starting automated backup...\n";

// Check if backup directory exists and is writable
if (!is_dir($backup_path)) {
    // Try to create it
    if (!mkdir($backup_path, 0755, true)) {
        die("ERROR: Backup directory does not exist and could not be created: $backup_path\n");
    }
}
if (!is_writable($backup_path)) {
    die("ERROR: Backup directory is not writable: $backup_path\n");
}

try {
    // Fetch all table names from the database
    $stmt_tables = $db->query('SHOW TABLES');
    $tables = $stmt_tables->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        die("ERROR: No tables found in the database.\n");
    }

    // Start building the SQL content
    $sql_content = "-- SchoolNest SQL Dump (Automated)\n";
    $sql_content .= "-- Generation Time: " . date('Y-m-d H:i:s') . "\n";
    $sql_content .= "-- Database: " . DB_NAME . "\n";
    $sql_content .= "--\n\n";
    $sql_content .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sql_content .= "START TRANSACTION;\n";
    $sql_content .= "SET time_zone = \"+00:00\";\n\n";
    $sql_content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    // Iterate through each table
    foreach ($tables as $table) {
        // Get table structure
        $stmt_create = $db->query("SHOW CREATE TABLE `$table`");
        $create_table_row = $stmt_create->fetch(PDO::FETCH_ASSOC);
        $sql_content .= "\n-- --------------------------------------------------------\n";
        $sql_content .= "-- Table structure for table `$table`\n--\n";
        $sql_content .= $create_table_row['Create Table'] . ";\n\n";

        // Get table data
        $stmt_data = $db->query("SELECT * FROM `$table`");
        $num_rows = $stmt_data->rowCount();

        if ($num_rows > 0) {
            $sql_content .= "-- Dumping data for table `$table`\n--\n";
            while ($row = $stmt_data->fetch(PDO::FETCH_ASSOC)) {
                $sql_content .= "INSERT INTO `$table` VALUES(";
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = "NULL";
                    } else {
                        // Use PDO's quote method for safe string escaping
                        $values[] = $db->quote($value);
                    }
                }
                $sql_content .= implode(', ', $values) . ");\n";
            }
        }
    }

    $sql_content .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
    $sql_content .= "COMMIT;\n";

    // Define the filename
    $filename = "backup-auto-" . date('Y-m-d_H-i-s') . ".sql";
    $file_path = $backup_path . $filename;

    // Save the SQL file
    if (file_put_contents($file_path, $sql_content) === false) {
        echo "ERROR: Could not write backup file to disk at: $file_path\n";
    } else {
        echo "SUCCESS: Backup completed successfully.\n";
        echo "File saved to: $file_path\n";
    }

} catch (PDOException $e) {
    die("DATABASE ERROR: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("GENERAL ERROR: " . $e->getMessage() . "\n");
}

?>