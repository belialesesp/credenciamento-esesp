<?php
// backend/api/update_teacher_evaluation.php

session_start();
require_once __DIR__ . '/../classes/database.class.php';

ob_clean();
header('Content-Type: application/json');

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Não autenticado');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data) || !isset($data['teacher_id']) || !isset($data['discipline_id']) || 
        !isset($data['evaluation_type']) || !isset($data['status'])) {
        throw new Exception('Dados inválidos');
    }
    
    $teacher_id = intval($data['teacher_id']);
    $discipline_id = intval($data['discipline_id']);
    $evaluation_type = $data['evaluation_type'];
    $status = $data['status'] === null || $data['status'] === 'null' ? null : intval($data['status']);
    $evaluator_id = $_SESSION['user_id'];
    
    // Validate evaluation type
    if (!in_array($evaluation_type, ['gese', 'pedagogico'])) {
        throw new Exception('Tipo de avaliação inválido');
    }
    
    // Check permissions
    $user_roles = $_SESSION['user_roles'] ?? [];
    $can_evaluate = false;
    
    if ($evaluation_type === 'gese' && (in_array('gese', $user_roles) || in_array('admin', $user_roles))) {
        $can_evaluate = true;
    } elseif ($evaluation_type === 'pedagogico' && (in_array('pedagogico', $user_roles) || in_array('admin', $user_roles))) {
        $can_evaluate = true;
    }
    
    if (!$can_evaluate) {
        throw new Exception('Sem permissão para realizar esta avaliação');
    }
    
    // Validate IDs
    if ($teacher_id <= 0 || $discipline_id <= 0) {
        throw new Exception('IDs inválidos');
    }
    
    if ($status !== null && !in_array($status, [0, 1])) {
        throw new Exception('Status inválido');
    }
    
    $connection = new Database();
    $conn = $connection->connect();
    
    // Determine which table to update based on the page context
    // Check if this is a postg teacher
    $checkPostgSql = "SELECT COUNT(*) FROM postg_teacher WHERE id = :teacher_id";
    $checkStmt = $conn->prepare($checkPostgSql);
    $checkStmt->execute([':teacher_id' => $teacher_id]);
    $isPostg = $checkStmt->fetchColumn() > 0;
    
    $table = $isPostg ? 'postg_teacher_disciplines' : 'teacher_disciplines';
    
    // Build update query
    $column_prefix = $evaluation_type === 'gese' ? 'gese' : 'pedagogico';
    
    $sql = "
        UPDATE $table 
        SET 
            {$column_prefix}_evaluation = :status,
            {$column_prefix}_evaluated_at = :evaluated_at,
            {$column_prefix}_evaluated_by = :evaluator_id
        WHERE teacher_id = :teacher_id 
        AND discipline_id = :discipline_id
    ";
    
    $stmt = $conn->prepare($sql);
    $params = [
        ':status' => $status,
        ':evaluated_at' => $status !== null ? date('Y-m-d H:i:s') : null,
        ':evaluator_id' => $status !== null ? $evaluator_id : null,
        ':teacher_id' => $teacher_id,
        ':discipline_id' => $discipline_id
    ];
    
    $stmt->execute($params);
    
    // Log the evaluation
    error_log("Evaluation updated: Teacher $teacher_id, Discipline $discipline_id, Type $evaluation_type, Status " . var_export($status, true));
    
    echo json_encode([
        'success' => true,
        'message' => 'Avaliação atualizada com sucesso'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>