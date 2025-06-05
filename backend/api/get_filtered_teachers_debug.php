<?php
// backend/api/get_filtered_teachers_debug.php
require_once '../classes/database.class.php';

header('Content-Type: application/json');

$category = $_GET['category'] ?? '';
$course = $_GET['course'] ?? '';
$status = $_GET['status'] ?? '';

try {
    $conection = new Database();
    $conn = $conection->connect();
    
    // Debug info
    $debug = [
        'filters' => [
            'category' => $category,
            'course' => $course,
            'status' => $status
        ],
        'queries' => []
    ];
    
    // Get one teacher as example (teacher ID 24 - Alef)
    $teacherId = 24;
    
    // Check what values are actually in the database
    $checkSql = "
        SELECT 
            td.discipline_id,
            d.name,
            td.enabled,
            CASE 
                WHEN td.enabled IS NULL THEN 'IS_NULL'
                WHEN td.enabled = '' THEN 'EMPTY_STRING'  
                WHEN td.enabled = '0' THEN 'STRING_ZERO'
                WHEN td.enabled = 0 THEN 'INT_ZERO'
                WHEN td.enabled = '1' THEN 'STRING_ONE'
                WHEN td.enabled = 1 THEN 'INT_ONE'
                ELSE CONCAT('OTHER: ', td.enabled)
            END as enabled_type,
            LENGTH(td.enabled) as enabled_length
        FROM teacher_disciplines td
        INNER JOIN disciplinas d ON td.discipline_id = d.id
        WHERE td.teacher_id = :teacher_id
        ORDER BY d.name
    ";
    
    $stmt = $conn->prepare($checkSql);
    $stmt->execute([':teacher_id' => $teacherId]);
    $allDisciplines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $debug['all_disciplines'] = $allDisciplines;
    
    // Now test different filter conditions
    $testQueries = [
        'test_1_string' => "SELECT * FROM teacher_disciplines WHERE teacher_id = $teacherId AND enabled = '1'",
        'test_1_int' => "SELECT * FROM teacher_disciplines WHERE teacher_id = $teacherId AND enabled = 1",
        'test_null' => "SELECT * FROM teacher_disciplines WHERE teacher_id = $teacherId AND enabled IS NULL",
        'test_empty' => "SELECT * FROM teacher_disciplines WHERE teacher_id = $teacherId AND enabled = ''",
        'test_0_string' => "SELECT * FROM teacher_disciplines WHERE teacher_id = $teacherId AND enabled = '0'",
        'test_0_int' => "SELECT * FROM teacher_disciplines WHERE teacher_id = $teacherId AND enabled = 0"
    ];
    
    foreach ($testQueries as $testName => $testSql) {
        $stmt = $conn->query($testSql);
        $debug['test_results'][$testName] = $stmt->rowCount();
    }
    
    // Test the actual filter query
    if ($status === '1') {
        $filterSql = "
            SELECT d.name, td.enabled 
            FROM teacher_disciplines td
            INNER JOIN disciplinas d ON td.discipline_id = d.id
            WHERE td.teacher_id = $teacherId AND td.enabled = '1'
        ";
    } else if ($status === '0') {
        $filterSql = "
            SELECT d.name, td.enabled 
            FROM teacher_disciplines td
            INNER JOIN disciplinas d ON td.discipline_id = d.id
            WHERE td.teacher_id = $teacherId AND td.enabled = '0'
        ";
    } else if ($status === 'null') {
        $filterSql = "
            SELECT d.name, td.enabled 
            FROM teacher_disciplines td
            INNER JOIN disciplinas d ON td.discipline_id = d.id
            WHERE td.teacher_id = $teacherId AND (td.enabled IS NULL OR td.enabled = '')
        ";
    }
    
    if (isset($filterSql)) {
        $stmt = $conn->query($filterSql);
        $debug['filtered_disciplines'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $debug['filter_query'] = $filterSql;
    }
    
    echo json_encode($debug, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>