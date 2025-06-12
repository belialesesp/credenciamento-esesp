<?php
// backend/api/get_filtered_teachers.php - COMPLETE FIXED VERSION
require_once '../classes/database.class.php';

header('Content-Type: application/json; charset=utf-8');

$category = $_GET['category'] ?? '';
$course = $_GET['course'] ?? '';
$status = $_GET['status'] ?? '';

try {
    $conection = new Database();
    $conn = $conection->connect();
    
    // First, get teachers that match the filters
    $sql = "SELECT DISTINCT t.* FROM teacher t";
    $joins = [];
    $where = [];
    $params = [];
    
    // Add necessary joins based on filters
    if ($category !== '') {
        $joins[] = "INNER JOIN teacher_activities ta ON t.id = ta.teacher_id";
        $where[] = "ta.activity_id = :category";
        $params[':category'] = $category;
    }
    
    if ($status !== '' || $course !== '') {
        $joins[] = "INNER JOIN teacher_disciplines td_filter ON t.id = td_filter.teacher_id";
        
        if ($course !== '') {
            $where[] = "td_filter.discipline_id = :course";
            $params[':course'] = $course;
        }
        
        if ($status === '1') {
            $where[] = "BINARY td_filter.enabled = '1'";
        } else if ($status === '0') {
            $where[] = "BINARY td_filter.enabled = '0'";
        } else if ($status === 'null') {
            // FIXED: Use BINARY for empty string to prevent '0' from matching ''
            $where[] = "(td_filter.enabled IS NULL OR BINARY td_filter.enabled = '')";
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
    
    // Now get disciplines for each teacher (filtered by status if applicable)
    $result = [];
    foreach ($teachers as $teacher) {
        // Build discipline query
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
        
        // Apply course filter to disciplines
        if ($course !== '') {
            $discSql .= " AND d.id = :course";
            $discParams[':course'] = $course;
        }
        
        // Apply status filter to disciplines
        if ($status !== '') {
            if ($status === '1') {
                $discSql .= " AND BINARY td.enabled = '1'";
            } else if ($status === '0') {
                $discSql .= " AND BINARY td.enabled = '0'";
            } else if ($status === 'null') {
                // FIXED: Use BINARY for empty string to prevent '0' from matching ''
                $discSql .= " AND (td.enabled IS NULL OR BINARY td.enabled = '')";
            }
        }
        
        $discSql .= " ORDER BY d.name";
        
        $discStmt = $conn->prepare($discSql);
        $discStmt->execute($discParams);
        $disciplines = $discStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Build discipline statuses string
        $disciplineStatuses = [];
        foreach ($disciplines as $disc) {
            $disciplineName = $disc['name']; // Keep full name with colons
            
            // Determine status value
            $enabledRaw = $disc['enabled'];
            if ($enabledRaw === null || $enabledRaw === '') {
                $statusValue = 'null';
            } elseif ($enabledRaw == 0 && !is_null($enabledRaw)) {
                $statusValue = '0';
            } elseif ($enabledRaw == 1) {
                $statusValue = '1';
            } else {
                $statusValue = 'null';
            }
            
            // Use |~| as delimiter instead of :
            $disciplineStatuses[] = $disc['id'] . '|~|' . $disciplineName . '|~|' . $statusValue;
        }
        
        // Always include the teacher, even if no disciplines match
        $teacher['discipline_statuses'] = implode('|~~|', $disciplineStatuses);
        $result[] = $teacher;
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error in get_filtered_teachers.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>