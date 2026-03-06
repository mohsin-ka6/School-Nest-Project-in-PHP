<?php
// --- ENVIRONMENT & ERROR REPORTING ---
// Set to 'development' or 'production'. In production, errors are logged, not displayed.
define('ENVIRONMENT', 'development'); 

if (ENVIRONMENT == 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    // Optional: Define a log file path. Ensure the directory is writable by the server.
    // ini_set('error_log', dirname(__FILE__) . '/logs/php_errors.log');
}

// Set Default Timezone
date_default_timezone_set('Asia/Karachi');

// --- SECURE SESSION MANAGEMENT ---
// Ensure cookies are sent only over HTTPS (in production) and are not accessible to JavaScript.
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1); // Prevents session fixation via URL
if (ENVIRONMENT !== 'development') {
    ini_set('session.cookie_secure', 1);
}

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Session Inactivity Timeout (e.g., 30 minutes) ---
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    // Last request was more than 30 minutes ago
    session_unset();     // Unset $_SESSION variable for the run-time
    session_destroy();   // Destroy session data in storage
}
$_SESSION['last_activity'] = time(); // Update last activity time stamp

// --- DATABASE CREDENTIALS ---
define('DB_HOST', 'localhost'); // Change to your database host if not local
define('DB_USER', 'root'); // Your database username, likely 'root' for local XAMPP/WAMP
define('DB_PASS', ''); // Your database password, likely empty for local XAMPP/WAMP
define('DB_NAME', 'myschool');

// --- ESTABLISH DATABASE CONNECTION (PDO) ---
try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Set the connection's timezone to match PHP's timezone setting (PKT is UTC+5)
    $db->exec("SET time_zone = '+05:00'");
} catch (PDOException $e) {
    die("ERROR: Database connection failed. " . $e->getMessage());
}

define('BASE_URL', 'https://schoolnest.free.nf'); // IMPORTANT: Change this to your project URL

// --- PATHS ---
define('ROOT_PATH', dirname(__FILE__));

// --- BACKUP CONFIGURATION ---
define('BACKUP_PATH', ROOT_PATH . '/Private/locked/backup/');

// --- SITE CONFIGURATION (from Database) ---
try {
    $settings_stmt = $db->query("SELECT setting_key, setting_value FROM settings");
    $settings_array = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $settings_array = []; // Default if settings table doesn't exist yet
}
define('SITE_NAME', $settings_array['site_name'] ?? 'SchoolNest');
define('SITE_LOGO', $settings_array['site_logo'] ?? null);  

// --- Composer Autoloader ---
// This loads PHPMailer and other potential libraries.
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}