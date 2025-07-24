<?php
// backend/api/get_filtered_interpreters.php
require_once '../classes/database.class.php';

header('Content-Type: application/json; charset=utf-8');

$name = $_GET['name'] ?? '';
$status = $_GET['status'] ?? '';

try {
    $conection = new Database();
    $conn = $conection->connect();

    // Base query
    $sql = "SELECT * FROM interpreter";
    $where = [];
    $params = [];

    // Apply status filter
    if ($status !== '') {
        if ($status === 'null') {
            $where[] = "(enabled IS NULL OR enabled = '')";
        } else {
            $where[] = "enabled = :status";
            $params[':status'] = intval($status);
        }
    }
    
    if ($name !== '') {
        $where[] = "name LIKE :name";
        $params[':name'] = '%' . $name . '%';
    }
    // Add WHERE clause if there are conditions
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    // Order by creation date
    $sql .= ' ORDER BY created_at ASC';

    // Execute query
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $interpreters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return JSON response
    echo json_encode($interpreters);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
