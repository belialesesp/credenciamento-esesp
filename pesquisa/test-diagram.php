<?php
/**
 * Test Diagram Generation
 * Helps diagnose issues with Python diagram generation
 */

// Fix for CLI
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '/pesquisa/test-diagram.php';
}

require_once __DIR__ . '/includes/init.php';

echo "<h1>Diagram Generation Test</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;} .ok{color:green;} .error{color:red;} pre{background:white;padding:10px;border:1px solid #ddd;}</style>";

// Test 1: Check Python
echo "<h2>1. Python Installation</h2>";
exec('which python3 2>&1', $pythonPath, $returnCode);
if ($returnCode === 0) {
    echo "<p class='ok'>✅ Python3 found at: " . implode('', $pythonPath) . "</p>";
    
    exec('python3 --version 2>&1', $version);
    echo "<p class='ok'>✅ Version: " . implode('', $version) . "</p>";
} else {
    echo "<p class='error'>❌ Python3 not found!</p>";
    echo "<p>Install with: <code>sudo apt-get install python3</code></p>";
}

// Test 2: Check required Python libraries
echo "<h2>2. Python Libraries</h2>";
$libraries = ['matplotlib', 'numpy'];
foreach ($libraries as $lib) {
    exec("python3 -c 'import $lib' 2>&1", $output, $returnCode);
    if ($returnCode === 0) {
        echo "<p class='ok'>✅ $lib is installed</p>";
    } else {
        echo "<p class='error'>❌ $lib is NOT installed</p>";
        echo "<p>Install with: <code>pip3 install $lib</code></p>";
    }
}

// Test 3: Check generate_diagram.py file
echo "<h2>3. Python Script</h2>";
$scriptPath = __DIR__ . '/generate_diagram.py';
if (file_exists($scriptPath)) {
    echo "<p class='ok'>✅ Script found at: $scriptPath</p>";
    
    if (is_readable($scriptPath)) {
        echo "<p class='ok'>✅ Script is readable</p>";
    } else {
        echo "<p class='error'>❌ Script is not readable</p>";
    }
    
    // Check if executable
    if (is_executable($scriptPath)) {
        echo "<p class='ok'>✅ Script is executable</p>";
    } else {
        echo "<p class='error'>⚠️ Script is not executable (this is OK, we call with python3)</p>";
    }
} else {
    echo "<p class='error'>❌ Script NOT found at: $scriptPath</p>";
}

// Test 4: Check diagrams directory
echo "<h2>4. Diagrams Directory</h2>";
$diagramsDir = DIAGRAMS_DIR;
echo "<p>Directory: $diagramsDir</p>";

if (file_exists($diagramsDir)) {
    echo "<p class='ok'>✅ Directory exists</p>";
    
    if (is_writable($diagramsDir)) {
        echo "<p class='ok'>✅ Directory is writable</p>";
    } else {
        echo "<p class='error'>❌ Directory is NOT writable</p>";
        echo "<p>Fix with: <code>sudo chmod 755 $diagramsDir && sudo chown www-data:www-data $diagramsDir</code></p>";
    }
} else {
    echo "<p class='error'>❌ Directory does NOT exist</p>";
    mkdir($diagramsDir, 0755, true);
    echo "<p class='ok'>✅ Created directory</p>";
}

// Test 5: Try to generate a test diagram
echo "<h2>5. Test Diagram Generation</h2>";

$db = PesquisaDatabase::getInstance();

// Get a course with responses
$course = $db->fetchOne("
    SELECT c.*, ac.overall_score, ac.pedagogical_score, ac.didactic_score, ac.infrastructure_score, ac.response_count
    FROM courses c
    JOIN analytics_cache ac ON c.id = ac.course_id
    WHERE ac.response_count > 0
    LIMIT 1
");

if ($course) {
    echo "<p class='ok'>✅ Found test course: " . htmlspecialchars($course['name']) . "</p>";
    echo "<p>Course ID: " . $course['id'] . "</p>";
    echo "<p>Responses: " . $course['response_count'] . "</p>";
    echo "<p>Overall Score: " . number_format($course['overall_score'], 1) . "%</p>";
    
    // Prepare test data
    $testData = [
        'course_name' => $course['name'],
        'docente_name' => $course['docente_name'],
        'overall_score' => $course['overall_score'],
        'pedagogical_score' => $course['pedagogical_score'],
        'didactic_score' => $course['didactic_score'],
        'infrastructure_score' => $course['infrastructure_score'],
        'response_count' => $course['response_count']
    ];
    
    $jsonFile = sys_get_temp_dir() . '/test_diagram_data.json';
    file_put_contents($jsonFile, json_encode($testData));
    echo "<p class='ok'>✅ Created test JSON: $jsonFile</p>";
    
    $outputFile = $diagramsDir . 'test_diagram.png';
    $command = "python3 $scriptPath " . escapeshellarg($jsonFile) . " " . escapeshellarg($outputFile) . " 2>&1";
    
    echo "<p>Command: <code>$command</code></p>";
    
    exec($command, $output, $returnCode);
    
    echo "<h3>Output:</h3>";
    echo "<pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
    
    echo "<h3>Return Code: $returnCode</h3>";
    
    if ($returnCode === 0) {
        echo "<p class='ok'>✅ Command executed successfully!</p>";
        
        if (file_exists($outputFile)) {
            echo "<p class='ok'>✅ Diagram file created!</p>";
            echo "<p><img src='diagrams/test_diagram.png' style='max-width:800px; border:2px solid #ddd;'></p>";
            echo "<p><a href='diagrams/test_diagram.png' download>Download Test Diagram</a></p>";
        } else {
            echo "<p class='error'>❌ Diagram file was NOT created</p>";
        }
    } else {
        echo "<p class='error'>❌ Command failed with return code: $returnCode</p>";
    }
    
    // Cleanup
    if (file_exists($jsonFile)) {
        unlink($jsonFile);
    }
    
} else {
    echo "<p class='error'>❌ No courses with responses found. Run generate-dummy-data.php first.</p>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>If all tests pass, diagram generation should work in analytics.php</p>";
echo "<p><a href='analytics.php'>Go to Analytics</a> | <a href='courses.php'>Go to Courses</a></p>";
?>