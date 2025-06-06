<?php
// backend/api/test_apto_filter.php
require_once '../classes/database.class.php';

header('Content-Type: application/json');

$conection = new Database();
$conn = $conection->connect();

// Simple test: just get all teachers with enabled = 1 disciplines
$sql = "
    SELECT DISTINCT
        t.id,
        t.name,
        t.email,
        t.phone,
        t.created_at,
        GROUP_CONCAT(
            CONCAT(d.id, ':', d.name, ':', COALESCE(td.enabled, 'null'))
            ORDER BY d.name
            SEPARATOR '||'
        ) as discipline_statuses
    FROM teacher t
    INNER JOIN teacher_disciplines td ON t.id = td.teacher_id
    INNER JOIN disciplinas d ON td.discipline_id = d.id
    WHERE td.enabled = 1
    GROUP BY t.id
";

$stmt = $conn->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Also get the count
$countSql = "SELECT COUNT(DISTINCT teacher_id) as count FROM teacher_disciplines WHERE enabled = 1";
$countStmt = $conn->query($countSql);
$count = $countStmt->fetch(PDO::FETCH_ASSOC);

$response = [
    'debug' => [
        'total_teachers_with_enabled_1' => $count['count'],
        'query' => $sql
    ],
    'teachers' => $results
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>