<?php
// backend/api/test_null_filter.php
require_once '../classes/database.class.php';

$conection = new Database();
$conn = $conection->connect();

echo "<h1>Testing NULL Filter Issue</h1>";

// Test teacher ID 24 (Alef) as example
$teacherId = 24;

echo "<h2>All disciplines for Teacher 24:</h2>";
$sql = "
    SELECT 
        d.id,
        d.name,
        td.enabled,
        CASE
            WHEN td.enabled IS NULL THEN 'IS NULL'
            WHEN td.enabled = '' THEN 'EMPTY STRING'
            WHEN td.enabled = 0 THEN 'INT ZERO'
            WHEN td.enabled = '0' THEN 'STRING ZERO'
            WHEN td.enabled = 1 THEN 'INT ONE'
            WHEN td.enabled = '1' THEN 'STRING ONE'
            ELSE CONCAT('OTHER: ', td.enabled)
        END as status_type
    FROM teacher_disciplines td
    INNER JOIN disciplinas d ON td.discipline_id = d.id
    WHERE td.teacher_id = :teacher_id
    ORDER BY d.name
";

$stmt = $conn->prepare($sql);
$stmt->execute([':teacher_id' => $teacherId]);
$allDisciplines = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Name</th><th>Enabled</th><th>Status Type</th></tr>";
foreach ($allDisciplines as $disc) {
    echo "<tr>";
    echo "<td>{$disc['id']}</td>";
    echo "<td>" . htmlspecialchars($disc['name']) . "</td>";
    echo "<td>" . var_export($disc['enabled'], true) . "</td>";
    echo "<td>{$disc['status_type']}</td>";
    echo "</tr>";
}
echo "</table>";

// Test different filter conditions
echo "<h2>Testing filter conditions on Teacher 24:</h2>";

$filterTests = [
    "NULL filter: (td.enabled IS NULL)" => "td.enabled IS NULL",
    "Empty filter: (td.enabled = '')" => "td.enabled = ''",
    "NULL OR Empty: (td.enabled IS NULL OR td.enabled = '')" => "(td.enabled IS NULL OR td.enabled = '')",
    "Zero int: (td.enabled = 0)" => "td.enabled = 0",
    "Zero string: (td.enabled = '0')" => "td.enabled = '0'",
    "One int: (td.enabled = 1)" => "td.enabled = 1",
    "One string: (td.enabled = '1')" => "td.enabled = '1'"
];

foreach ($filterTests as $label => $condition) {
    $sql = "
        SELECT COUNT(*) as count, GROUP_CONCAT(d.name SEPARATOR ', ') as disciplines
        FROM teacher_disciplines td
        INNER JOIN disciplinas d ON td.discipline_id = d.id
        WHERE td.teacher_id = :teacher_id AND $condition
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':teacher_id' => $teacherId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>$label</h3>";
    echo "<p>Count: {$result['count']}</p>";
    if ($result['count'] > 0) {
        echo "<p>Disciplines: " . htmlspecialchars($result['disciplines']) . "</p>";
    }
}

// Check if 0 values are being caught by NULL check
echo "<h2>Specific test: Does NULL condition catch 0 values?</h2>";
$sql = "
    SELECT 
        d.name,
        td.enabled,
        td.enabled IS NULL as check_is_null,
        td.enabled = '' as check_is_empty,
        td.enabled = 0 as check_is_zero
    FROM teacher_disciplines td
    INNER JOIN disciplinas d ON td.discipline_id = d.id
    WHERE td.teacher_id = :teacher_id 
    AND td.enabled = 0
";

$stmt = $conn->prepare($sql);
$stmt->execute([':teacher_id' => $teacherId]);
$zeroRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Disciplines with enabled = 0:</p>";
echo "<table border='1'>";
echo "<tr><th>Name</th><th>Enabled</th><th>IS NULL?</th><th>= ''?</th><th>= 0?</th></tr>";
foreach ($zeroRecords as $record) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($record['name']) . "</td>";
    echo "<td>" . var_export($record['enabled'], true) . "</td>";
    echo "<td>" . ($record['check_is_null'] ? 'YES' : 'NO') . "</td>";
    echo "<td>" . ($record['check_is_empty'] ? 'YES' : 'NO') . "</td>";
    echo "<td>" . ($record['check_is_zero'] ? 'YES' : 'NO') . "</td>";
    echo "</tr>";
}
echo "</table>";
?>