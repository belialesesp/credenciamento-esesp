<?php

require_once("../classes/database.class.php");

function validate_data() {

  $cpf = $_POST['cpf'];

  $conection = new Database();
  $conn = $conection->connect();

  $sql = "
    SELECT 
      *
    FROM 
      interpreter
    WHERE
      cpf = :cpf
  ";

  $query_run = $conn->prepare($sql);

  $stmt = $conn->prepare($sql);
  $stmt->bindParam(":cpf", $cpf);
  $stmt->execute();

  $result = $stmt->fetch(PDO::FETCH_ASSOC);

  if($result) {
    echo json_encode(['exists' => true]);
  } else {
    echo json_encode(['exists' => false]);
  }

}

validate_data();