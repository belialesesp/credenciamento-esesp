<?php
// backend/api/get_filtered_teachers.php
require_once '../classes/database.class.php';

header('Content-Type: application/json');

$category = $_GET['category'] ?? '';
$course = $_GET['course'] ?? '';
$status = $_GET['status'] ?? '';

try {
    $conection = new Database();
    $conn = $conection->connect();
    
    // Get teachers that match filters
    $sql = "SELECT DISTINCT t.* FROM teacher t";
    $where = [];
    $params = [];
    
    if ($category !== '') {
        $sql .= " INNER JOIN teacher_activities ta ON t.id = ta.teacher_id";
        $where[] = "ta.activity_id = :category";
        $params[':category'] = $category;
    }
    
    if ($status !== '' || $course !== '') {
        $sql .= " INNER JOIN teacher_disciplines td ON t.id = td.teacher_id";
        
        if ($course !== '') {
            $where[] = "td.discipline_id = :course";
            $params[':course'] = $course;
        }
        
        if ($status === '1') {
            $where[] = "td.enabled = 1";
        } else if ($status === '0') {
            $where[] = "td.enabled = 0";
        } else if ($status === 'null') {
            $where[] = "td.enabled IS NULL";
        }
    }
    
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY t.created_at ASC';
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get disciplines for each teacher
    $result = [];
    foreach ($teachers as $teacher) {
        // Build discipline query - START FRESH
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
        
        // Apply course filter if set
        if ($course !== '') {
            $discSql .= " AND d.id = :course";
            $discParams[':course'] = $course;
        }
        
        // Apply status filter - BE VERY EXPLICIT
        if ($status !== '') {
            if ($status === '1') {
                // For Apto - only show enabled = 1
                $discSql .= " AND td.enabled = 1";
            } else if ($status === '0') {
                // For Inapto - only show enabled = 0
                $discSql .= " AND td.enabled = 0";
            } else if ($status === 'null') {
                // For Aguardando - only show NULL
                $discSql .= " AND td.enabled IS NULL";
            }
        }
        
        $discSql .= " ORDER BY d.name";
        
        $discStmt = $conn->prepare($discSql);
        // Add this right before $discStmt->execute($discParams);
error_log("Discipline query for teacher " . $teacher['id'] . ": " . $discSql);
        $discStmt->execute($discParams);
        $disciplines = $discStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Only include teacher if they have disciplines after filtering
        if (!empty($disciplines)) {
            $disciplineStatuses = [];
            foreach ($disciplines as $disc) {
                $statusValue = $disc['enabled'] === null ? 'null' : $disc['enabled'];
                $disciplineStatuses[] = $disc['id'] . ':' . $disc['name'] . ':' . $statusValue;
            }
            
            $teacher['discipline_statuses'] = implode('||', $disciplineStatuses);
            $result[] = $teacher;
        }
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>