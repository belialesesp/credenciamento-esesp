<?php
// backend/api/check_enabled_values.php
require_once '../classes/database.class.php';

$conection = new Database();
$conn = $conection->connect();

echo "<h1>Checking Enabled Values in teacher_disciplines</h1>";

// 1. Check distinct values
echo "<h2>1. Distinct enabled values:</h2>";
$sql = "
    SELECT 
        td.enabled,
        COUNT(*) as count,
        td.enabled IS NULL as is_null,
        td.enabled = '' as is_empty_string,
        td.enabled = 0 as equals_zero_int,
        td.enabled = '0' as equals_zero_string,
        td.enabled = 1 as equals_one_int,
        td.enabled = '1' as equals_one_string,
        LENGTH(td.enabled) as length
    FROM teacher_disciplines td
    GROUP BY td.enabled
    ORDER BY count DESC
";

$stmt = $conn->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>Value</th><th>Count</th><th>Is NULL</th><th>Is Empty</th><th>= 0 (int)</th><th>= '0'</th><th>= 1 (int)</th><th>= '1'</th><th>Length</th><th>Type</th></tr>";
foreach ($results as $row) {
    $value = $row['enabled'];
    $display = $value === null ? 'NULL' : ($value === '' ? 'EMPTY' : htmlspecialchars($value));
    echo "<tr>";
    echo "<td>$display</td>";
    echo "<td>{$row['count']}</td>";
    echo "<td>" . ($row['is_null'] ? 'YES' : 'NO') . "</td>";
    echo "<td>" . ($row['is_empty_string'] ? 'YES' : 'NO') . "</td>";
    echo "<td>" . ($row['equals_zero_int'] ? 'YES' : 'NO') . "</td>";
    echo "<td>" . ($row['equals_zero_string'] ? 'YES' : 'NO') . "</td>";
    echo "<td>" . ($row['equals_one_int'] ? 'YES' : 'NO') . "</td>";
    echo "<td>" . ($row['equals_one_string'] ? 'YES' : 'NO') . "</td>";
    echo "<td>{$row['length']}</td>";
    echo "<td>" . gettype($value) . "</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Test filtering queries
echo "<h2>2. Test filtering queries:</h2>";

$tests = [
    "NULL or empty" => "(td.enabled IS NULL OR td.enabled = '')",
    "Equals 1" => "(td.enabled = 1 OR td.enabled = '1')",
    "Equals 0" => "(td.enabled = 0 OR td.enabled = '0')",
    "Not 1 and not 0" => "(td.enabled != 1 AND td.enabled != '1' AND td.enabled != 0 AND td.enabled != '0')"
];

foreach ($tests as $label => $condition) {
    $sql = "SELECT COUNT(*) as count FROM teacher_disciplines td WHERE $condition";
    $result = $conn->query($sql)->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>$label:</strong> {$result['count']} records</p>";
}

// 3. Show some actual examples
echo "<h2>3. Sample records for each type:</h2>";

$sql = "
    SELECT 
        t.name as teacher_name,
        d.name as discipline_name,
        td.enabled,
        td.enabled IS NULL as is_null,
        td.enabled = '' as is_empty
    FROM teacher_disciplines td
    INNER JOIN teacher t ON t.id = td.teacher_id
    INNER JOIN disciplinas d ON d.id = td.discipline_id
    WHERE td.enabled IS NULL OR td.enabled = ''
    LIMIT 5
";

echo "<h3>NULL or Empty enabled:</h3>";
$stmt = $conn->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>Teacher</th><th>Discipline</th><th>Enabled</th><th>Is NULL</th><th>Is Empty</th></tr>";
foreach ($results as $row) {
    $display = $row['enabled'] === null ? 'NULL' : ($row['enabled'] === '' ? 'EMPTY' : $row['enabled']);
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['teacher_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['discipline_name']) . "</td>";
    echo "<td>$display</td>";
    echo "<td>" . ($row['is_null'] ? 'YES' : 'NO') . "</td>";
    echo "<td>" . ($row['is_empty'] ? 'YES' : 'NO') . "</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Column information
echo "<h2>4. Column Definition:</h2>";
$sql = "SHOW COLUMNS FROM teacher_disciplines WHERE Field = 'enabled'";
$stmt = $conn->query($sql);
$column = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>" . print_r($column, true) . "</pre>";
?>