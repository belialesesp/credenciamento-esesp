<?php
// backend/api/update_teacher_discipline_status.php

error_log('Request received: ' . file_get_contents('php://input'));

require_once __DIR__ . '/../classes/database.class.php';

ob_clean();
header('Content-Type: application/json');

try {
  $data = json_decode(file_get_contents('php://input'), true);

  if (empty($data) || !isset($data['teacher_id']) || !isset($data['discipline_id']) || !isset($data['status'])) {
    throw new Exception('Dados inválidos');
  }

  $teacher_id = intval($data['teacher_id']);
  $discipline_id = intval($data['discipline_id']);
  $status = $data['status'] === 'null' ? null : intval($data['status']);

  // Validação adicional
  if ($teacher_id <= 0 || $discipline_id <= 0) {
      throw new Exception('IDs inválidos');
  }

  if ($status !== null && !in_array($status, [0, 1])) {
      throw new Exception('Status inválido');
  }

  $conection = new Database();
  $conn = $conection->connect();

  // Update the status for specific teacher-discipline combination
  $sql = "
    UPDATE teacher_disciplines 
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
  
  $stmt->execute($params);

  die(json_encode(['success' => true]));

} catch (Exception $e) {
  http_response_code(400);
  echo json_encode([
    'success' => false,
    'message' => $e->getMessage()
  ]);
}