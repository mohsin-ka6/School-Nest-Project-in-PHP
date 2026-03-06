<?php
session_start();

/**
 * =================================================================================
 * IMPORTANT SECURITY NOTICE
 * =================================================================================
 * This script is for one-time initial setup only.
 * It creates the first Super Admin user and the main school branch.
 *
 * ONCE SETUP IS COMPLETE, YOU MUST DELETE THIS FILE FROM YOUR SERVER.
 *
 * Leaving this file on a live server poses a major security risk, as it could
 * allow unauthorized users to create new super admin accounts.
 * =================================================================================
 */

// --- Database Configuration ---
// Replace with your actual database credentials.
define('DB_HOST', 'sql309.infinityfree.com');
define('DB_NAME', 'if0_39968952_schoolnest');
define('DB_USER', 'if0_39968952');
define('DB_PASS', 'schoolnest'); // Your database password, likely empty for local WAMP/XAMPP.

$message = '';
$message_type = '';

try {
    // --- 1. Connect to the Database ---
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // --- 2. Check if Setup is Already Done ---
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'superadmin'");
    if ($stmt->fetchColumn() > 0) {
        // If a superadmin exists, die immediately with a security warning.
        die("<h1>Setup Already Completed</h1><p>A super administrator account already exists. This script cannot be used again. <strong>Please delete this file (`auth/rg.php`) from your server immediately for security reasons.</strong></p>");
    }

    // --- 3. Handle Form Submission ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sanitize and retrieve form data
        $admin_name = trim(filter_input(INPUT_POST, 'admin_name', FILTER_SANITIZE_STRING));
        $admin_username = trim(filter_input(INPUT_POST, 'admin_username', FILTER_SANITIZE_STRING));
        $admin_email = trim(filter_input(INPUT_POST, 'admin_email', FILTER_VALIDATE_EMAIL));
        $admin_password = $_POST['admin_password'];

        $branch_name = trim(filter_input(INPUT_POST, 'branch_name', FILTER_SANITIZE_STRING));
        $branch_email = trim(filter_input(INPUT_POST, 'branch_email', FILTER_VALIDATE_EMAIL));
        $branch_phone = trim(filter_input(INPUT_POST, 'branch_phone', FILTER_SANITIZE_STRING));
        $branch_address = trim(filter_input(INPUT_POST, 'branch_address', FILTER_SANITIZE_STRING));

        // Basic Validation
        if (empty($admin_name) || empty($admin_username) || empty($admin_email) || empty($admin_password) || empty($branch_name)) {
            $message = "Please fill in all required fields.";
            $message_type = 'error';
        } elseif (strlen($admin_password) < 8) {
            $message = "Password must be at least 8 characters long.";
            $message_type = 'error';
        } else {
            $pdo->beginTransaction();

            try {
                // --- 4. Create the First Branch ---
                $stmt = $pdo->prepare(
                    "INSERT INTO branches (id, name, address, phone, email) VALUES (DEFAULT, :name, :address, :phone, :email)"
                );
                $stmt->execute([
                    ':name' => $branch_name,
                    ':address' => $branch_address,
                    ':phone' => $branch_phone,
                    ':email' => $branch_email
                ]);
                $branch_id = $pdo->lastInsertId();

                // --- 5. Create the Super Admin User ---
                $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    "INSERT INTO users (id, username, email, password, full_name, role, status, branch_id) 
                     VALUES (DEFAULT, :username, :email, :password, :full_name, 'superadmin', 'active', NULL)"
                );
                $stmt->execute([
                    ':username' => $admin_username,
                    ':email' => $admin_email,
                    ':password' => $hashed_password,
                    ':full_name' => $admin_name
                ]);

                // --- 6. Create Default Settings ---
                $settings = [
                    ['setting_key' => 'site_name', 'setting_value' => 'SchoolNest'],
                    ['setting_key' => 'site_logo', 'setting_value' => NULL],
                    ['setting_key' => 'currency_symbol', 'setting_value' => '$'],
                ];
                $stmt = $pdo->prepare("INSERT INTO settings (id, setting_key, setting_value) VALUES (DEFAULT, :key, :value)");
                foreach ($settings as $setting) {
                    $stmt->execute([':key' => $setting['setting_key'], ':value' => $setting['setting_value']]);
                }

                $pdo->commit();

                $message = "<strong>Success!</strong> The initial setup is complete. You can now log in with the super admin account. <br><strong>FOR SECURITY, PLEASE DELETE THIS FILE (`auth/rg.php`) FROM YOUR SERVER NOW.</strong>";
                $message_type = 'success';

            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "An error occurred during setup: " . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
} catch (PDOException $e) {
    // This error is for the initial connection or pre-check
    die("<h1>Database Error</h1><p>Could not connect to the database. Please check your credentials in `auth/rg.php` and ensure the database `ai_school` exists and is accessible.</p><p>Error: " . $e->getMessage() . "</p>");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initial System Setup</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: #f4f7f6; color: #333; margin: 0; padding: 2em; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #2c3e50; border-bottom: 2px solid #e0e0e0; padding-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #3498db; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; }
        button:hover { background-color: #2980b9; }
        .message { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .required:after { content:" *"; color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Initial System & Super Admin Setup</h1>
        <p>This form will create the first Super Administrator and the main school branch. This is a one-time process.</p>

        <?php if (!empty($message) && $message_type === 'success'): ?>
            <div class="message success"><?= $message; ?></div>
        <?php else: ?>
            <?php if (!empty($message)): ?>
                <div class="message <?= $message_type; ?>"><?= $message; ?></div>
            <?php endif; ?>

            <form action="rg copy.php" method="POST">
                <h2>Super Admin Details</h2>
                <div class="form-group">
                    <label for="admin_name" class="required">Full Name</label>
                    <input type="text" id="admin_name" name="admin_name" required>
                </div>
                <div class="form-group">
                    <label for="admin_username" class="required">Username</label>
                    <input type="text" id="admin_username" name="admin_username" required>
                </div>
                <div class="form-group">
                    <label for="admin_email" class="required">Email</label>
                    <input type="email" id="admin_email" name="admin_email" required>
                </div>
                <div class="form-group">
                    <label for="admin_password" class="required">Password (min 8 characters)</label>
                    <input type="password" id="admin_password" name="admin_password" required minlength="8">
                </div>

                <h2>Main School Branch Details</h2>
                <div class="form-group">
                    <label for="branch_name" class="required">School/Branch Name</label>
                    <input type="text" id="branch_name" name="branch_name" required>
                </div>
                <div class="form-group">
                    <label for="branch_email">Branch Email</label>
                    <input type="email" id="branch_email" name="branch_email">
                </div>
                <div class="form-group">
                    <label for="branch_phone">Branch Phone</label>
                    <input type="text" id="branch_phone" name="branch_phone">
                </div>
                <div class="form-group">
                    <label for="branch_address">Branch Address</label>
                    <input type="text" id="branch_address" name="branch_address">
                </div>

                <button type="submit">Complete Setup</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
