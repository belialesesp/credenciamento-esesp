<?php
// diagnose.php - Put in backend/api/ and run

require_once '../classes/database.class.php';
require_once 'get_registers.php';

$conn = (new Database())->connect();

echo "<h1>Diagnosing Your Issues</h1>";

// 1. Check docentes.php issue
echo "<h2>1. Checking docentes.php 'Chamado em' issue:</h2>";

$teachers = get_docente($conn);
if (!empty($teachers)) {
    $first = $teachers[0];
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0;'>";
    echo "<strong>First teacher data:</strong><br>";
    echo "Name: " . $first['name'] . "<br>";
    echo "Has 'called_at' field: " . (isset($first['called_at']) ? '<span style="color:green">YES ✅</span>' : '<span style="color:red">NO ❌</span>') . "<br>";
    
    if (isset($first['called_at'])) {
        echo "called_at value: " . ($first['called_at'] ?: 'NULL (no date set)') . "<br>";
        echo "<strong>✅ The field exists! Make sure you're displaying it in the table.</strong>";
    } else {
        echo "<strong>❌ The field is missing from get_docente() function!</strong><br>";
        echo "Add <code>t.called_at,</code> to the SELECT in get_docente()";
    }
    echo "</div>";
}

// 2. Check docentes-pos.php
echo "<h2>2. Checking docentes-pos.php data:</h2>";

$postg_teachers = get_postg_docente($conn);
if (!empty($postg_teachers)) {
    $first = $postg_teachers[0];
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0;'>";
    echo "<strong>First postgraduate teacher:</strong><br>";
    echo "Name: " . $first['name'] . "<br>";
    echo "Has discipline_statuses: " . (isset($first['discipline_statuses']) ? '<span style="color:green">YES ✅</span>' : '<span style="color:red">NO ❌</span>') . "<br>";
    
    if (isset($first['discipline_statuses'])) {
        echo "Sample disciplines: " . htmlspecialchars(substr($first['discipline_statuses'], 0, 100)) . "...<br>";
        echo "<strong>✅ Discipline data is working!</strong>";
    }
    echo "</div>";
}

// 3. Summary
echo "<h2>3. Summary:</h2>";
echo "<ul>";
echo "<li>If called_at is missing: Update get_docente() in get_registers.php</li>";
echo "<li>If called_at exists: You're not displaying it correctly in the table</li>";
echo "<li>For docentes-pos.php: Remove PHP comments and change header to 'Cursos e Status'</li>";
echo "</ul>";

// 4. Sample SQL to add test data
echo "<h2>4. Add Test Data (if needed):</h2>";
echo "<pre style='background: #f5f5f5; padding: 10px;'>";
echo "UPDATE teacher SET called_at = '2025-01-15 10:00:00' WHERE id = (SELECT id FROM teacher LIMIT 1);";
echo "</pre>";
?>