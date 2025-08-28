<?php
// backend/api/get_filtered_technicians.php 
require_once '../classes/database.class.php';

header('Content-Type: application/json; charset=utf-8');

$name = $_GET['name'] ?? '';
$status = $_GET['status'] ?? '';

try {
    $conection = new Database();
    $conn = $conection->connect();

    // Base query with JOIN to user_roles table
    $sql = "SELECT u.* 
            FROM user u 
            JOIN user_roles ur ON u.id = ur.user_id 
            WHERE ur.role = 'tecnico'";
    $where = [];
    $params = [];

    // Apply status filter
    if ($status !== '') {
        if ($status === 'null') {
            $where[] = "(u.enabled IS NULL OR u.enabled = '')";
        } else {
            $where[] = "u.enabled = :status";
            $params[':status'] = intval($status);
        }
    }
    
    // Apply name filter
    if ($name !== '') {
        $where[] = "u.name LIKE :name";
        $params[':name'] = '%' . $name . '%';
    }
    
    // Add WHERE clause if there are conditions
    if (!empty($where)) {
        $sql .= ' AND ' . implode(' AND ', $where);
    }

    // Order by creation date
    $sql .= ' ORDER BY u.created_at ASC';

    // Execute query
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return JSON response
    echo json_encode($technicians, JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log("Database error in get_filtered_technicians.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Server error in get_filtered_technicians.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>