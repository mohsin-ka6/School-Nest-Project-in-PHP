<?php
require_once 'config/config.php';

echo "<h2>Database Connection Test</h2>";

try {
    // Test database connection
    $stmt = $db->query("SELECT 1");
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Check if students table exists
    $stmt = $db->query("SHOW TABLES LIKE 'students'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Students table exists!</p>";
        
        // Show table structure
        $stmt = $db->query("DESCRIBE students");
        echo "<h3>Students Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Count students
        $stmt = $db->query("SELECT COUNT(*) as count FROM students");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>Total students in database: <strong>$count</strong></p>";
        
    } else {
        echo "<p style='color: red;'>❌ Students table does not exist!</p>";
        echo "<p>Please run the database setup script.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='setup.php'>Run Setup Script</a> | <a href='modules/admin/students.php'>Go to Students Page</a></p>";
?>
