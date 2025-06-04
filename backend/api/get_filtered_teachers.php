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

// Add status condition if filtering by status
if ($status !== null && $status !== '') {
    if ($status === 'null') {
        $conditions[] = "(td.enabled IS NULL OR td.enabled = '')";
    } else {
        $conditions[] = "td.enabled = :status";
        $params[':status'] = $status;
    }
}

// Apply joins
foreach ($joins as $join) {
    $sql .= " " . $join;
}

// Apply conditions
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " GROUP BY t.id ORDER BY t.created_at ASC";

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