<?php
// verify_data_format.php - Place in backend/api/ to check data format

require_once '../classes/database.class.php';
require_once 'get_registers.php';

header('Content-Type: text/html; charset=utf-8');

$conn = (new Database())->connect();

echo "<h1>Verify Data Format</h1>";

// 1. Check raw data from get_docente()
echo "<h2>1. Data from get_docente():</h2>";
$teachers = get_docente($conn);

// Show first 3 teachers
for ($i = 0; $i < min(3, count($teachers)); $i++) {
    $teacher = $teachers[$i];
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0; font-family: monospace;'>";
    echo "<strong>Teacher: " . htmlspecialchars($teacher['name']) . "</strong><br>";
    echo "Raw discipline_statuses: <code>" . htmlspecialchars($teacher['discipline_statuses'] ?: 'NULL') . "</code><br>";
    
    if ($teacher['discipline_statuses']) {
        echo "<br>Parsing with |~~| and |~| delimiters:<br>";
        $groups = explode('|~~|', $teacher['discipline_statuses']);
        foreach ($groups as $idx => $group) {
            echo "Group $idx: <code>" . htmlspecialchars($group) . "</code><br>";
            $parts = explode('|~|', $group);
            echo "Parts: ";
            foreach ($parts as $j => $part) {
                echo "[$j]=" . htmlspecialchars($part) . " ";
            }
            echo "<br>";
        }
    }
    echo "</div>";
}

// 2. Check filtered data
echo "<h2>2. Data from get_filtered_teachers.php (no filter):</h2>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/seu-projeto/backend/api/get_filtered_teachers.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

if ($response) {
    $filteredData = json_decode($response, true);
    if ($filteredData && count($filteredData) > 0) {
        $teacher = $filteredData[0];
        echo "<div style='background: #e0f0e0; padding: 10px; margin: 10px 0; font-family: monospace;'>";
        echo "<strong>First teacher from API:</strong><br>";
        echo "Name: " . htmlspecialchars($teacher['name']) . "<br>";
        echo "discipline_statuses: <code>" . htmlspecialchars($teacher['discipline_statuses'] ?: 'NULL') . "</code><br>";
        echo "</div>";
    }
}

// 3. Check actual database values
echo "<h2>3. Direct database check:</h2>";
$sql = "
    SELECT 
        t.id,
        t.name,
        COUNT(td.discipline_id) as discipline_count,
        GROUP_CONCAT(
            CONCAT('ID:', d.id, ' Name:', d.name, ' Status:', COALESCE(td.enabled, 'NULL'))
            SEPARATOR ' | '
        ) as disciplines_info
    FROM teacher t
    LEFT JOIN teacher_disciplines td ON t.id = td.teacher_id
    LEFT JOIN disciplinas d ON td.discipline_id = d.id
    WHERE t.id IN (SELECT id FROM teacher LIMIT 3)
    GROUP BY t.id
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
    echo "<div style='background: #e0e0f0; padding: 10px; margin: 10px 0;'>";
    echo "<strong>" . htmlspecialchars($row['name']) . "</strong><br>";
    echo "Discipline count: " . $row['discipline_count'] . "<br>";
    echo "Info: " . htmlspecialchars($row['disciplines_info'] ?: 'No disciplines') . "<br>";
    echo "</div>";
}

// 4. Check for problematic data
echo "<h2>4. Check for problematic entries:</h2>";
$sql = "
    SELECT 
        t.id,
        t.name,
        td.discipline_id,
        d.name as disc_name,
        td.enabled,
        HEX(td.enabled) as hex_enabled
    FROM teacher t
    LEFT JOIN teacher_disciplines td ON t.id = td.teacher_id
    LEFT JOIN disciplinas d ON td.discipline_id = d.id
    WHERE d.id = 0 OR d.id IS NULL OR d.name LIKE '%|%' OR d.name LIKE '%~%'
    LIMIT 10
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$problematic = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($problematic) {
    echo "<p style='color: red;'>Found problematic entries:</p>";
    foreach ($problematic as $row) {
        echo "<div style='background: #ffe0e0; padding: 5px; margin: 5px 0;'>";
        echo "Teacher: " . htmlspecialchars($row['name']) . ", ";
        echo "Disc ID: " . $row['discipline_id'] . ", ";
        echo "Disc Name: " . htmlspecialchars($row['disc_name'] ?: 'NULL') . ", ";
        echo "Enabled: " . var_export($row['enabled'], true) . "<br>";
        echo "</div>";
    }
} else {
    echo "<p style='color: green;'>No problematic entries found.</p>";
}
?>