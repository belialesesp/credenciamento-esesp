<?php
// backend/api/test_and_fix.php
// Run this to identify and fix the export issues

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Export Test and Fix</h1>";

// 1. Check PHP version
echo "<h2>1. PHP Version Check</h2>";
echo "Current version: " . PHP_VERSION . "<br>";
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    echo "<span style='color: orange;'>⚠️ PHP < 8.0 detected. The 'match' expression won't work!</span><br>";
    echo "<strong>Solution:</strong> Use the fixed export files provided that use if/else instead of match.<br>";
} else {
    echo "<span style='color: green;'>✓ PHP 8.0+ - match expressions are supported</span><br>";
}

// 2. Test a minimal PDF with current setup
echo "<h2>2. Testing Minimal PDF Generation</h2>";
try {
    require_once '../../vendor/autoload.php';
    
    // Clear any output
    ob_start();
    ob_clean();
    
    $pdf = new TCPDF();
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Test PDF', 0, 1, 'C');
    
    // Try to output
    $content = $pdf->Output('test.pdf', 'S');
    ob_end_clean();
    
    echo "✓ PDF generation successful (" . strlen($content) . " bytes)<br>";
    echo '<a href="data:application/pdf;base64,' . base64_encode($content) . '" download="test.pdf">Download Test PDF</a><br>';
    
} catch (Exception $e) {
    echo "<span style='color: red;'>✗ PDF Error: " . $e->getMessage() . "</span><br>";
}

// 3. Check for output before headers
echo "<h2>3. Checking for Premature Output</h2>";
$files_to_check = array(
    'export_technicians_pdf.php',
    'export_interpreters_pdf.php',
    'export_docentes_pdf.php',
    'export_docentes_pos_pdf.php'
);

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (substr($content, 0, 5) !== '<?php') {
            echo "<span style='color: red;'>✗ $file has content before &lt;?php tag!</span><br>";
        } else {
            echo "✓ $file starts correctly<br>";
        }
        
        // Check for BOM
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            echo "<span style='color: red;'>✗ $file has UTF-8 BOM!</span><br>";
        }
    }
}

// 4. Test database queries
echo "<h2>4. Testing Database Queries</h2>";
try {
    require_once '../classes/database.class.php';
    $connection = new Database();
    $conn = $connection->connect();
    
    // Test technician query
    $sql = "SELECT COUNT(*) as count FROM technician";
    $stmt = $conn->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Technicians table: " . $result['count'] . " records<br>";
    
    // Test if called_at column exists
    $sql = "SHOW COLUMNS FROM technician LIKE 'called_at'";
    $stmt = $conn->query($sql);
    if ($stmt->rowCount() > 0) {
        echo "✓ called_at column exists in technician table<br>";
    } else {
        echo "<span style='color: red;'>✗ called_at column NOT FOUND in technician table</span><br>";
    }
    
} catch (Exception $e) {
    echo "<span style='color: red;'>✗ Database error: " . $e->getMessage() . "</span><br>";
}

// 5. Quick fixes
echo "<h2>5. Quick Fix Options</h2>";
echo '<p>Choose a fix to apply:</p>';
echo '<ol>';
echo '<li><a href="?action=download_fixed_files">Download all fixed export files (PHP 7 compatible)</a></li>';
echo '<li><a href="?action=test_simple_pdf">Test simple PDF export</a></li>';
echo '<li><a href="?action=test_csv_export">Test CSV export (Excel compatible)</a></li>';
echo '</ol>';

// Handle actions
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'test_simple_pdf':
            ob_clean();
            $pdf = new TCPDF();
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->AddPage();
            $pdf->Cell(0, 10, 'Simple Test PDF - ' . date('Y-m-d H:i:s'), 0, 1, 'C');
            $pdf->Output('simple_test.pdf', 'D');
            exit;
            
        case 'test_csv_export':
            ob_clean();
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="test.csv"');
            echo "\xEF\xBB\xBF"; // BOM for Excel
            echo "Name;Email;Date\n";
            echo "Test User;test@example.com;" . date('Y-m-d') . "\n";
            exit;
            
        case 'download_fixed_files':
            echo '<h3>Fixed Files to Copy:</h3>';
            echo '<p>Copy the content from the "Complete Fixed Export Files" artifact provided earlier.</p>';
            echo '<p>The files are PHP 7 compatible and include:</p>';
            echo '<ul>';
            echo '<li>export_technicians_pdf.php</li>';
            echo '<li>export_interpreters_pdf.php</li>';
            echo '<li>export_docentes_pdf.php</li>';
            echo '<li>export_docentes_pos_pdf.php</li>';
            echo '<li>export_to_excel.php (CSV format)</li>';
            echo '<li>export_to_excel_postg.php (CSV format)</li>';
            echo '</ul>';
            break;
    }
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>Based on the diagnostic, the most likely issues are:</p>";
echo "<ol>";
echo "<li><strong>PHP version incompatibility</strong> - The 'match' expression requires PHP 8.0+</li>";
echo "<li><strong>Output before headers</strong> - Whitespace or BOM before &lt;?php tag</li>";
echo "<li><strong>Missing Excel export files</strong> - The export_to_excel.php files may be missing</li>";
echo "</ol>";
echo "<p><strong>Recommended solution:</strong> Use the fixed export files provided in the artifacts above.</p>";
?>