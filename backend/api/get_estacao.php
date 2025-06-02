<?php

require_once("../classes/database.class.php");
$conection = new Database();
$conn = $conection->connect(); 

if(isset($_GET['eixo_id'])) {
  $eixo_id = intval($_GET['eixo_id']);

  $query = "SELECT id, name FROM estacao WHERE eixo_id = :eixo_id ORDER BY name";
  $stmt = $conn->prepare($query);
  $stmt->bindParam(":eixo_id", $eixo_id, PDO::PARAM_INT);
  $stmt->execute();

  $estacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($estacoes);

}