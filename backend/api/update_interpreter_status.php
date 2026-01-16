<?php
// backend/api/update_interpreter_status.php - UPDATED VERSION

error_log('Request received: ' . file_get_contents('php://input'));

require_once __DIR__ . '/../classes/database.class.php';
require_once __DIR__ . '/../services/interprete.service.php';

ob_clean();
header('Content-Type: application/json');

try {
  $data = json_decode(file_get_contents('php://input'), true);

  if (empty($data) || !isset($data['interpreter_id']) || !array_key_exists('status', $data)) {
    throw new Exception('Dados inválidos');
  }

  $interpreter_id = intval($data['interpreter_id']);
  $status = $data['status'] === 'null' ? null : ($data['status'] === null ? null : intval($data['status']));

  // Validação adicional
  if ($interpreter_id <= 0) {
      throw new Exception('ID inválido');
  }

  if ($status !== null && !in_array($status, [0, 1])) {
      throw new Exception('Status inválido');
  }

  $conection = new Database();
  $conn = $conection->connect();

  // Update status directly using SQL
  $sql = "UPDATE interpreter SET enabled = :status WHERE id = :id";
  $stmt = $conn->prepare($sql);
  $params = [
    ':status' => $status,
    ':id' => $interpreter_id
  ];
  
  $stmt->execute($params);

  die(json_encode(['success' => true]));

} catch (Exception $e) {
  http_response_code(400);
  echo json_encode([
    'success' => false,
    'message' => $e->getMessage()
  ]);
}
?>