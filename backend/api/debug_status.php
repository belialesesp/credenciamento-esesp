<?php
// debug_status.php - Place this in your backend/api/ folder and run it
require_once '../classes/database.class.php';

$conection = new Database();
$conn = $conection->connect();

echo "<h2>Debugging Discipline Status Values</h2>";

// Check distinct status values
$sql = "SELECT DISTINCT enabled, COUNT(*) as count 
        FROM teacher_disciplines 
        GROUP BY enabled 
        ORDER BY enabled";

$stmt = $conn->prepare($sql);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Distinct status values in teacher_disciplines table:</h3>";
echo "<table border='1'>";
echo "<tr><th>Status Value</th><th>Type</th><th>Count</th></tr>";
foreach ($results as $row) {
    $value = $row['enabled'];
    $type = gettype($value);
    $display = $value === null ? 'NULL' : ($value === '' ? 'EMPTY STRING' : $value);
    echo "<tr>";
    echo "<td>" . htmlspecialchars($display) . "</td>";
    echo "<td>" . $type . "</td>";
    echo "<td>" . $row['count'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Sample some actual records
echo "<h3>Sample records:</h3>";
$sql = "SELECT t.name, d.name as discipline, td.enabled, 
        CASE 
            WHEN td.enabled IS NULL THEN 'IS NULL'
            WHEN td.enabled = '' THEN 'EMPTY STRING'
            WHEN td.enabled = '0' THEN 'STRING ZERO'
            WHEN td.enabled = 0 THEN 'INT ZERO'
            WHEN td.enabled = '1' THEN 'STRING ONE'
            WHEN td.enabled = 1 THEN 'INT ONE'
            ELSE CONCAT('OTHER: ', td.enabled)
        END as status_type
        FROM teacher_disciplines td
        JOIN teacher t ON t.id = td.teacher_id
        JOIN disciplinas d ON d.id = td.discipline_id
        LIMIT 20";

$stmt = $conn->prepare($sql);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>Teacher</th><th>Discipline</th><th>Status</th><th>Status Type</th></tr>";
foreach ($results as $row) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['discipline']) . "</td>";
    echo "<td>" . ($row['enabled'] === null ? 'NULL' : htmlspecialchars($row['enabled'])) . "</td>";
    echo "<td>" . htmlspecialchars($row['status_type']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check the column type
$sql = "SHOW COLUMNS FROM teacher_disciplines WHERE Field = 'enabled'";
$stmt = $conn->prepare($sql);
$stmt->execute();
$column = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>Column Definition:</h3>";
echo "<pre>";
print_r($column);
echo "</pre>";
?>