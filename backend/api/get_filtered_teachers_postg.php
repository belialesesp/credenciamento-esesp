<?php
// backend/api/get_filtered_teachers_postg.php - FIXED VERSION
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
        GROUP_CONCAT(
            DISTINCT CONCAT(
                d.id, ':', 
                d.name, ':', 
                COALESCE(td.enabled, 'null')
            ) SEPARATOR '||'
        ) as discipline_statuses
    FROM postg_teacher t
";

$conditions = [];
$params = [];
$joins = [];

// Always join with disciplines to get the status info
$joins[] = "LEFT JOIN postg_teacher_disciplines td ON t.id = td.teacher_id";
$joins[] = "LEFT JOIN postg_disciplinas d ON td.discipline_id = d.id";

// Add activity join if filtering by category
if ($category) {
    $joins[] = "INNER JOIN postg_teacher_activities ta ON t.id = ta.teacher_id";
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

// Apply WHERE conditions (category and course filters)
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

// Group by teacher
$sql .= " GROUP BY t.id";

// FIXED: Handle status filtering with HAVING clause
if ($status !== null && $status !== '') {
    if ($status === 'null' || $status === 'pending') {
        // Find teachers with at least one discipline with NULL/pending status
        // Use BINARY in the CASE statement to ensure exact comparison
        $sql .= " HAVING SUM(CASE WHEN (td.enabled IS NULL OR BINARY td.enabled = '') THEN 1 ELSE 0 END) > 0";
    } else if ($status === '1') {
        // Find teachers with at least one discipline with status '1'
        $sql .= " HAVING SUM(CASE WHEN BINARY td.enabled = '1' THEN 1 ELSE 0 END) > 0";
    } else if ($status === '0') {
        // Find teachers with at least one discipline with status '0'
        $sql .= " HAVING SUM(CASE WHEN BINARY td.enabled = '0' THEN 1 ELSE 0 END) > 0";
    } else {
        // Handle any other status values
        $sql .= " HAVING SUM(CASE WHEN BINARY td.enabled = :status THEN 1 ELSE 0 END) > 0";
        $params[':status'] = $status;
    }
}

// Order by creation date
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