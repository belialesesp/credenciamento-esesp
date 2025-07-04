<?php
require_once '../classes/database.class.php';

error_reporting(0);

$category = isset($_GET['category']) ? $_GET['category'] : null;
$course = isset($_GET['course']) ? $_GET['course'] : null;
$date = '2025-02-28'; // Data atual como padrão

$conection = new Database();
$conn = $conection->connect();

$sql = "
  SELECT
    t.id,
    t.name,
    td.enabled,
    COALESCE(DATE_FORMAT(td.called_at, '%d/%m/%Y'), '') as discipline_called_at,
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
  AND
    td.enabled = 1
";

$params = [':date' => $date];

if (!$category && !$course) {
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