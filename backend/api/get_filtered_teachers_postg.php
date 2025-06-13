<?php
// backend/api/get_filtered_teachers_postg.php
require_once '../classes/database.class.php';

error_reporting(0);

$category = isset($_GET['category']) ? $_GET['category'] : null;
$course = isset($_GET['course']) ? $_GET['course'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;

$conection = new Database();
$conn = $conection->connect();

try {
    // Base query - we'll build it dynamically based on filters
    $sql = "
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
            t.address_id
        FROM postg_teacher t
    ";

    $conditions = [];
    $params = [];
    $joins = [];

    // Always join with disciplines to get the status info
    $joins[] = "LEFT JOIN postg_teacher_disciplines td ON t.id = td.teacher_id";
    $joins[] = "LEFT JOIN postg_disciplinas d ON td.discipline_id = d.id";

    // Add activity join if filtering by category
    if ($category) {
        $joins[] = "INNER JOIN postg_teacher_activities ta ON t.id = ta.teacher_id";
        $conditions[] = "ta.activity_id = :category";
        $params[':category'] = $category;
    }

    // Add discipline condition if filtering by course
    if ($course) {
        $conditions[] = "td.discipline_id = :course";
        $params[':course'] = $course;
    }

    // Apply joins
    foreach ($joins as $join) {
        $sql .= " " . $join;
    }

    // Apply WHERE conditions (category and course filters)
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    // Group by teacher to avoid duplicates
    $sql .= " GROUP BY t.id";

    // Handle status filtering with HAVING clause (after grouping)
    if ($status !== null && $status !== '') {
        if ($status === 'null') {
            // Find teachers with at least one discipline with NULL/pending status
            $sql .= " HAVING SUM(CASE WHEN (td.enabled IS NULL OR td.enabled = '') THEN 1 ELSE 0 END) > 0";
        } else {
            // Find teachers with at least one discipline with the specified status (0 or 1)
            $sql .= " HAVING SUM(CASE WHEN td.enabled = :status THEN 1 ELSE 0 END) > 0";
            $params[':status'] = intval($status);
        }
    }

    // Order by creation date
    $sql .= " ORDER BY t.created_at ASC";

    // Execute the main query
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // For each teacher, get their discipline statuses
    $result = [];
    foreach ($teachers as $teacher) {
        $discSql = "
            SELECT 
                d.id,
                d.name,
                td.enabled
            FROM postg_teacher_disciplines td
            INNER JOIN postg_disciplinas d ON td.discipline_id = d.id
            WHERE td.teacher_id = :teacher_id
        ";
        
        $discParams = [':teacher_id' => $teacher['id']];
        
        // If filtering by specific course, only get that discipline
        if ($course) {
            $discSql .= " AND d.id = :course";
            $discParams[':course'] = $course;
        }
        
        // If filtering by status, only get disciplines with that status
        if ($status !== null && $status !== '') {
            if ($status === 'null') {
                $discSql .= " AND (td.enabled IS NULL OR td.enabled = '')";
            } else {
                $discSql .= " AND td.enabled = :status";
                $discParams[':status'] = intval($status);
            }
        }
        
        $discSql .= " ORDER BY d.name";
        
        $discStmt = $conn->prepare($discSql);
        $discStmt->execute($discParams);
        $disciplines = $discStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Build the discipline status string
        $disciplineStatuses = [];
        foreach ($disciplines as $disc) {
            // Clean the name to avoid delimiter conflicts
            $cleanName = str_replace([':', '||'], ' ', $disc['name']);
            
            // Handle the enabled status properly
            $enabledRaw = $disc['enabled'];
            
            if ($enabledRaw === null || $enabledRaw === '') {
                $statusValue = 'null';
            } elseif ($enabledRaw == 0) {
                $statusValue = '0';
            } elseif ($enabledRaw == 1) {
                $statusValue = '1';
            } else {
                $statusValue = 'null';
            }
            
            $disciplineStatuses[] = $disc['id'] . ':' . $cleanName . ':' . $statusValue;
        }
        
        // Only include teachers that have matching disciplines
        if (!empty($disciplineStatuses)) {
            $teacher['discipline_statuses'] = implode('||', $disciplineStatuses);
            $result[] = $teacher;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error in get_filtered_teachers_postg.php: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>