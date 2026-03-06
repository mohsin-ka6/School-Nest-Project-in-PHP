<?php
// AI School Management System - Setup Script
// Run this file once to set up the database and create initial users

echo "<h1>AI School Management System - Setup</h1>";

// Check if database connection works
try {
    require_once 'config/database.php';
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in config/database.php</p>";
    exit;
}

// Check if database exists and has tables
$stmt = $db->prepare("SHOW TABLES");
$stmt->execute();
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($tables)) {
    echo "<p style='color: orange;'>⚠ Database is empty. Please import the database schema first.</p>";
    echo "<p>Run this SQL command in your MySQL client:</p>";
    echo "<pre>mysql -u root -p school_management < database/school_management.sql</pre>";
    echo "<p>Or import the file 'database/school_management.sql' through phpMyAdmin.</p>";
} else {
    echo "<p style='color: green;'>✓ Database tables found: " . count($tables) . " tables</p>";
}

// Check if admin user exists
$stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
$stmt->execute();
$admin_count = $stmt->fetchColumn();

if ($admin_count == 0) {
    echo "<p style='color: orange;'>⚠ No admin user found. Creating default admin user...</p>";
    
    // Create default admin user
    $hashed_password = password_hash('password', PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['admin', 'admin@school.com', $hashed_password, 'System Administrator', 'admin']);
    
    echo "<p style='color: green;'>✓ Default admin user created!</p>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> password</p>";
} else {
    echo "<p style='color: green;'>✓ Admin user exists</p>";
}

// Create demo users if they don't exist
$demo_users = [
    ['teacher', 'teacher@school.com', 'password', 'Demo Teacher', 'teacher'],
    ['student', 'student@school.com', 'password', 'Demo Student', 'student'],
    ['parent', 'parent@school.com', 'password', 'Demo Parent', 'parent'],
    ['staff', 'staff@school.com', 'password', 'Demo Staff', 'staff']
];

foreach ($demo_users as $user) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$user[0]]);
    if ($stmt->fetchColumn() == 0) {
        $hashed_password = password_hash($user[2], PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user[0], $user[1], $hashed_password, $user[3], $user[4]]);
        echo "<p style='color: green;'>✓ Created demo user: {$user[0]}</p>";
    }
}

// Check file permissions
$upload_dir = 'assets/uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
    echo "<p style='color: green;'>✓ Created uploads directory</p>";
}

if (is_writable($upload_dir)) {
    echo "<p style='color: green;'>✓ Uploads directory is writable</p>";
} else {
    echo "<p style='color: red;'>✗ Uploads directory is not writable</p>";
    echo "<p>Please set proper permissions on: $upload_dir</p>";
}

echo "<hr>";
echo "<h2>Setup Complete!</h2>";
echo "<p>Your AI School Management System is ready to use.</p>";
echo "<p><a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";

echo "<h3>Demo Login Credentials:</h3>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin / password</li>";
echo "<li><strong>Teacher:</strong> teacher / password</li>";
echo "<li><strong>Student:</strong> student / password</li>";
echo "<li><strong>Parent:</strong> parent / password</li>";
echo "<li><strong>Staff:</strong> staff / password</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Login as admin and configure your school settings</li>";
echo "<li>Add classes and sections</li>";
echo "<li>Add teachers and assign them to classes</li>";
echo "<li>Add students and assign them to classes</li>";
echo "<li>Set up fee structures</li>";
echo "<li>Configure email settings for notifications</li>";
echo "</ol>";

echo "<p><strong>Important:</strong> Delete this setup.php file after successful setup for security reasons.</p>";
?>

