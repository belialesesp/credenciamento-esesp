<?php
require_once '../classes/database.class.php';

error_reporting(0);

$category = isset($_GET['category']) ? $_GET['category'] : null;
$course = isset($_GET['course']) ? $_GET['course'] : null;


$conection = new Database();
$conn = $conection->connect();

if (!$category && !$course) {
    $sql = "SELECT * FROM teacher ORDER BY created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($teachers);
    exit;
}

$sql = "SELECT DISTINCT t.* FROM teacher t";
$conditions = [];
$params = [];
    
if ($category) {
    $sql .= " INNER JOIN teacher_activities ta ON t.id = ta.teacher_id";
    $conditions[] = "ta.activity_id = ?";
    $params[] = $category;
}

if ($course) {
    $sql .= " INNER JOIN teacher_disciplines td ON t.id = td.teacher_id";
    $conditions[] = "td.discipline_id = ?";
    $params[] = $course;
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= "ORDER BY called_at ASC, created_at ASC";

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