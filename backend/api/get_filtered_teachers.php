<?php
// backend/api/get_filtered_teachers.php - Bulletproof version
require_once '../classes/database.class.php';

header('Content-Type: application/json');

$category = $_GET['category'] ?? '';
$course = $_GET['course'] ?? '';
$status = $_GET['status'] ?? '';

try {
    $conection = new Database();
    $conn = $conection->connect();
    
    // Build list of teacher IDs that match filters
    $teacherIds = [];
    
    // Status filter
    if ($status !== '') {
        if ($status === 'null') {
            $sql = "SELECT DISTINCT teacher_id FROM teacher_disciplines WHERE enabled IS NULL";
        } else {
            $sql = "SELECT DISTINCT teacher_id FROM teacher_disciplines WHERE enabled = " . intval($status);
        }
        
        $stmt = $conn->query($sql);
        $statusTeacherIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($statusTeacherIds)) {
            // No teachers match this status
            echo json_encode([]);
            exit;
        }
        
        $teacherIds = $statusTeacherIds;
    }
    
    // Category filter
    if ($category !== '') {
        $sql = "SELECT DISTINCT teacher_id FROM teacher_activities WHERE activity_id = " . intval($category);
        $stmt = $conn->query($sql);
        $categoryTeacherIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($categoryTeacherIds)) {
            echo json_encode([]);
            exit;
        }
        
        if (!empty($teacherIds)) {
            $teacherIds = array_intersect($teacherIds, $categoryTeacherIds);
        } else {
            $teacherIds = $categoryTeacherIds;
        }
    }
    
    // Course filter
    if ($course !== '') {
        $sql = "SELECT DISTINCT teacher_id FROM teacher_disciplines WHERE discipline_id = " . intval($course);
        if ($status !== '') {
            if ($status === 'null') {
                $sql .= " AND enabled IS NULL";
            } else {
                $sql .= " AND enabled = " . intval($status);
            }
        }
        
        $stmt = $conn->query($sql);
        $courseTeacherIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($courseTeacherIds)) {
            echo json_encode([]);
            exit;
        }
        
        if (!empty($teacherIds)) {
            $teacherIds = array_intersect($teacherIds, $courseTeacherIds);
        } else {
            $teacherIds = $courseTeacherIds;
        }
    }
    
    // If no filters, get all teachers
    if ($status === '' && $category === '' && $course === '') {
        $sql = "SELECT DISTINCT id FROM teacher";
        $stmt = $conn->query($sql);
        $teacherIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // If no teachers match filters
    if (empty($teacherIds)) {
        echo json_encode([]);
        exit;
    }
    
    // Get full teacher data with discipline statuses
    $placeholders = implode(',', array_map('intval', $teacherIds));
    $sql = "
        SELECT 
            t.*,
            GROUP_CONCAT(
                CONCAT(
                    IFNULL(d.id, ''), ':', 
                    IFNULL(d.name, ''), ':', 
                    IFNULL(CAST(td.enabled AS CHAR), 'null')
                ) SEPARATOR '||'
            ) as discipline_statuses
        FROM teacher t
        LEFT JOIN teacher_disciplines td ON t.id = td.teacher_id
        LEFT JOIN disciplinas d ON td.discipline_id = d.id
        WHERE t.id IN ($placeholders)
        GROUP BY t.id
        ORDER BY t.created_at ASC
    ";
    
    $stmt = $conn->query($sql);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($teachers);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>