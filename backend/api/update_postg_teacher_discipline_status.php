<?php
// backend/api/update_postg_teacher_discipline_status.php - DEBUG VERSION

$rawInput = file_get_contents('php://input');
error_log('Raw input received: ' . $rawInput);

require_once __DIR__ . '/../classes/database.class.php';

ob_clean();
header('Content-Type: application/json');

try {
  $data = json_decode($rawInput, true);
  
  error_log('Decoded data: ' . print_r($data, true));
  error_log('JSON decode error: ' . json_last_error_msg());
  
  if (empty($data)) {
    error_log('Data is empty');
    throw new Exception('Dados vazios - JSON: ' . json_last_error_msg());
  }
  
  if (!isset($data['teacher_id'])) {
    error_log('teacher_id not set');
    throw new Exception('teacher_id não encontrado');
  }
  
  if (!isset($data['discipline_id'])) {
    error_log('discipline_id not set');
    throw new Exception('discipline_id não encontrado');
  }
  
  if (!isset($data['status'])) {
    error_log('status not set');
    throw new Exception('status não encontrado');
  }

  $teacher_id = intval($data['teacher_id']);
  $discipline_id = intval($data['discipline_id']);
  $status = $data['status'] === 'null' ? null : intval($data['status']);

  error_log("Processing: teacher_id=$teacher_id, discipline_id=$discipline_id, status=" . var_export($status, true));

  // Validação adicional
  if ($teacher_id <= 0) {
      throw new Exception('teacher_id inválido: ' . $teacher_id);
  }
  
  if ($discipline_id <= 0) {
      throw new Exception('discipline_id inválido: ' . $discipline_id);
  }

  if ($status !== null && !in_array($status, [0, 1])) {
      throw new Exception('Status inválido: ' . var_export($status, true));
  }

  $conection = new Database();
  $conn = $conection->connect();

  // Check if the record exists first
  $checkSql = "SELECT COUNT(*) FROM postg_teacher_disciplines WHERE teacher_id = :teacher_id AND discipline_id = :discipline_id";
  $checkStmt = $conn->prepare($checkSql);
  $checkStmt->execute([':teacher_id' => $teacher_id, ':discipline_id' => $discipline_id]);
  $exists = $checkStmt->fetchColumn();
  
  error_log("Record exists check: $exists");
  
  if ($exists == 0) {
      throw new Exception("Registro não encontrado: teacher_id=$teacher_id, discipline_id=$discipline_id");
  }

  // Update the status for specific teacher-discipline combination in postg table
  $sql = "
    UPDATE postg_teacher_disciplines 
    SET enabled = :status
    WHERE teacher_id = :teacher_id 
    AND discipline_id = :discipline_id
  ";

  $stmt = $conn->prepare($sql);
  $params = [
    ':status' => $status,
    ':teacher_id' => $teacher_id,
    ':discipline_id' => $discipline_id
  ];
  
  $result = $stmt->execute($params);
  $affected = $stmt->rowCount();
  
  error_log("Update result: $result, affected rows: $affected");

  echo json_encode([
    'success' => true,
    'message' => "Status atualizado com sucesso",
    'affected_rows' => $affected
  ]);

} catch (Exception $e) {
  error_log('Exception caught: ' . $e->getMessage());
  http_response_code(400);
  echo json_encode([
    'success' => false,
    'message' => $e->getMessage()
  ]);
}
?>