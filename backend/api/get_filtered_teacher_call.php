<?php
// backend/api/get_filtered_teacher_call.php - FIXED VERSION
require_once '../classes/database.class.php';

error_reporting(0);

$category = isset($_GET['category']) ? $_GET['category'] : null;
$course = isset($_GET['course']) ? $_GET['course'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;
$date = '2025-02-28'; // Data atual como padrão

$conection = new Database();
$conn = $conection->connect();

// In backend/api/get_filtered_teacher_call.php
$sql = "
  SELECT
    t.id,
    t.name,
    td.enabled,
    td.called_at as discipline_called_at,
    t.called_at as teacher_called_at,
    d.name AS course,
    d.id AS course_id,
    td.created_at,
    a.name AS category
  FROM 
    teacher AS t
  LEFT JOIN
    teacher_disciplines AS td
    ON td.teacher_id = t.id
  LEFT JOIN
    disciplinas AS d 
    ON td.discipline_id = d.id
  LEFT JOIN
    teacher_activities AS ta 
    ON ta.teacher_id = t.id
  LEFT JOIN
    activities AS a 
    ON a.id = ta.activity_id
  WHERE
    d.name is NOT NULL
  AND
    td.created_at < :date
";

$params = [':date' => $date];

// Apply filters
$conditions = [];

if ($category) {
    $conditions[] = "a.id = :category";
    $params[':category'] = $category;
}

if ($course) {
    $conditions[] = "d.id = :course";
    $params[':course'] = $course;
}

// FIXED: Handle both 'pending' and 'null' values for Aguardando status
if ($status !== null && $status !== '') {
    if ($status === 'pending' || $status === 'null') {
        // For "Aguardando" status - check for NULL or empty string
        // Use BINARY to prevent '0' from matching empty string
        $conditions[] = "(td.enabled IS NULL OR BINARY td.enabled = '')";
    } else if ($status === '1') {
        // For "Apto" status
        $conditions[] = "BINARY td.enabled = '1'";
    } else if ($status === '0') {
        // For "Inapto" status
        $conditions[] = "BINARY td.enabled = '0'";
    } else {
        // Handle any other status values
        $conditions[] = "td.enabled = :status";
        $params[':status'] = $status;
    }
}

// Add conditions to query
if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

// Order
$sql .= " ORDER BY d.name ASC, a.name ASC, td.created_at ASC";

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