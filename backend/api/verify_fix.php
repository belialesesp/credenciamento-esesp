<?php
// backend/api/verify_fix.php
// Run this after replacing the export files to verify everything works

echo "<h1>Export Fix Verification</h1>";

// Test if files have been updated
$files_to_check = [
    'export_technicians_pdf.php',
    'export_interpreters_pdf.php', 
    'export_docentes_pdf.php',
    'export_docentes_pos_pdf.php',
    'export_to_excel.php',
    'export_to_excel_postg.php'
];

echo "<h2>Checking if files have been updated:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>File</th><th>Status</th><th>Has ob_clean()</th><th>Test</th></tr>";

foreach ($files_to_check as $file) {
    echo "<tr>";
    echo "<td>$file</td>";
    
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Check for our fixes
        $hasObClean = strpos($content, 'ob_clean()') !== false || strpos($content, 'ob_end_clean()') !== false;
        $hasProperMatch = strpos($content, 'match (intval(') !== false || !strpos($content, 'match (');
        
        if ($hasObClean) {
            echo "<td style='color: green;'>✓ Updated</td>";
            echo "<td style='color: green;'>✓ Yes</td>";
        } else {
            echo "<td style='color: red;'>✗ Old version</td>";
            echo "<td style='color: red;'>✗ No</td>";
        }
        
        echo "<td><a href='$file' download>Download</a></td>";
    } else {
        echo "<td style='color: red;'>✗ Missing</td>";
        echo "<td>-</td>";
        echo "<td>-</td>";
    }
    
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h2>Quick Links to Test:</h2>";
echo "<ul>";
echo "<li><a href='export_technicians_pdf.php' target='_blank'>Test Technicians PDF Export</a></li>";
echo "<li><a href='export_interpreters_pdf.php' target='_blank'>Test Interpreters PDF Export</a></li>";
echo "<li><a href='export_docentes_pdf.php' target='_blank'>Test Docentes PDF Export</a></li>";
echo "<li><a href='export_docentes_pos_pdf.php' target='_blank'>Test Docentes Pós PDF Export</a></li>";
echo "<li><a href='export_to_excel.php' target='_blank'>Test Docentes Excel Export</a></li>";
echo "<li><a href='export_to_excel_postg.php' target='_blank'>Test Docentes Pós Excel Export</a></li>";
echo "</ul>";

echo "<p><strong>If files show 'Old version':</strong> You need to replace them with the fixed versions from the artifacts.</p>";
echo "<p><strong>If downloads work:</strong> Your exports are fixed!</p>";
?>