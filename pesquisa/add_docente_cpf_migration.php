<?php
/**
 * Database Migration: Add docente_cpf to courses table
 * This script adds the docente_cpf column to allow filtering courses by docente CPF
 */

// Change this to match your database connection file
require_once __DIR__ . '/includes/init.php';

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Migration: Add Docente CPF Column</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1e3a5f;
            border-bottom: 3px solid #2c5f8d;
            padding-bottom: 10px;
        }
        .success {
            color: #10b981;
            background: #d1fae5;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            color: #ef4444;
            background: #fee2e2;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            color: #3b82f6;
            background: #dbeafe;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .warning {
            color: #f59e0b;
            background: #fef3c7;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        pre {
            background: #1f2937;
            color: #f9fafb;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .step {
            margin: 20px 0;
            padding-left: 20px;
            border-left: 3px solid #2c5f8d;
        }
    </style>
</head>
<body>
<div class='container'>
<h1>🔧 Database Migration: Add Docente CPF Column</h1>
";

try {
    $db = PesquisaDatabase::getInstance();
    
    echo "<div class='step'>";
    echo "<h2>Step 1: Checking current table structure</h2>";
    
    // Check if column already exists
    $columns = $db->fetchAll("SHOW COLUMNS FROM courses LIKE 'docente_cpf'");
    
    if (!empty($columns)) {
        echo "<div class='warning'>";
        echo "⚠️ Column <code>docente_cpf</code> already exists in the courses table.";
        echo "</div>";
        echo "<p>No migration needed. The column structure is already up to date.</p>";
    } else {
        echo "<div class='info'>";
        echo "✓ Column <code>docente_cpf</code> does not exist. Proceeding with migration...";
        echo "</div>";
        echo "</div>";
        
        // Add the column
        echo "<div class='step'>";
        echo "<h2>Step 2: Adding docente_cpf column</h2>";
        
        $sql = "ALTER TABLE courses 
                ADD COLUMN docente_cpf VARCHAR(11) DEFAULT NULL AFTER docente_name,
                ADD INDEX idx_docente_cpf (docente_cpf)";
        
        echo "<p>Executing SQL:</p>";
        echo "<pre>$sql</pre>";
        
        $db->query($sql);
        
        echo "<div class='success'>";
        echo "✅ Successfully added <code>docente_cpf</code> column to courses table!";
        echo "</div>";
        echo "</div>";
        
        // Verify the change
        echo "<div class='step'>";
        echo "<h2>Step 3: Verifying changes</h2>";
        
        $columns = $db->fetchAll("SHOW COLUMNS FROM courses");
        
        echo "<p>Current columns in <code>courses</code> table:</p>";
        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
        echo "<tr style='background: #f3f4f6;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        foreach ($columns as $column) {
            $highlight = ($column['Field'] === 'docente_cpf') ? "style='background: #d1fae5;'" : "";
            echo "<tr $highlight>";
            echo "<td><strong>" . htmlspecialchars($column['Field']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div class='success' style='margin-top: 20px;'>";
        echo "✅ Migration completed successfully!";
        echo "</div>";
        echo "</div>";
    }
    
    // Instructions for next steps
    echo "<div class='step'>";
    echo "<h2>Next Steps</h2>";
    echo "<ol>";
    echo "<li>The <code>docente_cpf</code> column has been added to the courses table</li>";
    echo "<li>You can now use the improved <code>courses.php</code> file with CPF and month filters</li>";
    echo "<li>When creating new courses, you can optionally include the docente CPF</li>";
    echo "<li>To populate existing courses with CPF data, you'll need to update them manually or create a data migration script</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>How to use the new filter:</h3>";
    echo "<ul>";
    echo "<li>Go to <code>courses.php</code></li>";
    echo "<li>You'll see a new 'CPF do Docente' filter field</li>";
    echo "<li>Enter the CPF (with or without formatting)</li>";
    echo "<li>Combine with other filters like category, month, and year</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Error during migration</h2>";
    echo "<p><strong>Error message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Stack trace:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>Troubleshooting:</h3>";
    echo "<ol>";
    echo "<li>Check your database connection settings</li>";
    echo "<li>Ensure you have ALTER TABLE privileges</li>";
    echo "<li>Verify that the courses table exists</li>";
    echo "<li>Check the database logs for more details</li>";
    echo "</ol>";
    echo "</div>";
}

echo "</div></body></html>";
?>