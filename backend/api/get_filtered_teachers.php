<?php
require_once '../classes/database.class.php';

error_reporting(0);

$category = isset($_GET['category']) ? $_GET['category'] : null;
$course = isset($_GET['course']) ? $_GET['course'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;

$conection = new Database();
$conn = $conection->connect();

// Base query with discipline status information
$sql = "
    SELECT DISTINCT 
        t.*,
        GROUP_CONCAT(
            CONCAT(
                d.id, ':', 
                d.name, ':', 
                COALESCE(td.enabled, 'null')
            ) SEPARATOR '||'
        ) as discipline_statuses
    FROM teacher t
    LEFT JOIN teacher_disciplines td ON t.id = td.teacher_id
    LEFT JOIN disciplinas d ON td.discipline_id = d.id
";

$conditions = [];
$params = [];
$joins = [];

// Add activity join if filtering by category
if ($category) {
    $joins[] = "INNER JOIN teacher_activities ta ON t.id = ta.teacher_id";
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

// Apply conditions (but not status yet)
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

// Handle status filtering differently - as a HAVING clause after GROUP BY
$sql .= " GROUP BY t.id";

// Add status filter using HAVING clause to filter after grouping
if ($status !== null && $status !== '') {
    if ($status === 'null') {
        // Find teachers with at least one discipline with NULL/empty status
        $sql .= " HAVING SUM(CASE WHEN td.enabled IS NULL OR td.enabled = '' THEN 1 ELSE 0 END) > 0";
    } else {
        // Find teachers with at least one discipline with the specified status
        $sql .= " HAVING SUM(CASE WHEN td.enabled = :status THEN 1 ELSE 0 END) > 0";
        $params[':status'] = intval($status);
    }
}

$sql .= " ORDER BY t.created_at ASC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($teachers);
} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?>