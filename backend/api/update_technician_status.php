<?php

error_log('Request received: ' . file_get_contents('php://input'));

require_once __DIR__ . '/../classes/database.class.php';
require_once __DIR__ . '/../services/technician.service.php';

ob_clean();
header('Content-Type: application/json');


try {
  $data = json_decode(file_get_contents('php://input'), true);

  if (empty($data) || !isset($data['technician_id']) || !isset($data['status'])) {
    throw new Exception('Dados inválidos');
  }

  $technician_id = intval($data['technician_id']);
  $status = intval($data['status']);

  // Validação adicional
  if ($technician_id <= 0 || !in_array($status, [0, 1])) {
      throw new Exception('Valores inválidos');
  }

  $conection = new Database();
  $conn = $conection->connect();

  $technicianService = new TechnicianService($conn);
  $technicianService->updateStatus($technician_id, $status);

  die(json_encode(['success' => true]));

} catch (Exception $e) {
  http_response_code(400);
  echo json_encode([
    'success' => false,
    'message' => $e->getMessage()
  ]);
}

