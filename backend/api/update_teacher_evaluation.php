<?php
// backend/api/update_teacher_evaluation.php - UPDATED WITH ACTIVITY_ID SUPPORT

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
    $activity_id = isset($data['activity_id']) ? intval($data['activity_id']) : null;
    $evaluation_type = $data['evaluation_type'];
    $status = $data['status'] === null || $data['status'] === 'null' ? null : intval($data['status']);
    $evaluator_id = $_SESSION['user_id'];
    
    error_log("PARSED VALUES: user_id=$user_id, discipline_id=$discipline_id, activity_id=$activity_id, evaluation_type=$evaluation_type, status=" . var_export($status, true));
    
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
        throw new Exception('Status inválido: ' . var_export($status, true));
    }
    
    // Connect to database
    $database = new Database();
    $conn = $database->connect();
    
    // Determine which table to use based on teacher type
    $checkRoleSql = "SELECT role FROM user_roles WHERE user_id = :user_id LIMIT 1";
    $checkStmt = $conn->prepare($checkRoleSql);
    $checkStmt->execute([':user_id' => $user_id]);
    $roleResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$roleResult) {
        throw new Exception('Usuário não encontrado');
    }
    
    $is_postgrad = ($roleResult['role'] === 'docente_pos');
    $table = $is_postgrad ? 'postg_teacher_disciplines' : 'teacher_disciplines';
    $teacher_id_field = $is_postgrad ? 'teacher_id' : 'teacher_id';
    
    error_log("Using table: $table, is_postgrad: " . ($is_postgrad ? 'yes' : 'no'));
    
    // Build the UPDATE query
    $field_map = [
        'gese' => [
            'evaluation' => 'gese_evaluation',
            'evaluated_at' => 'gese_evaluated_at',
            'evaluated_by' => 'gese_evaluated_by'
        ],
        'pedagogico' => [
            'evaluation' => 'pedagogico_evaluation',
            'evaluated_at' => 'pedagogico_evaluated_at',
            'evaluated_by' => 'pedagogico_evaluated_by'
        ]
    ];
    
    $fields = $field_map[$evaluation_type];
    
    // If status is NULL, we're resetting the evaluation
    if ($status === null) {
        $sql = "UPDATE $table 
                SET {$fields['evaluation']} = NULL,
                    {$fields['evaluated_at']} = NULL,
                    {$fields['evaluated_by']} = NULL
                WHERE user_id = :user_id 
                AND discipline_id = :discipline_id";
    } else {
        $sql = "UPDATE $table 
                SET {$fields['evaluation']} = :status,
                    {$fields['evaluated_at']} = NOW(),
                    {$fields['evaluated_by']} = :evaluator_id
                WHERE user_id = :user_id 
                AND discipline_id = :discipline_id";
    }
    
    // Add activity_id condition if provided (for specific activity evaluation)
    if ($activity_id !== null) {
        $sql .= " AND activity_id = :activity_id";
    }
    
    error_log("SQL: " . $sql);
    
    $stmt = $conn->prepare($sql);
    $params = [
        ':user_id' => $user_id,
        ':discipline_id' => $discipline_id
    ];
    
    if ($status !== null) {
        $params[':status'] = $status;
        $params[':evaluator_id'] = $evaluator_id;
    }
    
    if ($activity_id !== null) {
        $params[':activity_id'] = $activity_id;
    }
    
    error_log("PARAMS: " . print_r($params, true));
    
    $result = $stmt->execute($params);
    $rowCount = $stmt->rowCount();
    
    error_log("Execute result: " . ($result ? 'success' : 'failure') . ", rows affected: " . $rowCount);
    
    if ($rowCount === 0) {
        // No rows updated - might not exist
        throw new Exception('Nenhum registro foi atualizado. Verifique se a disciplina e atividade estão cadastradas para este docente.');
    }
    
    // Also update the legacy 'enabled' field based on both evaluations
    // Only do this if BOTH evaluations are now set
    $checkBothSql = "SELECT gese_evaluation, pedagogico_evaluation 
                     FROM $table 
                     WHERE user_id = :user_id 
                     AND discipline_id = :discipline_id";
    
    if ($activity_id !== null) {
        $checkBothSql .= " AND activity_id = :activity_id";
    }
    
    $checkStmt = $conn->prepare($checkBothSql);
    $checkParams = [
        ':user_id' => $user_id,
        ':discipline_id' => $discipline_id
    ];
    
    if ($activity_id !== null) {
        $checkParams[':activity_id'] = $activity_id;
    }
    
    $checkStmt->execute($checkParams);
    $evals = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    // Update enabled field based on both evaluations
    if ($evals && $evals['gese_evaluation'] !== null && $evals['pedagogico_evaluation'] !== null) {
        $enabled_value = null;
        
        if ($evals['gese_evaluation'] == 1 && $evals['pedagogico_evaluation'] == 1) {
            $enabled_value = 1; // Apto
        } elseif ($evals['gese_evaluation'] == 0 || $evals['pedagogico_evaluation'] == 0) {
            $enabled_value = 0; // Inapto
        }
        
        if ($enabled_value !== null) {
            $updateEnabledSql = "UPDATE $table 
                                 SET enabled = :enabled 
                                 WHERE user_id = :user_id 
                                 AND discipline_id = :discipline_id";
            
            if ($activity_id !== null) {
                $updateEnabledSql .= " AND activity_id = :activity_id";
            }
            
            $updateEnabledStmt = $conn->prepare($updateEnabledSql);
            $updateEnabledParams = [
                ':enabled' => $enabled_value,
                ':user_id' => $user_id,
                ':discipline_id' => $discipline_id
            ];
            
            if ($activity_id !== null) {
                $updateEnabledParams[':activity_id'] = $activity_id;
            }
            
            $updateEnabledStmt->execute($updateEnabledParams);
            error_log("Updated enabled field to: " . $enabled_value);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Avaliação atualizada com sucesso',
        'rows_affected' => $rowCount
    ]);
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    error_log("SQL State: " . $e->errorInfo[0]);
    error_log("Error Code: " . $e->errorInfo[1]);
    error_log("Error Message: " . $e->errorInfo[2]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro no banco de dados: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}