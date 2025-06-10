<?php
// backend/api/get_filtered_teachers.php - FIXED NULL FILTER
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

        // FIXED: Use BINARY for exact comparison to avoid type coercion
        if ($status === '1') {
            $where[] = "BINARY td.enabled = '1'";
        } else if ($status === '0') {
            $where[] = "BINARY td.enabled = '0'";
        } else if ($status === 'null') {
            // FIXED: Use BINARY to prevent 0 from matching empty string
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
    foreach ($teachers as &$teacher) {
    // Get discipline statuses
    $discSql = "
        SELECT 
            d.id,
            d.name,
            td.enabled
        FROM teacher_disciplines td
        INNER JOIN disciplinas d ON td.discipline_id = d.id
        WHERE td.teacher_id = :teacher_id
        ORDER BY d.name
    ";
    
    $discStmt = $conn->prepare($discSql);
    $discStmt->execute([':teacher_id' => $teacher['id']]);
    $disciplines = $discStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build discipline_statuses string
    $disciplineStatuses = [];
    foreach ($disciplines as $disc) {
        $cleanName = str_replace([':', '||'], ' ', $disc['name']);
        $statusValue = $disc['enabled'] ?? 'null';
        if ($statusValue === '') $statusValue = 'null';
        $disciplineStatuses[] = $disc['id'] . ':' . $cleanName . ':' . $statusValue;
    }
    
    $teacher['discipline_statuses'] = implode('||', $disciplineStatuses);
}
header('Content-Type: application/json');
echo json_encode($teachers);

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
