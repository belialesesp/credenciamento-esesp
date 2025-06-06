<?php
// backend/api/debug_get_filtered_teachers.php
require_once '../classes/database.class.php';

header('Content-Type: text/html; charset=utf-8');

$category = $_GET['category'] ?? '';
$course = $_GET['course'] ?? '';
$status = $_GET['status'] ?? '';

echo "<h1>Debug Filtered Teachers</h1>";
echo "<p>Filters: Category=$category, Course=$course, Status=$status</p>";

try {
    $conection = new Database();
    $conn = $conection->connect();
    
    // Test with a specific teacher (Alef - ID 24)
    $teacherId = 24;
    
    // Build the discipline query exactly as in get_filtered_teachers.php
    $discSql = "
        SELECT 
            d.id,
            d.name,
            td.enabled,
            CAST(td.enabled AS CHAR) as enabled_str
        FROM teacher_disciplines td
        INNER JOIN disciplinas d ON td.discipline_id = d.id
        WHERE td.teacher_id = :teacher_id
    ";
    
    $discParams = [':teacher_id' => $teacherId];
    
    // Apply course filter if set
    if ($course !== '') {
        $discSql .= " AND d.id = :course";
        $discParams[':course'] = $course;
    }
    
    // Apply status filter using CAST for safety
    if ($status !== '') {
        if ($status === '1') {
            $discSql .= " AND CAST(td.enabled AS SIGNED) = 1";
        } else if ($status === '0') {
            $discSql .= " AND CAST(td.enabled AS SIGNED) = 0";
        } else if ($status === 'null') {
            $discSql .= " AND td.enabled IS NULL";
        }
    }
    
    $discSql .= " ORDER BY d.name";
    
    echo "<h2>Query for Teacher 24 (Alef):</h2>";
    echo "<pre>" . htmlspecialchars($discSql) . "</pre>";
    echo "<p>Parameters: " . print_r($discParams, true) . "</p>";
    
    $discStmt = $conn->prepare($discSql);
    $discStmt->execute($discParams);
    $disciplines = $discStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Raw discipline data:</h2>";
    echo "<pre>" . print_r($disciplines, true) . "</pre>";
    
    // Build discipline statuses string
    $disciplineStatuses = [];
    foreach ($disciplines as $disc) {
        echo "<h3>Processing discipline:</h3>";
        echo "<pre>" . print_r($disc, true) . "</pre>";
        
        // Convert enabled to string consistently
        if ($disc['enabled'] === null) {
            $statusValue = 'null';
        } elseif ($disc['enabled'] == 1) {
            $statusValue = '1';
        } elseif ($disc['enabled'] == 0) {
            $statusValue = '0';
        } else {
            $statusValue = 'null';
        }
        
        $statusString = $disc['id'] . ':' . $disc['name'] . ':' . $statusValue;
        echo "<p>Status string: <code>" . htmlspecialchars($statusString) . "</code></p>";
        
        $disciplineStatuses[] = $statusString;
    }
    
    $finalString = implode('||', $disciplineStatuses);
    echo "<h2>Final discipline_statuses string:</h2>";
    echo "<pre>" . htmlspecialchars($finalString) . "</pre>";
    
    // Test parsing on frontend
    echo "<h2>Test Frontend Parsing:</h2>";
    ?>
    <div id="parseTest"></div>
    <script>
    const discipline_statuses = <?php echo json_encode($finalString); ?>;
    console.log('discipline_statuses:', discipline_statuses);
    
    let html = '<h3>JavaScript Parsing Test:</h3>';
    if (discipline_statuses) {
        const statusPairs = discipline_statuses.split('||');
        html += '<p>Number of pairs: ' + statusPairs.length + '</p>';
        
        statusPairs.forEach((pair, index) => {
            if (pair && pair.trim()) {
                html += '<div style="border: 1px solid #ccc; margin: 10px; padding: 10px;">';
                html += '<p><strong>Pair ' + index + ':</strong> <code>' + pair + '</code></p>';
                
                // Handle discipline names that may contain colons
                const firstColonIndex = pair.indexOf(':');
                const lastColonIndex = pair.lastIndexOf(':');
                
                html += '<p>First colon at: ' + firstColonIndex + ', Last colon at: ' + lastColonIndex + '</p>';
                
                if (firstColonIndex !== -1 && lastColonIndex !== -1 && firstColonIndex !== lastColonIndex) {
                    const discId = pair.substring(0, firstColonIndex);
                    const status = pair.substring(lastColonIndex + 1);
                    const discName = pair.substring(firstColonIndex + 1, lastColonIndex);
                    
                    html += '<p>Parsed ID: <code>' + discId + '</code></p>';
                    html += '<p>Parsed Name: <code>' + discName + '</code></p>';
                    html += '<p>Parsed Status: <code>' + status + '</code></p>';
                } else {
                    html += '<p style="color: red;">Failed to parse - not enough colons</p>';
                }
                html += '</div>';
            }
        });
    } else {
        html += '<p>No discipline_statuses data</p>';
    }
    
    document.getElementById('parseTest').innerHTML = html;
    </script>
    <?php
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>