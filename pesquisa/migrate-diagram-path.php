<?php
/**
 * Migration: Add diagram_path column to courses table
 */

// Fix for CLI
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '/pesquisa/migrate-diagram-path.php';
}

require_once __DIR__ . '/includes/init.php';

echo "<h1>Database Migration: Add diagram_path Column</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;} .ok{color:green;} .error{color:red;}</style>";

$db = PesquisaDatabase::getInstance();

try {
    echo "<h2>Step 1: Check if column exists</h2>";
    
    // Check if column already exists
    $columns = $db->fetchAll("SHOW COLUMNS FROM courses LIKE 'diagram_path'");
    
    if (count($columns) > 0) {
        echo "<p class='ok'>✅ Column 'diagram_path' already exists!</p>";
        echo "<p><a href='analytics.php'>Go to Analytics</a></p>";
        exit;
    }
    
    echo "<p>Column 'diagram_path' does not exist. Adding it now...</p>";
    
    echo "<h2>Step 2: Add column</h2>";
    
    $sql = "ALTER TABLE courses 
            ADD COLUMN diagram_path VARCHAR(255) DEFAULT NULL COMMENT 'Path to generated diagram' 
            AFTER qr_code_path";
    
    $db->execute($sql);
    
    echo "<p class='ok'>✅ Column 'diagram_path' added successfully!</p>";
    
    echo "<h2>Step 3: Add index</h2>";
    
    $sql = "ALTER TABLE courses ADD INDEX idx_diagram_path (diagram_path)";
    
    try {
        $db->execute($sql);
        echo "<p class='ok'>✅ Index added successfully!</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "<p class='ok'>✅ Index already exists (skipped)</p>";
        } else {
            throw $e;
        }
    }
    
    echo "<h2>Step 4: Verify</h2>";
    
    $columns = $db->fetchAll("SHOW COLUMNS FROM courses");
    
    echo "<table border='1' cellpadding='5' style='background:white;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        $highlight = ($col['Field'] === 'diagram_path') ? ' style="background:#d1fae5;"' : '';
        echo "<tr$highlight>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2 class='ok'>✅ Migration Complete!</h2>";
    echo "<p>The 'diagram_path' column has been successfully added to the courses table.</p>";
    echo "<p><strong>You can now generate diagrams!</strong></p>";
    echo "<p><a href='analytics.php'>Go to Analytics</a> | <a href='courses.php'>View Courses</a></p>";
    
} catch (PDOException $e) {
    echo "<h2 class='error'>❌ Migration Failed</h2>";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>