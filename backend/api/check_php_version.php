<?php
// backend/api/check_php_version.php
// This script checks if match expressions are causing the issue

echo "<h1>PHP Version and Match Expression Test</h1>";

echo "<h2>PHP Version</h2>";
echo "Your PHP version: <strong>" . PHP_VERSION . "</strong><br>";

echo "<h2>Testing Match Expression</h2>";
try {
    // This will fail on PHP < 8.0
    $testValue = 1;
    eval('$result = match($testValue) {
        1 => "Apto",
        0 => "Inapto",
        default => "Aguardando"
    };');
    
    echo "<span style='color: green;'>✓ Match expressions are supported!</span><br>";
    
} catch (ParseError $e) {
    echo "<span style='color: red;'>✗ Match expressions are NOT supported!</span><br>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "<br><strong>SOLUTION:</strong> You must use the fixed export files that replace match with if/else statements.<br>";
}

echo "<h2>Testing Your Current Export Files</h2>";

$files = [
    'export_technicians_pdf.php',
    'export_interpreters_pdf.php',
    'export_docentes_pdf.php',
    'export_docentes_pos_pdf.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        if (strpos($content, 'match (') !== false || strpos($content, 'match(') !== false) {
            echo "<span style='color: red;'>✗ $file contains match expressions - WILL FAIL on PHP < 8.0!</span><br>";
        } else {
            echo "<span style='color: green;'>✓ $file does not use match expressions</span><br>";
        }
    } else {
        echo "<span style='color: orange;'>⚠️ $file not found</span><br>";
    }
}

echo "<hr>";
echo "<h2>Summary</h2>";

if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    echo "<div style='background: #ffebee; padding: 10px; border: 1px solid #f44336;'>";
    echo "<strong>You have PHP " . PHP_VERSION . " which does NOT support match expressions!</strong><br>";
    echo "You MUST replace all export files with the PHP 7 compatible versions provided.<br>";
    echo "The match expression is causing your PDF exports to fail with 'Falha ao carregar documento PDF'";
    echo "</div>";
} else {
    echo "<div style='background: #e8f5e9; padding: 10px; border: 1px solid #4caf50;'>";
    echo "Your PHP version supports match expressions. The error might be caused by:<br>";
    echo "- Whitespace before &lt;?php tag<br>";
    echo "- Output being sent before PDF headers<br>";
    echo "- Missing ob_clean() calls<br>";
    echo "</div>";
}
?>