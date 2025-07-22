<?php
// backend/diagnose_issues.php - Diagnose all login system issues
session_start();
require_once 'classes/database.class.php';

echo "<h1>Login System Diagnostic</h1>";

try {
    $connection = new Database();
    $conn = $connection->connect();
    
    // Check 1: PHP Version
    echo "<h2>1. PHP Version Check</h2>";
    echo "Current PHP Version: " . PHP_VERSION . "<br>";
    if (version_compare(PHP_VERSION, '8.0.0', '<')) {
        echo "<span style='color: orange;'>⚠️ PHP < 8.0 - match expressions won't work</span><br>";
    } else {
        echo "<span style='color: green;'>✓ PHP 8.0+ - match expressions supported</span><br>";
    }
    
    // Check 2: Session Status
    echo "<h2>2. Session Check</h2>";
    if (session_status() === PHP_SESSION_ACTIVE) {
        echo "<span style='color: green;'>✓ Session is active</span><br>";
        echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";
    } else {
        echo "<span style='color: red;'>✗ Session not active</span><br>";
    }
    
    // Check 3: Database Tables
    echo "<h2>3. Database Tables Check</h2>";
    $tables = ['user', 'teacher', 'education_degree', 'teacher_disciplines', 'disciplinas'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            echo "<span style='color: green;'>✓ Table '$table' exists</span><br>";
        } else {
            echo "<span style='color: red;'>✗ Table '$table' missing</span><br>";
        }
    }
    
    // Check 4: Education Table Structure
    echo "<h2>4. Education Table Columns</h2>";
    $stmt = $conn->query("SHOW COLUMNS FROM education_degree");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns: " . implode(', ', $columns) . "<br>";
    
    if (in_array('instituition', $columns)) {
        echo "<span style='color: orange;'>⚠️ Found 'instituition' (typo) in database</span><br>";
        echo "Fix: Use <code>\$education->instituition</code> in views OR rename column<br>";
    } else if (in_array('institution', $columns)) {
        echo "<span style='color: green;'>✓ Correct 'institution' column found</span><br>";
    }
    
    // Check 5: Sample Teacher with Disciplines
    echo "<h2>5. Sample Teacher Data Structure</h2>";
    $stmt = $conn->query("
        SELECT t.id, t.name, 
               d.id as disc_id, d.name as disc_name, td.enabled
        FROM teacher t
        LEFT JOIN teacher_disciplines td ON t.id = td.teacher_id
        LEFT JOIN disciplinas d ON td.discipline_id = d.id
        LIMIT 1
    ");
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sample) {
        echo "<pre>";
        print_r($sample);
        echo "</pre>";
        echo "Note: Disciplines are returned as arrays with keys: id, name, enabled<br>";
    }
    
    // Check 6: User Types
    echo "<h2>6. User Types in System</h2>";
    $stmt = $conn->query("SELECT DISTINCT user_type, COUNT(*) as count FROM user GROUP BY user_type");
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>User Type</th><th>Count</th></tr>";
    foreach ($types as $type) {
        echo "<tr><td>" . $type['user_type'] . "</td><td>" . $type['count'] . "</td></tr>";
    }
    echo "</table>";
    
    // Check 7: File Permissions
    echo "<h2>7. File Access Check</h2>";
    $files_to_check = [
        '../pages/login.php',
        '../auth/process_login.php',
        '../components/header.php',
        '../pages/docente.php'
    ];
    
    foreach ($files_to_check as $file) {
        if (file_exists($file)) {
            echo "<span style='color: green;'>✓ $file exists</span>";
            if (is_readable($file)) {
                echo " (readable)";
            } else {
                echo " <span style='color: red;'>(not readable)</span>";
            }
            echo "<br>";
        } else {
            echo "<span style='color: red;'>✗ $file not found</span><br>";
        }
    }
    
    // Summary
    echo "<h2>Summary of Fixes Needed:</h2>";
    echo "<ol>";
    echo "<li>Update login.php JavaScript to allow 'credenciamento'</li>";
    echo "<li>Fix undefined \$admin variable in header.php</li>";
    echo "<li>Fix Education property name (instituition vs institution)</li>";
    echo "<li>Ensure disciplines are accessed as arrays not objects</li>";
    echo "<li>Replace match expressions if PHP < 8.0</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}