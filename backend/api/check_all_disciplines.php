<?php
// backend/api/check_all_disciplines.php
require_once '../classes/database.class.php';

$conection = new Database();
$conn = $conection->connect();

echo "<h2>All Teacher Disciplines Status</h2>";

// 1. Summary of all statuses
echo "<h3>1. Summary of enabled values:</h3>";
$sql = "
    SELECT 
        td.enabled,
        COUNT(*) as count,
        COUNT(DISTINCT td.teacher_id) as teacher_count
    FROM teacher_disciplines td
    GROUP BY td.enabled
    ORDER BY td.enabled
";

$stmt = $conn->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>Enabled Value</th><th>Count</th><th>Number of Teachers</th></tr>";
foreach ($results as $row) {
    $display = $row['enabled'] === null ? 'NULL' : $row['enabled'];
    echo "<tr>";
    echo "<td>" . $display . "</td>";
    echo "<td>" . $row['count'] . "</td>";
    echo "<td>" . $row['teacher_count'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Teachers with enabled = 1
echo "<h3>2. All teachers with at least one discipline enabled = 1:</h3>";
$sql = "
    SELECT DISTINCT
        t.id,
        t.name,
        t.email,
        COUNT(CASE WHEN td.enabled = 1 THEN 1 END) as enabled_count,
        COUNT(td.discipline_id) as total_disciplines
    FROM teacher t
    INNER JOIN teacher_disciplines td ON t.id = td.teacher_id
    WHERE EXISTS (
        SELECT 1 FROM teacher_disciplines td2 
        WHERE td2.teacher_id = t.id AND td2.enabled = 1
    )
    GROUP BY t.id
    ORDER BY t.name
";

$stmt = $conn->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Found " . count($results) . " teachers with at least one enabled discipline</p>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Enabled Disciplines</th><th>Total Disciplines</th></tr>";
foreach ($results as $row) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
    echo "<td>" . $row['enabled_count'] . "</td>";
    echo "<td>" . $row['total_disciplines'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Check date filtering effect
echo "<h3>3. Check if date filtering affects results:</h3>";
$date = '2025-02-28';
$sql = "
    SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN td.created_at < :date THEN 1 END) as before_date,
        COUNT(CASE WHEN td.enabled = 1 THEN 1 END) as enabled_total,
        COUNT(CASE WHEN td.enabled = 1 AND td.created_at < :date THEN 1 END) as enabled_before_date
    FROM teacher_disciplines td
";

$stmt = $conn->prepare($sql);
$stmt->execute([':date' => $date]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<p>Total disciplines: " . $result['total'] . "</p>";
echo "<p>Disciplines created before $date: " . $result['before_date'] . "</p>";
echo "<p>Total enabled disciplines: " . $result['enabled_total'] . "</p>";
echo "<p>Enabled disciplines created before $date: " . $result['enabled_before_date'] . "</p>";

// 4. Show all enabled = 1 records with details
echo "<h3>4. All disciplines with enabled = 1:</h3>";
$sql = "
    SELECT 
        td.teacher_id,
        t.name as teacher_name,
        d.name as discipline_name,
        td.created_at,
        td.enabled
    FROM teacher_disciplines td
    INNER JOIN teacher t ON t.id = td.teacher_id
    INNER JOIN disciplinas d ON d.id = td.discipline_id
    WHERE td.enabled = 1
    ORDER BY td.created_at DESC
";

$stmt = $conn->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>Teacher</th><th>Discipline</th><th>Created At</th><th>Status</th></tr>";
foreach ($results as $row) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['teacher_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['discipline_name']) . "</td>";
    echo "<td>" . $row['created_at'] . "</td>";
    echo "<td>Apto</td>";
    echo "</tr>";
}
echo "</table>";
?>