<?php
// backend/api/save_contract_info.php
session_start();
require_once '../classes/database.class.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

$teacherId = filter_input(INPUT_POST, 'teacher_id', FILTER_VALIDATE_INT);
$courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$contractInfo = trim($_POST['contract_info'] ?? '');
$teacherType = ($_POST['teacher_type'] ?? 'regular') === 'postgraduate' ? 'postgraduate' : 'regular';

if (!$teacherId || !$courseId) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

try {
    $connection = new Database();
    $conn = $connection->connect();
    
    // Update the contract info for the accepted invitation
    $stmt = $conn->prepare("
        UPDATE course_invitations 
        SET contract_info = :contract_info
        WHERE teacher_id = :teacher_id 
        AND course_id = :course_id 
        AND teacher_type = :teacher_type
        AND status = 'accepted'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    
    $stmt->bindParam(':contract_info', $contractInfo);
    $stmt->bindParam(':teacher_id', $teacherId);
    $stmt->bindParam(':course_id', $courseId);
    $stmt->bindParam(':teacher_type', $teacherType);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Informações contratuais salvas']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar informações']);
    }
    
} catch (Exception $e) {
    error_log("Save contract info error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro no sistema']);
}