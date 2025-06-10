<?php
// backend/api/normalize_enabled_final.php
// Run this script once to normalize all enabled values in the database

require_once '../classes/database.class.php';

$conection = new Database();
$conn = $conection->connect();

echo "<h1>Normalizing Enabled Values - Final Version</h1>";

try {
    // Start transaction
    $conn->beginTransaction();
    
    // First, let's see what we're dealing with
    echo "<h2>Current state of teacher_disciplines:</h2>";
    $sql = "
        SELECT 
            td.enabled,
            COUNT(*) as count,
            td.enabled IS NULL as is_null,
            td.enabled = '' as is_empty
        FROM teacher_disciplines td
        GROUP BY td.enabled
        ORDER BY count DESC
    ";
    
    $stmt = $conn->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Value</th><th>Count</th><th>Is NULL</th><th>Is Empty</th><th>Type</th></tr>";
    foreach ($results as $row) {
        $value = $row['enabled'];
        $display = $value === null ? 'NULL' : ($value === '' ? 'EMPTY STRING' : htmlspecialchars($value));
        echo "<tr>";
        echo "<td>$display</td>";
        echo "<td>{$row['count']}</td>";
        echo "<td>" . ($row['is_null'] ? 'YES' : 'NO') . "</td>";
        echo "<td>" . ($row['is_empty'] ? 'YES' : 'NO') . "</td>";
        echo "<td>" . gettype($value) . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Normalize values
    echo "<h2>Normalizing values...</h2>";
    
    // Convert string '1' to integer 1
    $sql = "UPDATE teacher_disciplines SET enabled = 1 WHERE enabled = '1'";
    $count = $conn->exec($sql);
    echo "Updated $count records from '1' to 1<br>";
    
    // Convert string '0' to integer 0
    $sql = "UPDATE teacher_disciplines SET enabled = 0 WHERE enabled = '0'";
    $count = $conn->exec($sql);
    echo "Updated $count records from '0' to 0<br>";
    
    // Convert empty strings to NULL
    $sql = "UPDATE teacher_disciplines SET enabled = NULL WHERE enabled = ''";
    $count = $conn->exec($sql);
    echo "Updated $count records from empty string to NULL<br>";
    
    // Convert any other non-null, non-0, non-1 values to NULL
    $sql = "UPDATE teacher_disciplines SET enabled = NULL WHERE enabled IS NOT NULL AND enabled != 0 AND enabled != 1";
    $count = $conn->exec($sql);
    echo "Updated $count records with unexpected values to NULL<br>";
    
    // Do the same for postg_teacher_disciplines
    echo "<h2>Normalizing postg_teacher_disciplines...</h2>";
    
    $sql = "UPDATE postg_teacher_disciplines SET enabled = 1 WHERE enabled = '1'";
    $count = $conn->exec($sql);
    echo "Updated $count records from '1' to 1<br>";
    
    $sql = "UPDATE postg_teacher_disciplines SET enabled = 0 WHERE enabled = '0'";
    $count = $conn->exec($sql);
    echo "Updated $count records from '0' to 0<br>";
    
    $sql = "UPDATE postg_teacher_disciplines SET enabled = NULL WHERE enabled = ''";
    $count = $conn->exec($sql);
    echo "Updated $count records from empty string to NULL<br>";
    
    $sql = "UPDATE postg_teacher_disciplines SET enabled = NULL WHERE enabled IS NOT NULL AND enabled != 0 AND enabled != 1";
    $count = $conn->exec($sql);
    echo "Updated $count records with unexpected values to NULL<br>";
    
    // Show results after normalization
    echo "<h2>After normalization:</h2>";
    $sql = "
        SELECT 
            td.enabled,
            COUNT(*) as count
        FROM teacher_disciplines td
        GROUP BY td.enabled
        ORDER BY td.enabled
    ";
    
    $stmt = $conn->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Value</th><th>Count</th><th>Type</th></tr>";
    foreach ($results as $row) {
        $value = $row['enabled'];
        $display = $value === null ? 'NULL' : $value;
        echo "<tr>";
        echo "<td>$display</td>";
        echo "<td>{$row['count']}</td>";
        echo "<td>" . gettype($value) . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Commit transaction
    $conn->commit();
    echo "<h2 style='color: green;'>✓ Normalization completed successfully!</h2>";
    
    // Recommendations
    echo "<h2>Recommendations:</h2>";
    echo "<ol>";
    echo "<li>Ensure the column type is TINYINT(1) or INT for consistency</li>";
    echo "<li>Update your application code to always use integer values (0, 1, or NULL)</li>";
    echo "<li>Consider adding a CHECK constraint to ensure only 0, 1, or NULL values are allowed</li>";
    echo "</ol>";
    
    echo "<h3>SQL to update column types (if needed):</h3>";
    echo "<pre style='background: #f0f0f0; padding: 10px;'>";
    echo "ALTER TABLE teacher_disciplines MODIFY COLUMN enabled TINYINT(1) DEFAULT NULL;
ALTER TABLE postg_teacher_disciplines MODIFY COLUMN enabled TINYINT(1) DEFAULT NULL;

-- Optional: Add check constraints (MySQL 8.0.16+)
ALTER TABLE teacher_disciplines 
ADD CONSTRAINT chk_enabled_values 
CHECK (enabled IN (0, 1) OR enabled IS NULL);

ALTER TABLE postg_teacher_disciplines 
ADD CONSTRAINT chk_enabled_values 
CHECK (enabled IN (0, 1) OR enabled IS NULL);";
    echo "</pre>";
    
} catch (Exception $e) {
    $conn->rollBack();
    echo "<h2 style='color: red;'>✗ Error during normalization: " . htmlspecialchars($e->getMessage()) . "</h2>";
}
?>