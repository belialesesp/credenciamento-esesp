<?php
// backend/api/get_filtered_teachers_postg.php
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
            FROM postg_teacher t
            LEFT JOIN postg_disciplinas td ON t.id = td.teacher_id
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

    // Base query for postgraduate teachers
    $sql = "SELECT DISTINCT t.* FROM postg_teacher t";
    $joins = [];
    $where = [];
    $params = [];
    
    // Handle category filter
    if ($category !== '') {
        $joins[] = "INNER JOIN postg_teacher_activities ta ON t.id = ta.teacher_id";
        $where[] = "ta.activity_id = :category";
        $params[':category'] = $category;
    }
    
    // Handle course and/or status filters
    if ($status !== '' || $course !== '') {
        $joins[] = "INNER JOIN postg_teacher_disciplines td ON t.id = td.teacher_id";
        
        if ($course !== '') {
            $where[] = "td.discipline_id = :course";
            $params[':course'] = $course;
        }
        
        // Use BINARY for exact comparison to avoid type coercion
        if ($status === '1') {
            $where[] = "BINARY td.enabled = '1'";
        } else if ($status === '0') {
            $where[] = "BINARY td.enabled = '0'";
        } else if ($status === 'null') {
            $where[] = "(td.enabled IS NULL OR td.enabled = '')";
        }
    }
    
    // Build the complete query
    $sql .= ' ' . implode(' ', $joins);
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY t.created_at ASC';
    
    // Execute query to get filtered teacher IDs
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $filteredTeachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no teachers found, return empty array
    if (empty($filteredTeachers)) {
        echo json_encode([]);
        exit;
    }
    
    // Get teacher IDs
    $teacherIds = array_column($filteredTeachers, 'id');
    $idPlaceholders = implode(',', array_fill(0, count($teacherIds), '?'));
    
    // Now get complete teacher data with discipline statuses
    $detailSql = "
        SELECT DISTINCT
            t.id,
            t.name,
            t.email,
            t.phone,
            t.cpf,
            t.created_at,
            t.called_at,
            t.document_number,
            t.document_emissor,
            t.document_uf,
            t.special_needs,
            t.address_id,
            GROUP_CONCAT(
                CONCAT(
                    d.id, ':', 
                    d.name, ':', 
                    COALESCE(td.enabled, 'null')
                ) SEPARATOR '||'
            ) as discipline_statuses
        FROM postg_teacher t
        LEFT JOIN postg_teacher_disciplines td ON t.id = td.teacher_id
        LEFT JOIN postg_disciplinas d ON td.discipline_id = d.id
        WHERE t.id IN ($idPlaceholders)
        GROUP BY t.id
        ORDER BY t.created_at ASC
    ";
    
    $detailStmt = $conn->prepare($detailSql);
    $detailStmt->execute($teacherIds);
    $teachers = $detailStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($teachers);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>