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
    
    // Debug logging
    error_log("=== Filter Request ===");
    error_log("Category: " . $category);
    error_log("Course: " . $course);
    error_log("Status: " . $status);
    
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
        
        // Use CAST to ensure integer comparison
        if ($status === '1') {
            $where[] = "CAST(td.enabled AS SIGNED) = 1";
        } else if ($status === '0') {
            $where[] = "CAST(td.enabled AS SIGNED) = 0";
        } else if ($status === 'null') {
            $where[] = "td.enabled IS NULL";
        }
    }
    
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY t.created_at ASC';
    
    error_log("Main query: " . $sql);
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Found " . count($teachers) . " teachers matching initial filters");
    
    // Get disciplines for each teacher
    $result = [];
    foreach ($teachers as $teacher) {
        // Build discipline query
        $discSql = "
            SELECT 
                d.id,
                d.name,
                td.enabled,
                CAST(td.enabled AS CHAR) as enabled_str
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
        
        // Apply status filter using CAST for safety
        if ($status !== '') {
            if ($status === '1') {
                $discSql .= " AND CAST(td.enabled AS SIGNED) = 1";
            } else if ($status === '0') {
                $discSql .= " AND CAST(td.enabled AS SIGNED) = 0";
            } else if ($status === 'null') {
                $discSql .= " AND td.enabled IS NULL";
            }
        }
        
        $discSql .= " ORDER BY d.name";
        
        $discStmt = $conn->prepare($discSql);
        $discStmt->execute($discParams);
        $disciplines = $discStmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Teacher " . $teacher['id'] . " (" . $teacher['name'] . ") has " . count($disciplines) . " disciplines after filtering");
        
        // Only include teacher if they have disciplines after filtering
        if (!empty($disciplines)) {
            $disciplineStatuses = [];
            foreach ($disciplines as $disc) {
                // Convert enabled to string consistently
                if ($disc['enabled'] === null) {
                    $statusValue = 'null';
                } elseif ($disc['enabled'] == 1) {
                    $statusValue = '1';
                } elseif ($disc['enabled'] == 0) {
                    $statusValue = '0';
                } else {
                    $statusValue = 'null';
                }
                
                $disciplineStatuses[] = $disc['id'] . ':' . $disc['name'] . ':' . $statusValue;
            }
            
            $teacher['discipline_statuses'] = implode('||', $disciplineStatuses);
            $result[] = $teacher;
        }
    }
    
    error_log("Returning " . count($result) . " teachers total");
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Error in get_filtered_teachers.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>