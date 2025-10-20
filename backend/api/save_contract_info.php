<?php
// backend/api/save_contract_info.php
session_start();
require_once '../classes/database.class.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

// Check if user has permission to edit contract info
// Only admin and GESE can edit contract info
$user_roles = $_SESSION['user_roles'] ?? [];
$is_admin = in_array('admin', $user_roles);
$is_gese = in_array('gese', $user_roles);

// Check if user has permission (admin or GESE only)
if (!$is_admin && !$is_gese) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

// Common parameters
$userId = filter_input(INPUT_POST, 'teacher_id', FILTER_VALIDATE_INT);
$contractInfo = trim($_POST['contract_info'] ?? '');

// Parameters for course contracts
$courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$teacherType = ($_POST['teacher_type'] ?? 'regular') === 'postgraduate' ? 'postgraduate' : 'regular';

// New parameter for staff contracts
$isStaff = isset($_POST['is_staff']) ? filter_var($_POST['is_staff'], FILTER_VALIDATE_BOOLEAN) : false;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'ID do usuário é obrigatório']);
    exit;
}

// For course contracts, course ID is required
if (!$isStaff && !$courseId) {
    echo json_encode(['success' => false, 'message' => 'ID do curso é obrigatório para professores']);
    exit;
}

try {
    $connection = new Database();
    $conn = $connection->connect();

    if ($isStaff) {
        // Handle staff contract info
        $stmt = $conn->prepare("
            UPDATE staff_invitations 
            SET contract_info = :contract_info
            WHERE user_id = :user_id
            AND status = 'accepted'
            ORDER BY created_at DESC
            LIMIT 1
        ");

        $stmt->bindParam(':contract_info', $contractInfo);
        $stmt->bindParam(':user_id', $userId);
    } else {
        
        $stmt = $conn->prepare("
        UPDATE course_invitations 
        SET contract_info = :contract_info
        WHERE user_id = :user_id 
        AND course_id = :course_id 
        AND teacher_type = :teacher_type
        AND status = 'accepted'
        ORDER BY created_at DESC
        LIMIT 1
        ");

        $stmt->bindParam(':contract_info', $contractInfo);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':course_id', $courseId);
        $stmt->bindParam(':teacher_type', $teacherType);
    }

    if ($stmt->execute()) {
        $message = $isStaff
            ? 'Informações contratuais do técnico/intérprete salvas'
            : 'Informações contratuais do professor salvas';

        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar informações']);
    }
} catch (Exception $e) {
    error_log("Save contract info error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro no sistema']);
}
