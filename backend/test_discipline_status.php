<?php
// backend/test_discipline_status.php - Run this to check discipline statuses

require_once 'classes/database.class.php';

$teacher_id = $_GET['teacher_id'] ?? 283; // Use the teacher ID from your session

echo "<h2>Testing Discipline Status for Teacher ID: $teacher_id</h2>";

try {
    $connection = new Database();
    $conn = $connection->connect();
    
    // Query 1: Check raw data in teacher_disciplines
    echo "<h3>1. Raw data from teacher_disciplines table:</h3>";
    $stmt = $conn->prepare("
        SELECT td.*, d.name as discipline_name 
        FROM teacher_disciplines td
        JOIN disciplinas d ON td.discipline_id = d.id
        WHERE td.teacher_id = :teacher_id
    ");
    $stmt->execute([':teacher_id' => $teacher_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Teacher ID</th><th>Discipline ID</th><th>Discipline Name</th><th>Enabled</th><th>Enabled Type</th></tr>";
    foreach ($results as $row) {
        $enabled_type = gettype($row['enabled']);
        echo "<tr>";
        echo "<td>{$row['teacher_id']}</td>";
        echo "<td>{$row['discipline_id']}</td>";
        echo "<td>{$row['discipline_name']}</td>";
        echo "<td>" . var_export($row['enabled'], true) . "</td>";
        echo "<td>$enabled_type</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Query 2: Test the actual query from teacher.service.php
    echo "<h3>2. Testing the service query:</h3>";
    $sql = "
    SELECT 
        d.id AS discipline_id,
        d.name AS discipline_name,
        dt.enabled AS discipline_status
    FROM 
        teacher_disciplines AS dt
    LEFT JOIN disciplinas AS d ON dt.discipline_id = d.id
    WHERE dt.teacher_id = :teacher_id
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':teacher_id' => $teacher_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($results);
    echo "</pre>";
    
    // Query 3: Check column type
    echo "<h3>3. Column structure of teacher_disciplines:</h3>";
    $stmt = $conn->query("SHOW COLUMNS FROM teacher_disciplines");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Query 4: Check distinct enabled values
    echo "<h3>4. Distinct 'enabled' values in the system:</h3>";
    $stmt = $conn->query("SELECT DISTINCT enabled, COUNT(*) as count FROM teacher_disciplines GROUP BY enabled");
    $values = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Enabled Value</th><th>Count</th><th>Type</th></tr>";
    foreach ($values as $val) {
        echo "<tr>";
        echo "<td>" . var_export($val['enabled'], true) . "</td>";
        echo "<td>{$val['count']}</td>";
        echo "<td>" . gettype($val['enabled']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='docente.php?id=$teacher_id'>Back to Teacher Profile</a></p>";
?>