<?php
// backend/api/update_user_status.php - Generic endpoint for all user types
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

if (!isset($input['user_id']) || !isset($input['status']) || !isset($input['user_type'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$user_id = intval($input['user_id']);
$status = intval($input['status']);
$user_type = $input['user_type'];

// Validate user type
$valid_types = ['teacher', 'postg_teacher', 'technician', 'interpreter'];
if (!in_array($user_type, $valid_types)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user type']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->connect();
    
    // Update status in user table
    $stmt = $conn->prepare("
        UPDATE user 
        SET enabled = :status 
        WHERE id = :user_id AND user_type = :user_type
    ");
    
    $stmt->execute([
        ':status' => $status,
        ':user_id' => $user_id,
        ':user_type' => $user_type
    ]);
    
    if ($stmt->rowCount() > 0) {
        // Log the status change
        error_log("User status updated: ID=$user_id, Type=$user_type, Status=$status by Admin ID=" . $_SESSION['user_id']);
        
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found or type mismatch']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>