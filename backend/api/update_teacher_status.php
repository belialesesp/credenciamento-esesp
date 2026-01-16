<?php
// backend/api/update_teacher_status.php - Updated for unified user table
session_start();
require_once '../classes/database.class.php';

// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id']) || !isset($input['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$user_id = intval($input['user_id']);
$status = intval($input['status']);

try {
    $database = new Database();
    $conn = $database->connect();
    
    // Update status in user table
    $stmt = $conn->prepare("
        UPDATE user 
        SET enabled = :status 
        WHERE id = :user_id AND user_type = 'teacher'
    ");
    
    $stmt->execute([
        ':status' => $status,
        ':user_id' => $user_id
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found or not a teacher']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>