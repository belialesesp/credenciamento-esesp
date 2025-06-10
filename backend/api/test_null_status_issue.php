<?php
// backend/api/test_null_status_issue.php
require_once '../classes/database.class.php';

$conection = new Database();
$conn = $conection->connect();

echo "<h1>Testing NULL Status Filter Issue</h1>";

// Check ALEF's disciplines specifically
$teacherId = 24; // ALEF NOGUEIRA DE LIMA

echo "<h2>All disciplines for ALEF (ID: 24):</h2>";
$sql = "
    SELECT 
        d.id,
        d.name,
        td.enabled,
        td.enabled IS NULL as is_null,
        td.enabled = '' as is_empty,
        HEX(td.enabled) as hex_value,
        LENGTH(td.enabled) as length
    FROM teacher_disciplines td
    INNER JOIN disciplinas d ON td.discipline_id = d.id
    WHERE td.teacher_id = :teacher_id
    ORDER BY d.name
";

$stmt = $conn->prepare($sql);
$stmt->execute([':teacher_id' => $teacherId]);
$disciplines = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Name</th><th>Enabled</th><th>Is NULL</th><th>Is Empty</th><th>Hex</th><th>Length</th></tr>";
foreach ($disciplines as $disc) {
    $enabledDisplay = $disc['enabled'] === null ? 'NULL' : 
                     ($disc['enabled'] === '' ? 'EMPTY' : 
                     var_export($disc['enabled'], true));
    echo "<tr>";
    echo "<td>{$disc['id']}</td>";
    echo "<td>" . htmlspecialchars($disc['name']) . "</td>";
    echo "<td>{$enabledDisplay}</td>";
    echo "<td>" . ($disc['is_null'] ? 'YES' : 'NO') . "</td>";
    echo "<td>" . ($disc['is_empty'] ? 'YES' : 'NO') . "</td>";
    echo "<td>{$disc['hex_value']}</td>";
    echo "<td>{$disc['length']}</td>";
    echo "</tr>";
}
echo "</table>";

// Test the NULL filter condition
echo "<h2>Testing NULL filter condition:</h2>";
$nullCondition = "(td.enabled IS NULL OR td.enabled = '' OR td.enabled IS NULL)";

$sql = "
    SELECT 
        d.id,
        d.name,
        td.enabled
    FROM teacher_disciplines td
    INNER JOIN disciplinas d ON td.discipline_id = d.id
    WHERE td.teacher_id = :teacher_id
    AND $nullCondition
    ORDER BY d.name
";

$stmt = $conn->prepare($sql);
$stmt->execute([':teacher_id' => $teacherId]);
$nullDisciplines = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Disciplines matching NULL condition: " . count($nullDisciplines) . "</p>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Name</th><th>Enabled</th></tr>";
foreach ($nullDisciplines as $disc) {
    $enabledDisplay = $disc['enabled'] === null ? 'NULL' : 
                     ($disc['enabled'] === '' ? 'EMPTY' : 
                     var_export($disc['enabled'], true));
    echo "<tr>";
    echo "<td>{$disc['id']}</td>";
    echo "<td>" . htmlspecialchars($disc['name']) . "</td>";
    echo "<td>{$enabledDisplay}</td>";
    echo "</tr>";
}
echo "</table>";

// Test what happens with different conditions
echo "<h2>Testing various filter conditions:</h2>";

$conditions = [
    "td.enabled IS NULL" => "IS NULL only",
    "td.enabled = ''" => "Empty string only",
    "td.enabled = 0" => "Zero (int)",
    "td.enabled = '0'" => "Zero (string)",
    "(td.enabled IS NULL OR td.enabled = '')" => "NULL OR empty",
    "(td.enabled != 1 AND td.enabled != '1' AND td.enabled != 0 AND td.enabled != '0')" => "Not 1 and not 0",
    "(td.enabled IS NULL OR td.enabled = '' OR (td.enabled != '0' AND td.enabled != '1' AND td.enabled != 0 AND td.enabled != 1))" => "NULL/empty or other values"
];

foreach ($conditions as $condition => $description) {
    $sql = "
        SELECT COUNT(*) as count, GROUP_CONCAT(d.name SEPARATOR ', ') as names
        FROM teacher_disciplines td
        INNER JOIN disciplinas d ON td.discipline_id = d.id
        WHERE td.teacher_id = :teacher_id
        AND $condition
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':teacher_id' => $teacherId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>$description</h3>";
    echo "<p>Condition: <code>$condition</code></p>";
    echo "<p>Count: {$result['count']}</p>";
    if ($result['count'] > 0) {
        echo "<p>Disciplines: " . htmlspecialchars($result['names']) . "</p>";
    }
}

// Check all unique enabled values in the database
echo "<h2>All unique enabled values in database:</h2>";
$sql = "
    SELECT DISTINCT 
        td.enabled,
        COUNT(*) as count,
        td.enabled IS NULL as is_null,
        td.enabled = '' as is_empty,
        td.enabled = 0 as is_zero_int,
        td.enabled = '0' as is_zero_string
    FROM teacher_disciplines td
    GROUP BY td.enabled
    ORDER BY count DESC
";

$stmt = $conn->query($sql);
$values = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>Value</th><th>Count</th><th>IS NULL</th><th>= ''</th><th>= 0</th><th>= '0'</th><th>Type</th></tr>";
foreach ($values as $val) {
    $display = $val['enabled'] === null ? 'NULL' : 
              ($val['enabled'] === '' ? 'EMPTY' : 
              var_export($val['enabled'], true));
    echo "<tr>";
    echo "<td>$display</td>";
    echo "<td>{$val['count']}</td>";
    echo "<td>" . ($val['is_null'] ? 'YES' : 'NO') . "</td>";
    echo "<td>" . ($val['is_empty'] ? 'YES' : 'NO') . "</td>";
    echo "<td>" . ($val['is_zero_int'] ? 'YES' : 'NO') . "</td>";
    echo "<td>" . ($val['is_zero_string'] ? 'YES' : 'NO') . "</td>";
    echo "<td>" . gettype($val['enabled']) . "</td>";
    echo "</tr>";
}
echo "</table>";
?>