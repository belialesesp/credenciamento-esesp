<?php

require_once("../classes/database.class.php");
$conection = new Database();
$conn = $conection->connect();

if(isset($_GET)) {

  $query_eixo = "SELECT id, name FROM eixo ORDER BY name";
  $stmt_eixo = $conn->prepare($query_eixo);
  $stmt_eixo->execute();

  $eixos = $stmt_eixo->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($eixos);

}

