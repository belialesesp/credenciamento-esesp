<?php
// backend/api/get_filtered_teachers_postg.php - FIXED NULL FILTER
require_once '../classes/database.class.php';

error_reporting(0);

$category = isset($_GET['category']) ? $_GET['category'] : null;
$course = isset($_GET['course']) ? $_GET['course'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;

$conection = new Database();
$conn = $conection->connect();

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
        t.address_id,
        t.enabled
    FROM postg_teacher t
";

$conditions = [];
$params = [];
$joins = [];

// Add activity join if filtering by category
if ($category) {
    $joins[] = "INNER JOIN postg_teacher_activities ta ON t.id = ta.teacher_id";
    $conditions[] = "ta.activity_id = :category";
    $params[':category'] = $category;
}

// Add discipline join if filtering by course or status
if ($course || $status !== null) {
    $joins[] = "INNER JOIN postg_teacher_disciplines td ON t.id = td.teacher_id";
    
    if ($course) {
        $conditions[] = "td.discipline_id = :course";
        $params[':course'] = $course;
    }
    
    // FIXED: Apply status filter with BINARY for exact comparison
    if ($status === '1') {
        $conditions[] = "BINARY td.enabled = '1'";
    } else if ($status === '0') {
        $conditions[] = "BINARY td.enabled = '0'";
    } else if ($status === 'null') {
        // FIXED: Use BINARY to prevent 0 from matching empty string
        $conditions[] = "(td.enabled IS NULL OR BINARY td.enabled = '')";
    }
}

// Apply joins
foreach ($joins as $join) {
    $sql .= " " . $join;
}

// Apply WHERE conditions
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

// Order by creation date
$sql .= " ORDER BY t.created_at ASC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Now get discipline information for each teacher
    $result = [];
    foreach ($teachers as $teacher) {
        // Get disciplines for this teacher
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
        
        if ($course) {
            $discSql .= " AND d.id = :course";
            $discParams[':course'] = $course;
        }
        
        // FIXED: Apply status filter to discipline query with BINARY
        if ($status === '1') {
            $discSql .= " AND BINARY td.enabled = '1'";
        } else if ($status === '0') {
            $discSql .= " AND BINARY td.enabled = '0'";
        } else if ($status === 'null') {
            // FIXED: Use BINARY to prevent 0 from matching empty string
            $discSql .= " AND (td.enabled IS NULL OR BINARY td.enabled = '')";
        }
        
        $discSql .= " ORDER BY d.name";
        
        $discStmt = $conn->prepare($discSql);
        $discStmt->execute($discParams);
        $disciplines = $discStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Only include teacher if they have matching disciplines
        if (!empty($disciplines)) {
            $disciplineStatuses = [];
            
            foreach ($disciplines as $disc) {
                // Clean discipline name to prevent parsing issues
                $cleanName = str_replace([':', '||'], ' ', $disc['name']);
                
                // Determine status value
                $enabledRaw = $disc['enabled'];
                
                if ($enabledRaw === null) {
                    $statusValue = 'null';
                } elseif ($enabledRaw === '') {
                    $statusValue = 'null';  
                } elseif ($enabledRaw == 0 && !is_null($enabledRaw)) {
                    $statusValue = '0';
                } elseif ($enabledRaw == 1) {
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
    
    header('Content-Type: application/json');
    echo json_encode($result);
} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?>