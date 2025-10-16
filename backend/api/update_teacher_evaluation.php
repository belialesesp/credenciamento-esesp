<?php
// backend/api/update_teacher_evaluation.php - FIXED VERSION

session_start();
require_once __DIR__ . '/../classes/database.class.php';

// Clear any previous output
ob_clean();
header('Content-Type: application/json');

// Logging for debugging
$rawInput = file_get_contents('php://input');
error_log("RAW INPUT: " . $rawInput);

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Não autenticado');
    }
    
    $data = json_decode($rawInput, true);
    error_log("DECODED DATA: " . print_r($data, true));
    
    if (empty($data) || !isset($data['teacher_id']) || !isset($data['discipline_id']) || 
        !isset($data['evaluation_type']) || !isset($data['status'])) {
        throw new Exception('Dados inválidos - campos faltando');
    }
    
    $user_id = intval($data['teacher_id']); 
    $discipline_id = intval($data['discipline_id']);
    $evaluation_type = $data['evaluation_type'];
    $status = $data['status'] === null || $data['status'] === 'null' ? null : intval($data['status']);
    $evaluator_id = $_SESSION['user_id'];
    
    error_log("PARSED VALUES: user_id=$user_id, discipline_id=$discipline_id, evaluation_type=$evaluation_type, status=" . var_export($status, true));
    
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
    
    // Validate IDs
    if ($user_id <= 0 || $discipline_id <= 0) {
        throw new Exception('IDs inválidos: user_id=' . $user_id . ', discipline_id=' . $discipline_id);
    }
    
    if ($status !== null && !in_array($status, [0, 1])) {
        throw new Exception('Status inválido: ' . $status);
    }
    
    $connection = new Database();
    $conn = $connection->connect();
    
    // FIXED: Determine table by checking which table has this user_id + discipline_id combination
    // First check teacher_disciplines (regular teachers)
    $checkRegularSql = "SELECT COUNT(*) FROM teacher_disciplines WHERE user_id = :user_id AND discipline_id = :discipline_id";
    $checkRegularStmt = $conn->prepare($checkRegularSql);
    $checkRegularStmt->execute([':user_id' => $user_id, ':discipline_id' => $discipline_id]);
    $existsInRegular = $checkRegularStmt->fetchColumn() > 0;
    
    // Then check postg_teacher_disciplines (postgraduate teachers)
    $checkPostgSql = "SELECT COUNT(*) FROM postg_teacher_disciplines WHERE user_id = :user_id AND discipline_id = :discipline_id";
    $checkPostgStmt = $conn->prepare($checkPostgSql);
    $checkPostgStmt->execute([':user_id' => $user_id, ':discipline_id' => $discipline_id]);
    $existsInPostg = $checkPostgStmt->fetchColumn() > 0;
    
    // Determine which table to use based on where the record actually exists
    if ($existsInRegular && !$existsInPostg) {
        $table = 'teacher_disciplines';
        $isPostg = false;
    } elseif ($existsInPostg && !$existsInRegular) {
        $table = 'postg_teacher_disciplines';
        $isPostg = true;
    } elseif ($existsInRegular && $existsInPostg) {
        // Edge case: exists in both (shouldn't happen, but handle it)
        // Prefer the table that matches the discipline table
        $disciplineTableCheck = "SELECT COUNT(*) FROM postg_disciplinas WHERE id = :discipline_id";
        $disciplineStmt = $conn->prepare($disciplineTableCheck);
        $disciplineStmt->execute([':discipline_id' => $discipline_id]);
        $isPostgDiscipline = $disciplineStmt->fetchColumn() > 0;
        
        $table = $isPostgDiscipline ? 'postg_teacher_disciplines' : 'teacher_disciplines';
        $isPostg = $isPostgDiscipline;
        error_log("WARNING: Record exists in both tables. Using $table based on discipline type.");
    } else {
        throw new Exception("Registro não encontrado em nenhuma tabela para user_id=$user_id e discipline_id=$discipline_id");
    }
    
    error_log("TABLE TO UPDATE: $table (existsInRegular: $existsInRegular, existsInPostg: $existsInPostg)");
    
    // Build update query
    $column_prefix = $evaluation_type === 'gese' ? 'gese' : 'pedagogico';
    
    $sql = "
        UPDATE $table 
        SET 
            {$column_prefix}_evaluation = :status,
            {$column_prefix}_evaluated_at = :evaluated_at,
            {$column_prefix}_evaluated_by = :evaluator_id
        WHERE user_id = :user_id 
        AND discipline_id = :discipline_id
    ";
    
    $params = [
        ':status' => $status,
        ':evaluated_at' => $status !== null ? date('Y-m-d H:i:s') : null,
        ':evaluator_id' => $status !== null ? $evaluator_id : null,
        ':user_id' => $user_id,
        ':discipline_id' => $discipline_id
    ];
    
    error_log("SQL: $sql");
    error_log("PARAMS: " . print_r($params, true));
    
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute($params);
    $rowsAffected = $stmt->rowCount();
    
    error_log("UPDATE RESULT: result=$result, rowsAffected=$rowsAffected");
    
    if ($rowsAffected === 0) {
        error_log("WARNING: No rows were updated. Values may already be the same.");
    }
    
    // Verify the update
    $verifySql = "SELECT {$column_prefix}_evaluation, {$column_prefix}_evaluated_at, {$column_prefix}_evaluated_by, enabled FROM $table WHERE user_id = :user_id AND discipline_id = :discipline_id";
    $verifyStmt = $conn->prepare($verifySql);
    $verifyStmt->execute([':user_id' => $user_id, ':discipline_id' => $discipline_id]);
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
    
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
    error_log("ERROR CAUGHT: " . $errorMessage);
    error_log("ERROR TRACE: " . $e->getTraceAsString());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $errorMessage
    ]);
    exit();
}
?>