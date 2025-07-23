<?php
// backend/api/test_pdf_export.php
// Diagnostic script to test PDF export functionality

// Check if required files exist
echo "<h2>PDF Export Diagnostic</h2>";

echo "<h3>1. Checking File Paths:</h3>";
$files = [
    '../classes/database.class.php' => 'Database class',
    '../../vendor/autoload.php' => 'Composer autoload',
    '../../vendor/tecnickcom/tcpdf/tcpdf.php' => 'TCPDF library'
];

foreach ($files as $file => $description) {
    if (file_exists($file)) {
        echo "✓ $description found at: $file<br>";
    } else {
        echo "✗ $description NOT FOUND at: $file<br>";
    }
}

echo "<h3>2. Checking PHP Extensions:</h3>";
$required_extensions = ['PDO', 'pdo_mysql', 'gd', 'mbstring'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✓ $ext extension is loaded<br>";
    } else {
        echo "✗ $ext extension is NOT loaded<br>";
    }
}

echo "<h3>3. Testing Database Connection:</h3>";
try {
    require_once '../classes/database.class.php';
    $connection = new Database();
    $conn = $connection->connect();
    echo "✓ Database connection successful<br>";
    
    // Test queries
    $tables = ['technician', 'interpreter', 'teacher', 'postg_teacher'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✓ Table '$table' has {$result['count']} records<br>";
    }
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "<br>";
}

echo "<h3>4. Testing TCPDF:</h3>";
try {
    require_once '../../vendor/autoload.php';
    
    // Check if TCPDF class exists
    if (class_exists('TCPDF')) {
        echo "✓ TCPDF class is available<br>";
        
        // Try to create a simple PDF
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Test PDF', 0, 1, 'C');
        
        // Output to string to test
        $pdfContent = $pdf->Output('test.pdf', 'S');
        if (strlen($pdfContent) > 1000) {
            echo "✓ TCPDF can generate PDF content (size: " . strlen($pdfContent) . " bytes)<br>";
        } else {
            echo "✗ TCPDF generated content is too small<br>";
        }
    } else {
        echo "✗ TCPDF class not found<br>";
    }
} catch (Exception $e) {
    echo "✗ TCPDF error: " . $e->getMessage() . "<br>";
}

echo "<h3>5. Testing Export URLs:</h3>";
$export_urls = [
    'export_technicians_pdf.php' => 'Technicians PDF Export',
    'export_interpreters_pdf.php' => 'Interpreters PDF Export',
    'export_docentes_pdf.php' => 'Docentes PDF Export',
    'export_docentes_pos_pdf.php' => 'Docentes Pós PDF Export'
];

foreach ($export_urls as $file => $description) {
    if (file_exists($file)) {
        echo "✓ $description file exists<br>";
    } else {
        echo "✗ $description file NOT FOUND<br>";
    }
}

echo "<h3>6. PHP Error Reporting:</h3>";
echo "Error reporting level: " . error_reporting() . "<br>";
echo "Display errors: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "<br>";
echo "Log errors: " . (ini_get('log_errors') ? 'ON' : 'OFF') . "<br>";
echo "Error log location: " . ini_get('error_log') . "<br>";

echo "<h3>7. Memory and Execution Limits:</h3>";
echo "Memory limit: " . ini_get('memory_limit') . "<br>";
echo "Max execution time: " . ini_get('max_execution_time') . " seconds<br>";

echo "<hr>";
echo "<p><strong>If you see any ✗ marks above, those indicate potential issues that need to be fixed.</strong></p>";
?>