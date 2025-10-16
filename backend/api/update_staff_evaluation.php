<?php
// backend/api/update_staff_evaluation.php
// API endpoint for technician and interpreter evaluations - FIXED for unified user table

session_start();
require_once __DIR__ . '/../classes/database.class.php';

ob_clean();
header('Content-Type: application/json');

$rawInput = file_get_contents('php://input');
error_log("STAFF EVALUATION - RAW INPUT: " . $rawInput);

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Não autenticado');
    }
    
    $data = json_decode($rawInput, true);
    error_log("STAFF EVALUATION - DECODED DATA: " . print_r($data, true));
    
    if (empty($data) || !isset($data['user_id']) || !isset($data['user_type']) ||
        !isset($data['evaluation_type']) || !isset($data['status'])) {
        throw new Exception('Dados inválidos - campos faltando');
    }
    
    $user_id = intval($data['user_id']);
    $user_type = $data['user_type']; // 'technician' or 'interpreter'
    $evaluation_type = $data['evaluation_type']; // 'gese' or 'pedagogico'
    $status = ($data['status'] === null || $data['status'] === 'null') ? null : intval($data['status']);
    $evaluator_id = $_SESSION['user_id'];
    
    error_log("PARSED: user_id=$user_id, type=$user_type, eval=$evaluation_type, status=" . var_export($status, true));
    
    // Validate user type
    if (!in_array($user_type, ['technician', 'interpreter'])) {
        throw new Exception('Tipo de usuário inválido: ' . $user_type);
    }
    
    // Validate evaluation type
    if (!in_array($evaluation_type, ['gese', 'pedagogico'])) {
        throw new Exception('Tipo de avaliação inválido: ' . $evaluation_type);
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
    
    // Validate IDs and status
    if ($user_id <= 0) {
        throw new Exception('ID de usuário inválido: ' . $user_id);
    }
    
    if ($status !== null && !in_array($status, [0, 1])) {
        throw new Exception('Status inválido: ' . $status);
    }
    
    $connection = new Database();
    $conn = $connection->connect();
    
    // FIXED: Use the unified 'user' table instead of separate technician/interpreter tables
    $table = 'user';
    
    // Verify the user exists and has the correct role
    $role = $user_type === 'technician' ? 'tecnico' : 'interprete';
    $checkSql = "SELECT COUNT(*) FROM user u 
                 INNER JOIN user_roles ur ON u.id = ur.user_id 
                 WHERE u.id = :user_id AND ur.role = :role";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([':user_id' => $user_id, ':role' => $role]);
    
    if ($checkStmt->fetchColumn() == 0) {
        throw new Exception("Usuário não encontrado ou não possui a função de $user_type (user_id=$user_id)");
    }
    
    // Build update query
    $column_prefix = $evaluation_type === 'gese' ? 'gese' : 'pedagogico';
    
    $sql = "
        UPDATE $table 
        SET 
            {$column_prefix}_evaluation = :status,
            {$column_prefix}_evaluated_at = :evaluated_at,
            {$column_prefix}_evaluated_by = :evaluator_id
        WHERE id = :user_id
    ";
    
    $params = [
        ':status' => $status,
        ':evaluated_at' => $status !== null ? date('Y-m-d H:i:s') : null,
        ':evaluator_id' => $status !== null ? $evaluator_id : null,
        ':user_id' => $user_id
    ];
    
    error_log("SQL: $sql");
    error_log("PARAMS: " . print_r($params, true));
    
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute($params);
    $rowsAffected = $stmt->rowCount();
    
    error_log("UPDATE RESULT: result=$result, rowsAffected=$rowsAffected");
    
    // Update the enabled field based on both evaluations
    $updateEnabledSql = "
        UPDATE $table t1
        JOIN (SELECT id, gese_evaluation, pedagogico_evaluation FROM $table WHERE id = :user_id) t2
        ON t1.id = t2.id
        SET t1.enabled = CASE
            WHEN t2.gese_evaluation = 1 AND t2.pedagogico_evaluation = 1 THEN 1
            WHEN t2.gese_evaluation = 0 OR t2.pedagogico_evaluation = 0 THEN 0
            ELSE NULL
        END
        WHERE t1.id = :user_id
    ";
    
    $updateStmt = $conn->prepare($updateEnabledSql);
    $updateStmt->execute([':user_id' => $user_id]);
    
    // Verify the update
    $verifySql = "SELECT {$column_prefix}_evaluation, {$column_prefix}_evaluated_at, 
                         {$column_prefix}_evaluated_by, 
                         gese_evaluation, pedagogico_evaluation, enabled 
                  FROM $table WHERE id = :user_id";
    $verifyStmt = $conn->prepare($verifySql);
    $verifyStmt->execute([':user_id' => $user_id]);
    $verifyResult = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("VERIFICATION: " . print_r($verifyResult, true));
    
    // Success response
    $response = [
        'success' => true,
        'message' => 'Avaliação atualizada com sucesso',
        'rows_affected' => $rowsAffected,
        'verification' => $verifyResult
    ];
    
    error_log("SUCCESS RESPONSE: " . print_r($response, true));
    
    echo json_encode($response);
    exit();
    
} catch (PDOException $e) {
    $errorMessage = "Erro de banco de dados: " . $e->getMessage();
    error_log("PDO ERROR CAUGHT: " . $errorMessage);
    error_log("ERROR TRACE: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $errorMessage,
        'error_type' => 'database'
    ]);
    exit();
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
    error_log("ERROR CAUGHT: " . $errorMessage);
    error_log("ERROR TRACE: " . $e->getTraceAsString());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $errorMessage,
        'error_type' => 'general'
    ]);
    exit();
}