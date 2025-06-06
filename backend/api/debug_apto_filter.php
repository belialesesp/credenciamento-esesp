<?php
// backend/api/debug_apto_filter.php
require_once '../classes/database.class.php';

$conection = new Database();
$conn = $conection->connect();

echo "<h2>Debugging 'Apto' Filter (enabled = 1)</h2>";

// 1. Check how many disciplines have enabled = 1
echo "<h3>1. Disciplines with enabled = 1:</h3>";
$sql = "
    SELECT 
        td.teacher_id,
        t.name as teacher_name,
        d.name as discipline_name,
        td.enabled,
        td.created_at
    FROM teacher_disciplines td
    INNER JOIN teacher t ON t.id = td.teacher_id
    INNER JOIN disciplinas d ON d.id = td.discipline_id
    WHERE td.enabled = 1
    ORDER BY t.name, d.name
";

$stmt = $conn->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Found " . count($results) . " disciplines with enabled = 1</p>";
echo "<table border='1'>";
echo "<tr><th>Teacher ID</th><th>Teacher Name</th><th>Discipline</th><th>Enabled</th><th>Created At</th></tr>";
foreach ($results as $row) {
    echo "<tr>";
    echo "<td>" . $row['teacher_id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['teacher_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['discipline_name']) . "</td>";
    echo "<td>" . $row['enabled'] . "</td>";
    echo "<td>" . $row['created_at'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Check what the filter query returns
echo "<h3>2. Testing the filter query (simulating status='1'):</h3>";
$sql = "SELECT DISTINCT t.* FROM teacher t
        INNER JOIN teacher_disciplines td ON t.id = td.teacher_id
        WHERE td.enabled = 1
        ORDER BY t.created_at ASC";

$stmt = $conn->query($sql);
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Found " . count($teachers) . " teachers with at least one discipline enabled = 1</p>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th></tr>";
foreach ($teachers as $teacher) {
    echo "<tr>";
    echo "<td>" . $teacher['id'] . "</td>";
    echo "<td>" . htmlspecialchars($teacher['name']) . "</td>";
    echo "<td>" . htmlspecialchars($teacher['email']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Test the exact query from get_filtered_teachers.php
echo "<h3>3. Testing exact discipline query for teacher 24 (Alef):</h3>";
$discSql = "
    SELECT 
        d.id,
        d.name,
        td.enabled
    FROM teacher_disciplines td
    INNER JOIN disciplinas d ON td.discipline_id = d.id
    WHERE td.teacher_id = 24 AND td.enabled = 1
    ORDER BY d.name
";

$stmt = $conn->query($discSql);
$disciplines = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Found " . count($disciplines) . " disciplines for teacher 24 with enabled = 1</p>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Name</th><th>Enabled</th><th>Enabled Type</th></tr>";
foreach ($disciplines as $disc) {
    echo "<tr>";
    echo "<td>" . $disc['id'] . "</td>";
    echo "<td>" . htmlspecialchars($disc['name']) . "</td>";
    echo "<td>" . $disc['enabled'] . "</td>";
    echo "<td>" . gettype($disc['enabled']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Check data types
echo "<h3>4. Checking data types in results:</h3>";
$sql = "SELECT td.enabled, td.teacher_id, td.discipline_id 
        FROM teacher_disciplines td 
        WHERE td.teacher_id = 24 
        LIMIT 10";
$stmt = $conn->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>Teacher ID</th><th>Discipline ID</th><th>Enabled Value</th><th>PHP Type</th><th>Is === 1?</th><th>Is == 1?</th><th>Is == '1'?</th></tr>";
foreach ($results as $row) {
    $val = $row['enabled'];
    echo "<tr>";
    echo "<td>" . $row['teacher_id'] . "</td>";
    echo "<td>" . $row['discipline_id'] . "</td>";
    echo "<td>" . var_export($val, true) . "</td>";
    echo "<td>" . gettype($val) . "</td>";
    echo "<td>" . ($val === 1 ? 'YES' : 'NO') . "</td>";
    echo "<td>" . ($val == 1 ? 'YES' : 'NO') . "</td>";
    echo "<td>" . ($val == '1' ? 'YES' : 'NO') . "</td>";
    echo "</tr>";
}
echo "</table>";

// 5. Test the filter with different comparisons
echo "<h3>5. Testing different comparison methods:</h3>";
$comparisons = [
    "td.enabled = 1" => "Integer comparison",
    "td.enabled = '1'" => "String comparison",
    "CAST(td.enabled AS SIGNED) = 1" => "Cast to signed",
    "td.enabled = TRUE" => "Boolean comparison",
    "td.enabled <> 0 AND td.enabled IS NOT NULL" => "Not zero and not null"
];

foreach ($comparisons as $comparison => $description) {
    $sql = "SELECT COUNT(*) as count FROM teacher_disciplines td WHERE $comparison";
    $stmt = $conn->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>$description (<code>$comparison</code>): " . $result['count'] . " records</p>";
}
?>