<?php

require_once("../classes/database.class.php");
$conection = new Database();
$conn = $conection->connect(); 

if(isset($_GET['estacao_id'])) {
  $estacao_id = intval($_GET['estacao_id']);

  $query = "SELECT id, name FROM disciplinas WHERE estacao_id = :estacao_id ORDER BY name";
  $stmt = $conn->prepare($query);
  $stmt->bindParam(":estacao_id", $estacao_id, PDO::PARAM_INT);
  $stmt->execute();

  $disciplines = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($disciplines);

}