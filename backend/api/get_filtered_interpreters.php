<?php
// backend/api/get_filtered_interpreters.php - Example with name filter
require_once '../classes/database.class.php';

header('Content-Type: application/json; charset=utf-8');

// Get filter parameters
$status = $_GET['status'] ?? '';
$name = $_GET['name'] ?? '';

try {
    $conection = new Database();
    $conn = $conection->connect();
    
    // Base query
    $sql = "SELECT * FROM interpreter WHERE 1=1";
    $conditions = [];
    $params = [];
    
    // Add status filter
    if ($status !== '') {
        if ($status === 'null') {
            $conditions[] = "(enabled IS NULL OR enabled = '')";
        } else {
            $conditions[] = "enabled = :status";
            $params[':status'] = $status;
        }
    }
    
    // Add name filter
    if ($name !== '') {
        $conditions[] = "name LIKE :name";
        $params[':name'] = '%' . $name . '%';
    }
    
    // Build final query
    if (!empty($conditions)) {
        $sql .= " AND " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY name ASC";
    
    // Execute query
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $interpreters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return JSON response
    echo json_encode($interpreters, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error in get_filtered_interpreters.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>