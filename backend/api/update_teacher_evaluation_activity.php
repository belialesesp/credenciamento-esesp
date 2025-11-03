<?php
// backend/api/update_teacher_evaluation_activity.php
require_once '../config/config.php';
require_once '../services/functions.php';

header('Content-Type: application/json');

// Check if user is logged in and has proper permissions
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Check if user is admin, GESE or Pedagogico
$can_evaluate_gese = isGESE() || (isAdmin() && !isGEDTH());
$can_evaluate_ped = isPedagogico() || (isAdmin() && !isGEDTH());

if (!$can_evaluate_gese && !$can_evaluate_ped) {
    echo json_encode(['success' => false, 'message' => 'Sem permissão para avaliar']);
    exit;
}

// Get POST data
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$discipline_id = isset($_POST['discipline_id']) ? intval($_POST['discipline_id']) : 0;
$activity_id = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : 0;
$evaluation_type = isset($_POST['evaluation_type']) ? $_POST['evaluation_type'] : '';
$value = isset($_POST['value']) && $_POST['value'] !== '' ? intval($_POST['value']) : null;

// Validate input
if (!$user_id || !$discipline_id || !$activity_id || !in_array($evaluation_type, ['gese', 'pedagogico'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

// Check permissions for specific evaluation type
if ($evaluation_type === 'gese' && !$can_evaluate_gese) {
    echo json_encode(['success' => false, 'message' => 'Sem permissão para avaliação documental']);
    exit;
}

if ($evaluation_type === 'pedagogico' && !$can_evaluate_ped) {
    echo json_encode(['success' => false, 'message' => 'Sem permissão para avaliação pedagógica']);
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();
    
    // Check if the record exists
    $checkSql = "SELECT * FROM teacher_disciplines 
                 WHERE user_id = :user_id 
                 AND discipline_id = :discipline_id 
                 AND activity_id = :activity_id";
    
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([
        ':user_id' => $user_id,
        ':discipline_id' => $discipline_id,
        ':activity_id' => $activity_id
    ]);
    
    if (!$checkStmt->fetch()) {
        // If record doesn't exist, insert it first
        $insertSql = "INSERT INTO teacher_disciplines 
                      (teacher_id, user_id, discipline_id, activity_id, created_at) 
                      VALUES 
                      (:teacher_id, :user_id, :discipline_id, :activity_id, NOW())";
        
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->execute([
            ':teacher_id' => $user_id, // For this table, teacher_id is the same as user_id
            ':user_id' => $user_id,
            ':discipline_id' => $discipline_id,
            ':activity_id' => $activity_id
        ]);
    }
    
    // Prepare the update query based on evaluation type
    if ($evaluation_type === 'gese') {
        $sql = "UPDATE teacher_disciplines 
                SET gese_evaluation = :value,
                    gese_evaluated_at = :evaluated_at,
                    gese_evaluated_by = :evaluated_by
                WHERE user_id = :user_id 
                AND discipline_id = :discipline_id
                AND activity_id = :activity_id";
    } else {
        $sql = "UPDATE teacher_disciplines 
                SET pedagogico_evaluation = :value,
                    pedagogico_evaluated_at = :evaluated_at,
                    pedagogico_evaluated_by = :evaluated_by
                WHERE user_id = :user_id 
                AND discipline_id = :discipline_id
                AND activity_id = :activity_id";
    }
    
    $stmt = $conn->prepare($sql);
    $params = [
        ':value' => $value,
        ':evaluated_at' => $value !== null ? date('Y-m-d H:i:s') : null,
        ':evaluated_by' => $value !== null ? $_SESSION['user_id'] : null,
        ':user_id' => $user_id,
        ':discipline_id' => $discipline_id,
        ':activity_id' => $activity_id
    ];
    
    $stmt->execute($params);
    
    // Check if both evaluations are complete and update enabled status
    $statusSql = "SELECT gese_evaluation, pedagogico_evaluation 
                  FROM teacher_disciplines 
                  WHERE user_id = :user_id 
                  AND discipline_id = :discipline_id
                  AND activity_id = :activity_id";
    
    $statusStmt = $conn->prepare($statusSql);
    $statusStmt->execute([
        ':user_id' => $user_id,
        ':discipline_id' => $discipline_id,
        ':activity_id' => $activity_id
    ]);
    
    $statusResult = $statusStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($statusResult) {
        $gese = $statusResult['gese_evaluation'];
        $ped = $statusResult['pedagogico_evaluation'];
        
        // Determine final status
        $finalStatus = null;
        if ($gese !== null && $ped !== null) {
            if ($gese == 1 && $ped == 1) {
                $finalStatus = 1; // Both approved
            } else {
                $finalStatus = 0; // At least one rejected
            }
        }
        
        // Update the enabled field
        $enabledSql = "UPDATE teacher_disciplines 
                       SET enabled = :enabled 
                       WHERE user_id = :user_id 
                       AND discipline_id = :discipline_id
                       AND activity_id = :activity_id";
        
        $enabledStmt = $conn->prepare($enabledSql);
        $enabledStmt->execute([
            ':enabled' => $finalStatus,
            ':user_id' => $user_id,
            ':discipline_id' => $discipline_id,
            ':activity_id' => $activity_id
        ]);
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Avaliação atualizada com sucesso']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log('Error updating teacher evaluation: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar avaliação: ' . $e->getMessage()]);
}
?>