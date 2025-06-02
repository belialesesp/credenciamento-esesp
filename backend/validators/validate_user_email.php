<?php

require_once('../classes/database.class.php');

function validate_email() {
  $email = $_POST['email'];

  $connection = new Database();
  $conn = $connection->connect();

  $sql = "SELECT * FROM user WHERE email = :email";
  $query_run = $conn->prepare($sql);

  $stmt = $conn->prepare($sql);
  $stmt->bindParam(':email', $email);
  $stmt->execute();

  $result = $stmt->fetch(PDO::FETCH_ASSOC);

  if($result) {
    echo json_encode(['exists' => true]);
  } else {
    echo json_encode(['exists' => false]);
  }

}

validate_email();