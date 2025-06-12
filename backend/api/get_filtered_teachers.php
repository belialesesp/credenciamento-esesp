<?php
// backend/api/get_filtered_teachers.php - WITH NO DISCIPLINES FILTER
require_once '../classes/database.class.php';

header('Content-Type: application/json; charset=utf-8');

$category = $_GET['category'] ?? '';
$course = $_GET['course'] ?? '';
$status = $_GET['status'] ?? '';

try {
    $conection = new Database();
    $conn = $conection->connect();
    
    // Special handling for "no-disciplines" filter
    if ($status === 'no-disciplines') {
        // Get teachers with NO disciplines at all
        $sql = "
            SELECT DISTINCT
                t.id,
                t.name,
                t.email,
                t.phone,
                t.cpf,
                t.called_at,
                t.created_at,
                t.document_number,
                t.document_emissor,
                t.document_uf,
                t.special_needs,
                t.address_id,
                '' as discipline_statuses
            FROM teacher t
            LEFT JOIN teacher_disciplines td ON t.id = td.teacher_id
        ";
        
        $where = ["td.teacher_id IS NULL"];
        $params = [];
        
        // Still apply category filter if set
        if ($category !== '') {
            $sql .= " INNER JOIN teacher_activities ta ON t.id = ta.teacher_id";
            $where[] = "ta.activity_id = :category";
            $params[':category'] = $category;
        }
        
        $sql .= " WHERE " . implode(" AND ", $where);
        $sql .= " GROUP BY t.id ORDER BY t.created_at ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Return teachers with empty discipline_statuses
        echo json_encode($teachers, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Regular filtering logic for other statuses
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
        if ($status !== '' && $status !== 'no-disciplines') {
            if ($status === '1') {
                $discSql .= " AND BINARY td.enabled = '1'";
            } else if ($status === '0') {
                $discSql .= " AND BINARY td.enabled = '0'";
            } else if ($status === 'null') {
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
            $disciplineName = $disc['name'];
            
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
            
            $disciplineStatuses[] = $disc['id'] . '|~|' . $disciplineName . '|~|' . $statusValue;
        }
        
        // Always include the teacher
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