<?php
// backend/api/update_teacherpostg_status.php - FIXED VERSION

error_log('Request received: ' . file_get_contents('php://input'));

require_once __DIR__ . '/../classes/database.class.php';

ob_clean();
header('Content-Type: application/json');

try {
  $data = json_decode(file_get_contents('php://input'), true);

  if (empty($data) || !isset($data['teacher_id']) || !isset($data['status'])) {
    throw new Exception('Dados inválidos');
  }

  $teacher_id = intval($data['teacher_id']);
  $status = intval($data['status']);

  // Validação adicional
  if ($teacher_id <= 0 || !in_array($status, [0, 1])) {
      throw new Exception('Valores inválidos');
  }

  $conection = new Database();
  $conn = $conection->connect();

  // Check if teacher has any disciplines
  $checkSql = "SELECT COUNT(*) FROM postg_teacher_disciplines WHERE teacher_id = :teacher_id";
  $checkStmt = $conn->prepare($checkSql);
  $checkStmt->execute([':teacher_id' => $teacher_id]);
  
  if ($checkStmt->fetchColumn() == 0) {
      throw new Exception('Nenhuma disciplina encontrada para este professor');
  }

  // Update ALL disciplines for this teacher (NOT the teacher table!)
  $sql = "
    UPDATE postg_teacher_disciplines 
    SET enabled = :status
    WHERE teacher_id = :teacher_id
  ";

  $stmt = $conn->prepare($sql);
  $stmt->execute([
    ':status' => $status,
    ':teacher_id' => $teacher_id
  ]);
  
  $affected = $stmt->rowCount();

  die(json_encode([
    'success' => true,
    'message' => "Atualizado $affected disciplinas",
    'affected_rows' => $affected
  ]));

} catch (Exception $e) {
  http_response_code(400);
  echo json_encode([
    'success' => false,
    'message' => $e->getMessage()
  ]);
}
?>