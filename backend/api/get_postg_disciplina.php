<?php

require_once("../classes/database.class.php");
$conection = new Database();
$conn = $conection->connect(); 

if(isset($_GET['eixo_id'])) {
  $eixo_id = intval($_GET['eixo_id']);

  $query = "SELECT id, name FROM postg_disciplinas WHERE eixo_id = :eixo_id";
  $stmt = $conn->prepare($query);
  $stmt->bindParam(":eixo_id", $eixo_id, PDO::PARAM_INT);
  $stmt->execute();

  $disciplines = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($disciplines);

}