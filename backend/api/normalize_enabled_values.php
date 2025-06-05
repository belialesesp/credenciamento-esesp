<?php
// backend/api/normalize_enabled_values.php
// Run this script to normalize all enabled values in the database

require_once '../classes/database.class.php';

$conection = new Database();
$conn = $conection->connect();

echo "<h2>Normalizing Enabled Values</h2>";

try {
    // Start transaction
    $conn->beginTransaction();
    
    // First, let's see what we're dealing with
    $sql = "SELECT DISTINCT enabled, COUNT(*) as count FROM teacher_disciplines GROUP BY enabled";
    $stmt = $conn->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Current values in teacher_disciplines:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Value</th><th>Type</th><th>Count</th></tr>";
    foreach ($results as $row) {
        $value = $row['enabled'];
        $display = $value === null ? 'NULL' : ($value === '' ? 'EMPTY STRING' : $value);
        echo "<tr><td>" . htmlspecialchars($display) . "</td><td>" . gettype($value) . "</td><td>" . $row['count'] . "</td></tr>";
    }
    echo "</table><br>";
    
    // Normalize values
    echo "<h3>Normalizing values...</h3>";
    
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
    
    // Do the same for postg_teacher_disciplines
    echo "<h3>Normalizing postg_teacher_disciplines...</h3>";
    
    $sql = "UPDATE postg_teacher_disciplines SET enabled = 1 WHERE enabled = '1'";
    $count = $conn->exec($sql);
    echo "Updated $count records from '1' to 1<br>";
    
    $sql = "UPDATE postg_teacher_disciplines SET enabled = 0 WHERE enabled = '0'";
    $count = $conn->exec($sql);
    echo "Updated $count records from '0' to 0<br>";
    
    $sql = "UPDATE postg_teacher_disciplines SET enabled = NULL WHERE enabled = ''";
    $count = $conn->exec($sql);
    echo "Updated $count records from empty string to NULL<br>";
    
    // Show results
    echo "<h3>After normalization:</h3>";
    $sql = "SELECT DISTINCT enabled, COUNT(*) as count FROM teacher_disciplines GROUP BY enabled";
    $stmt = $conn->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Value</th><th>Type</th><th>Count</th></tr>";
    foreach ($results as $row) {
        $value = $row['enabled'];
        $display = $value === null ? 'NULL' : $value;
        echo "<tr><td>" . htmlspecialchars($display) . "</td><td>" . gettype($value) . "</td><td>" . $row['count'] . "</td></tr>";
    }
    echo "</table><br>";
    
    // Commit transaction
    $conn->commit();
    echo "<h3 style='color: green;'>Normalization completed successfully!</h3>";
    
} catch (Exception $e) {
    $conn->rollBack();
    echo "<h3 style='color: red;'>Error during normalization: " . $e->getMessage() . "</h3>";
}

// Add recommendation for column type
echo "<h3>Recommendation:</h3>";
echo "<p>Consider altering the column type to ensure consistency:</p>";
echo "<pre>";
echo "ALTER TABLE teacher_disciplines MODIFY COLUMN enabled TINYINT(1) DEFAULT NULL;
ALTER TABLE postg_teacher_disciplines MODIFY COLUMN enabled TINYINT(1) DEFAULT NULL;";
echo "</pre>";
?>