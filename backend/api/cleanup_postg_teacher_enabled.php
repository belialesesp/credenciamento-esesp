<?php
// cleanup_postg_teacher_enabled.php
// Run this to remove the enabled field from postg_teacher if it was added

require_once '../classes/database.class.php';

$conection = new Database();
$conn = $conection->connect();

echo "<h2>Cleanup: Remove 'enabled' from postg_teacher Table</h2>";

// Check if enabled field exists
$sql = "SHOW COLUMNS FROM postg_teacher WHERE Field = 'enabled'";
$stmt = $conn->query($sql);
$hasEnabledField = $stmt->rowCount() > 0;

if ($hasEnabledField) {
    echo "<p>Found 'enabled' field in postg_teacher table.</p>";
    echo "<p>Removing it to use discipline-based status instead...</p>";
    
    try {
        $dropSql = "ALTER TABLE postg_teacher DROP COLUMN enabled";
        $conn->exec($dropSql);
        echo "<p style='color: green;'>✅ Successfully removed 'enabled' field from postg_teacher table!</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error removing field: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: green;'>✅ The 'enabled' field does not exist in postg_teacher table (correct).</p>";
}

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Update get_postg_docente() in get_registers.php to NOT include t.enabled</li>";
echo "<li>Update docentes-pos.php to derive status from discipline_statuses</li>";
echo "<li>Test the filtering to ensure it works based on discipline status</li>";
echo "</ol>";

echo "<p><strong>Remember:</strong> Postgraduate teachers should work exactly like regular teachers - status comes from disciplines, not from the teacher record.</p>";
?>