<?php
// update_teacher_disciplines_to_apto.php
// Place this file in your backend/api/ directory and run it

require_once '../classes/database.class.php';

// Set execution time limit for large updates
set_time_limit(300);

$conection = new Database();
$conn = $conection->connect();

echo "<h2>Update All Teacher Disciplines to 'Apto' Status</h2>";

try {
    // Start transaction for safety
    $conn->beginTransaction();
    
    // First, let's check the current status distribution
    echo "<h3>Current Status Distribution:</h3>";
    $checkSql = "
        SELECT 
            enabled,
            COUNT(*) as count
        FROM teacher_disciplines
        GROUP BY enabled
        ORDER BY enabled
    ";
    
    $stmt = $conn->query($checkSql);
    $currentStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Status Value</th><th>Count</th></tr>";
    foreach ($currentStatus as $status) {
        $display = $status['enabled'] === null ? 'NULL' : $status['enabled'];
        echo "<tr>";
        echo "<td>" . htmlspecialchars($display) . "</td>";
        echo "<td>" . $status['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Count total records before update
    $countSql = "SELECT COUNT(*) as total FROM teacher_disciplines";
    $countStmt = $conn->query($countSql);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<p>Total records to update: <strong>" . $totalRecords . "</strong></p>";
    
    // Update all teacher_disciplines to enabled = 1 (Apto)
    echo "<h3>Updating all records to 'Apto' status (enabled = 1)...</h3>";
    
    $updateSql = "UPDATE teacher_disciplines SET enabled = 1";
    $affectedRows = $conn->exec($updateSql);
    
    echo "<p>Records updated: <strong>" . $affectedRows . "</strong></p>";
    
    // Verify the update
    echo "<h3>Verification - Status Distribution After Update:</h3>";
    $stmt = $conn->query($checkSql);
    $newStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Status Value</th><th>Count</th></tr>";
    foreach ($newStatus as $status) {
        $display = $status['enabled'] === null ? 'NULL' : $status['enabled'];
        echo "<tr>";
        echo "<td>" . htmlspecialchars($display) . "</td>";
        echo "<td>" . $status['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check how many teachers now have at least one discipline with enabled = 1
    $teacherCountSql = "
        SELECT COUNT(DISTINCT teacher_id) as teacher_count
        FROM teacher_disciplines
        WHERE enabled = 1
    ";
    $teacherStmt = $conn->query($teacherCountSql);
    $teacherCount = $teacherStmt->fetch(PDO::FETCH_ASSOC)['teacher_count'];
    
    echo "<p>Total teachers with at least one 'Apto' discipline: <strong>" . $teacherCount . "</strong></p>";
    
    // Commit the transaction
    $conn->commit();
    echo "<h3 style='color: green;'>✅ Update completed successfully!</h3>";
    
    // Optional: Show sample of updated records
    echo "<h3>Sample of Updated Records:</h3>";
    $sampleSql = "
        SELECT 
            t.name as teacher_name,
            d.name as discipline_name,
            td.enabled
        FROM teacher_disciplines td
        JOIN teacher t ON t.id = td.teacher_id
        JOIN disciplinas d ON d.id = td.discipline_id
        LIMIT 10
    ";
    
    $sampleStmt = $conn->query($sampleSql);
    $samples = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Teacher</th><th>Discipline</th><th>Status</th></tr>";
    foreach ($samples as $sample) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($sample['teacher_name']) . "</td>";
        echo "<td>" . htmlspecialchars($sample['discipline_name']) . "</td>";
        echo "<td>" . $sample['enabled'] . " (Apto)</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo "<h3 style='color: red;'>❌ Error occurred:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>