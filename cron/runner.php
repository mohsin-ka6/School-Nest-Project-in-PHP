<?php
/**
 * =============================================================================
 * Cron Runner Script
 * =============================================================================
 * This script should be run every 5-10 minutes by a system cron job or
 * scheduled task. It checks if the configured daily backup time has passed
 * and if a backup has already been run today. If not, it executes the
 * daily_backup.php script.
 * =============================================================================
 */

// Prevent direct web access
if (php_sapi_name() !== 'cli') {
    die("Access Denied. This script can only be run from the command line.");
}

require_once dirname(__DIR__) . '/config.php';

echo "--------------------------------------------------\n";
echo "Cron Runner executed at: " . date('Y-m-d H:i:s') . "\n";

// --- Get Settings ---
$backup_time_setting = $settings_array['backup_time'] ?? '02:00';
$backup_path = BACKUP_PATH;
$today_str = date('Y-m-d');

// --- Logic to decide if backup should run ---

// 1. Check if the configured backup time for today has passed
$backup_datetime_today = new DateTime($today_str . ' ' . $backup_time_setting);
$now = new DateTime();

if ($now < $backup_datetime_today) {
    echo "Scheduled backup time ({$backup_time_setting}) has not been reached yet. Exiting.\n";
    exit;
}

echo "Scheduled backup time ({$backup_time_setting}) has passed for today.\n";

// 2. Check if a backup has already been created today
$backup_file_pattern = $backup_path . "backup-auto-{$today_str}_*.sql";
$todays_backups = glob($backup_file_pattern);

if (!empty($todays_backups)) {
    echo "A backup for today already exists. Exiting.\n";
    // You can uncomment the line below to see which file was found
    // echo "Found file: " . $todays_backups[0] . "\n";
    exit;
}

echo "No backup found for today. Proceeding to run backup script.\n";

// --- Execute the Backup Script ---

// We can execute the other PHP script directly.
// This is more efficient than making an HTTP request or using shell_exec.
try {
    // Define a variable to let the included script know it's being run by the runner
    $is_run_by_runner = true;
    
    // Include and execute the backup script
    include 'daily_backup.php';

} catch (Exception $e) {
    echo "An error occurred while running the backup script: " . $e->getMessage() . "\n";
}

echo "Cron Runner finished at: " . date('Y-m-d H:i:s') . "\n";
echo "--------------------------------------------------\n";

?>