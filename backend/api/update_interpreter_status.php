<?php

error_log('Request received: ' . file_get_contents('php://input'));

require_once __DIR__ . '/../classes/database.class.php';
require_once __DIR__ . '/../services/interprete.service.php';

ob_clean();
header('Content-Type: application/json');


try {
  $data = json_decode(file_get_contents('php://input'), true);

  if (empty($data) || !isset($data['interpreter_id']) || !isset($data['status'])) {
    throw new Exception('Dados inválidos');
  }

  $interpreter_id = intval($data['interpreter_id']);
  $status = intval($data['status']);

  // Validação adicional
  if ($interpreter_id <= 0 || !in_array($status, [0, 1])) {
      throw new Exception('Valores inválidos');
  }

  $conection = new Database();
  $conn = $conection->connect();

  $interpreterService = new InterpreterService($conn);
  $interpreterService->updateStatus($interpreter_id, $status);

  die(json_encode(['success' => true]));

} catch (Exception $e) {
  http_response_code(400);
  echo json_encode([
    'success' => false,
    'message' => $e->getMessage()
  ]);
}

