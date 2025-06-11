<?php
// check_discipline_status.php
// Place this in your backend/api/ directory to debug status issues

require_once '../classes/database.class.php';

header('Content-Type: text/html; charset=utf-8');

$conection = new Database();
$conn = $conection->connect();

// Get parameters
$teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : 0;

echo "<h1>Teacher Discipline Status Check</h1>";

if ($teacher_id > 0) {
    // Get teacher info
    $sql = "SELECT id, name, email FROM teacher WHERE id = :teacher_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':teacher_id' => $teacher_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($teacher) {
        echo "<h2>Teacher: " . htmlspecialchars($teacher['name']) . " (ID: " . $teacher['id'] . ")</h2>";
        
        // Get all disciplines for this teacher
        $sql = "
            SELECT 
                td.teacher_id,
                td.discipline_id,
                td.enabled,
                td.created_at,
                d.name as discipline_name,
                CASE 
                    WHEN td.enabled = '1' THEN 'Apto'
                    WHEN td.enabled = '0' THEN 'Inapto'
                    WHEN td.enabled IS NULL THEN 'NULL (Aguardando)'
                    WHEN td.enabled = '' THEN 'Empty String (Aguardando)'
                    ELSE CONCAT('Unknown: ', td.enabled)
                END as status_text,
                HEX(td.enabled) as hex_value
            FROM teacher_disciplines td
            INNER JOIN disciplinas d ON td.discipline_id = d.id
            WHERE td.teacher_id = :teacher_id
            ORDER BY d.name
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([':teacher_id' => $teacher_id]);
        $disciplines = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($disciplines) {
            echo "<h3>Disciplines and Status:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr>";
            echo "<th>Discipline ID</th>";
            echo "<th>Discipline Name</th>";
            echo "<th>Enabled Value</th>";
            echo "<th>Hex Value</th>";
            echo "<th>Status Text</th>";
            echo "<th>Created</th>";
            echo "</tr>";
            
            foreach ($disciplines as $disc) {
                echo "<tr>";
                echo "<td>" . $disc['discipline_id'] . "</td>";
                echo "<td>" . htmlspecialchars($disc['discipline_name']) . "</td>";
                echo "<td>" . var_export($disc['enabled'], true) . "</td>";
                echo "<td>" . ($disc['hex_value'] ?: 'NULL') . "</td>";
                echo "<td>" . $disc['status_text'] . "</td>";
                echo "<td>" . $disc['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Show update form
            echo "<h3>Test Update:</h3>";
            echo "<form method='POST'>";
            echo "<input type='hidden' name='teacher_id' value='" . $teacher_id . "'>";
            echo "<select name='discipline_id'>";
            foreach ($disciplines as $disc) {
                echo "<option value='" . $disc['discipline_id'] . "'>" . htmlspecialchars($disc['discipline_name']) . "</option>";
            }
            echo "</select>";
            echo "<select name='new_status'>";
            echo "<option value='1'>Apto (1)</option>";
            echo "<option value='0'>Inapto (0)</option>";
            echo "<option value='null'>Aguardando (NULL)</option>";
            echo "</select>";
            echo "<button type='submit'>Update Status</button>";
            echo "</form>";
            
        } else {
            echo "<p>No disciplines found for this teacher.</p>";
        }
    } else {
        echo "<p>Teacher not found.</p>";
    }
} else {
    // Show form to enter teacher ID
    echo "<form method='GET'>";
    echo "<label>Enter Teacher ID: <input type='number' name='teacher_id' required></label>";
    echo "<button type='submit'>Check Status</button>";
    echo "</form>";
}

// Handle POST request to update status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = intval($_POST['teacher_id']);
    $discipline_id = intval($_POST['discipline_id']);
    $new_status = $_POST['new_status'] === 'null' ? null : intval($_POST['new_status']);
    
    echo "<hr>";
    echo "<h3>Updating Status...</h3>";
    
    $sql = "UPDATE teacher_disciplines SET enabled = :status WHERE teacher_id = :teacher_id AND discipline_id = :discipline_id";
    $stmt = $conn->prepare($sql);
    
    try {
        $stmt->execute([
            ':status' => $new_status,
            ':teacher_id' => $teacher_id,
            ':discipline_id' => $discipline_id
        ]);
        
        echo "<p style='color: green;'>✓ Status updated successfully!</p>";
        echo "<p><a href='?teacher_id=" . $teacher_id . "'>Refresh to see changes</a></p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    }
}
?>