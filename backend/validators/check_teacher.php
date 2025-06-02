<?php
include_once('../classes/database.class.php');

$conection = new Database();
$conn = $conection->connect();

// Verificar conexão

// Verificar se o CPF foi enviado
if (!isset($_POST['cpf']) || empty($_POST['cpf'])) {
    echo json_encode([
        'success' => false,
        'message' => 'CPF não informado'
    ]);
    exit;
}

$cpf = $_POST['cpf'];



$sql = "
    SELECT 
      *
    FROM 
      teacher
    WHERE
      cpf = :cpf
  ";

  $query_run = $conn->prepare($sql);

  $stmt = $conn->prepare($sql);
  $stmt->bindParam(":cpf", $cpf);
  $stmt->execute();

  $result = $stmt->fetch(PDO::FETCH_ASSOC);

  
  if($result) {
    echo json_encode([
        'success' => true,
        'found' => true,
        'teacher' => $result
    ]);
  } else {
    echo json_encode([
        'success' => true,
        'found' => false
    ]);
  }

?>