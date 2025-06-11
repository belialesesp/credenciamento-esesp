<?php
// backend/api/get_filtered_teachers.php - FIXED VERSION
require_once '../classes/database.class.php';

header('Content-Type: application/json; charset=utf-8');

$category = $_GET['category'] ?? '';
$course = $_GET['course'] ?? '';
$status = $_GET['status'] ?? '';

try {
    $conection = new Database();
    $conn = $conection->connect();
    
    // Get teachers matching filters
    $sql = "SELECT DISTINCT t.* FROM teacher t";
    $joins = [];
    $where = [];
    $params = [];
    
    if ($category !== '') {
        $joins[] = "INNER JOIN teacher_activities ta ON t.id = ta.teacher_id";
        $where[] = "ta.activity_id = :category";
        $params[':category'] = $category;
    }
    
    if ($status !== '' || $course !== '') {
        $joins[] = "INNER JOIN teacher_disciplines td ON t.id = td.teacher_id";
        
        if ($course !== '') {
            $where[] = "td.discipline_id = :course";
            $params[':course'] = $course;
        }
        
        // FIXED: Handle both 'pending' and 'null' values for Aguardando
        if ($status === '1') {
            $where[] = "BINARY td.enabled = '1'";
        } else if ($status === '0') {
            $where[] = "BINARY td.enabled = '0'";
        } else if ($status === 'null' || $status === 'pending') {
            $where[] = "(td.enabled IS NULL OR BINARY td.enabled = '')";
        }
    }
    
    $sql .= ' ' . implode(' ', $joins);
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY t.created_at ASC';
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process each teacher
    $result = [];
    foreach ($teachers as $teacher) {
        // Get discipline statuses for this teacher
        $discSql = "
            SELECT 
                d.id,
                d.name,
                td.enabled
            FROM teacher_disciplines td
            INNER JOIN disciplinas d ON td.discipline_id = d.id
            WHERE td.teacher_id = :teacher_id
        ";
        
        $discParams = [':teacher_id' => $teacher['id']];
        
        if ($course !== '') {
            $discSql .= " AND d.id = :course";
            $discParams[':course'] = $course;
        }
        
        // Apply status filter to disciplines as well
        if ($status === '1') {
            $discSql .= " AND BINARY td.enabled = '1'";
        } else if ($status === '0') {
            $discSql .= " AND BINARY td.enabled = '0'";
        } else if ($status === 'null' || $status === 'pending') {
            $discSql .= " AND (td.enabled IS NULL OR BINARY td.enabled = '')";
        }
        
        $discSql .= " ORDER BY d.name";
        
        $discStmt = $conn->prepare($discSql);
        $discStmt->execute($discParams);
        $disciplines = $discStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($disciplines)) {
            $disciplineStatuses = [];
            
            foreach ($disciplines as $disc) {
                $cleanName = str_replace([':', '||'], ' ', $disc['name']);
                
                // Determine status value with strict type checking
                $enabledRaw = $disc['enabled'];
                
                if ($enabledRaw === null || $enabledRaw === '') {
                    $statusValue = 'null';
                } else if ($enabledRaw === '0' || $enabledRaw === 0) {
                    $statusValue = '0';
                } else if ($enabledRaw === '1' || $enabledRaw === 1) {
                    $statusValue = '1';
                } else {
                    $statusValue = 'null';
                }
                
                $disciplineStatuses[] = $disc['id'] . ':' . $cleanName . ':' . $statusValue;
            }
            
            $teacher['discipline_statuses'] = implode('||', $disciplineStatuses);
            $result[] = $teacher;
        }
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>