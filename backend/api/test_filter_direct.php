<?php
// backend/api/test_filter_direct.php
require_once '../classes/database.class.php';

header('Content-Type: text/html; charset=utf-8');

$conection = new Database();
$conn = $conection->connect();

echo "<h1>Direct Test of Filter Query</h1>";

// Simulate the 'Aguardando' filter
$status = 'null';

echo "<h2>Testing with status = 'null' (Aguardando)</h2>";

// First, get teachers with at least one NULL/empty discipline
$sql = "SELECT DISTINCT t.* FROM teacher t
        INNER JOIN teacher_disciplines td ON t.id = td.teacher_id
        WHERE (td.enabled IS NULL OR td.enabled = '' OR td.enabled IS NULL)
        ORDER BY t.created_at ASC
        LIMIT 5";

$stmt = $conn->query($sql);
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Found " . count($teachers) . " teachers with NULL/empty disciplines</p>";

// For each teacher, get their filtered disciplines
foreach ($teachers as $teacher) {
    echo "<h3>Teacher: " . htmlspecialchars($teacher['name']) . " (ID: {$teacher['id']})</h3>";
    
    // Get ALL disciplines for comparison
    $allSql = "
        SELECT 
            d.id,
            d.name,
            td.enabled,
            CASE 
                WHEN td.enabled IS NULL THEN 'NULL'
                WHEN td.enabled = '' THEN 'EMPTY'
                WHEN td.enabled = 0 THEN '0'
                WHEN td.enabled = '0' THEN '0'
                WHEN td.enabled = 1 THEN '1'
                WHEN td.enabled = '1' THEN '1'
                ELSE CONCAT('OTHER:', td.enabled)
            END as status_display
        FROM teacher_disciplines td
        INNER JOIN disciplinas d ON td.discipline_id = d.id
        WHERE td.teacher_id = :teacher_id
        ORDER BY d.name
    ";
    
    $stmt = $conn->prepare($allSql);
    $stmt->execute([':teacher_id' => $teacher['id']]);
    $allDisciplines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>ALL disciplines:</h4>";
    echo "<table border='1'>";
    echo "<tr><th>Name</th><th>Enabled</th><th>Status</th></tr>";
    foreach ($allDisciplines as $disc) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($disc['name']) . "</td>";
        echo "<td>" . var_export($disc['enabled'], true) . "</td>";
        echo "<td>{$disc['status_display']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Get FILTERED disciplines (should only be NULL/empty)
    $filteredSql = "
        SELECT 
            d.id,
            d.name,
            td.enabled
        FROM teacher_disciplines td
        INNER JOIN disciplinas d ON td.discipline_id = d.id
        WHERE td.teacher_id = :teacher_id
        AND (td.enabled IS NULL OR td.enabled = '' OR td.enabled IS NULL)
        ORDER BY d.name
    ";
    
    $stmt = $conn->prepare($filteredSql);
    $stmt->execute([':teacher_id' => $teacher['id']]);
    $filteredDisciplines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>FILTERED disciplines (NULL/empty only):</h4>";
    echo "<table border='1'>";
    echo "<tr><th>Name</th><th>Enabled</th></tr>";
    foreach ($filteredDisciplines as $disc) {
        $display = $disc['enabled'] === null ? 'NULL' : 
                  ($disc['enabled'] === '' ? 'EMPTY' : var_export($disc['enabled'], true));
        echo "<tr>";
        echo "<td>" . htmlspecialchars($disc['name']) . "</td>";
        echo "<td>$display</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Build the status string as the API does
    echo "<h4>Status string for API:</h4>";
    $disciplineStatuses = [];
    foreach ($filteredDisciplines as $disc) {
        $cleanName = str_replace([':', '||'], ' ', $disc['name']);
        
        $enabledValue = $disc['enabled'];
        if ($enabledValue === null || $enabledValue === '') {
            $statusValue = 'null';
        } else if ($enabledValue == 0 && $enabledValue !== null && $enabledValue !== '') {
            $statusValue = '0';
        } else if ($enabledValue == 1) {
            $statusValue = '1';
        } else {
            $statusValue = 'null';
        }
        
        $disciplineStatuses[] = $disc['id'] . ':' . $cleanName . ':' . $statusValue;
    }
    
    $statusString = implode('||', $disciplineStatuses);
    echo "<p><code>" . htmlspecialchars($statusString) . "</code></p>";
    
    echo "<hr>";
}
?>