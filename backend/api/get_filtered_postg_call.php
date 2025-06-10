<?php
// backend/api/get_filtered_postg_call.php - FIXED NULL FILTER
require_once '../classes/database.class.php';

error_reporting(0);

$category = isset($_GET['category']) ? $_GET['category'] : null;
$course = isset($_GET['course']) ? $_GET['course'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;
$date = '2025-02-28'; // Data atual como padrão

$conection = new Database();
$conn = $conection->connect();

$sql = "
  SELECT
    t.id,
    t.name,
    t.enabled,
    d.name AS course,
    td.created_at,
    a.name AS category
  FROM 
    postg_teacher AS t
  LEFT JOIN
    postg_teacher_disciplines AS td
      ON td.teacher_id = t.id
  LEFT JOIN
    postg_disciplinas AS d 
      ON td.discipline_id = d.id
   LEFT JOIN
   	postg_teacher_activities AS ta
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

if (!$category && !$course && !$status) {
  try {
    $sql .= " ORDER BY d.name ASC, a.name ASC, td.created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($teachers);
    exit;
  } catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
  }
}

// Condições de filtro
$conditions = [];

if ($category) {
    $conditions[] = "a.id = :category";
    $params[':category'] = $category;
}

if ($course) {
    $conditions[] = "d.id = :course";
    $params[':course'] = $course;
}

// FIXED: Apply status filter with BINARY for exact comparison
if ($status !== null && $status !== '') {
    if ($status === 'pending' || $status === 'null') {
        // FIXED: Use BINARY to prevent 0 from matching empty string
        $conditions[] = "(td.enabled IS NULL OR BINARY td.enabled = '')";
    } else if ($status === '1') {
        $conditions[] = "BINARY td.enabled = '1'";
    } else if ($status === '0') {
        $conditions[] = "BINARY td.enabled = '0'";
    }
}

// Adiciona as condições à consulta
if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

// Ordem
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