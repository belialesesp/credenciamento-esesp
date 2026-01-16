<?php

require_once("../classes/database.class.php");
$conection = new Database();
$conn = $conection->connect(); 

if(isset($_GET['postg_id'])) {
  $postg_id = intval($_GET['postg_id']);

  $query = "SELECT id, name FROM postg_eixo WHERE postg_id = :postg_id";
  $stmt = $conn->prepare($query);
  $stmt->bindParam(":postg_id", $postg_id, PDO::PARAM_INT);
  $stmt->execute();

  $eixos = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($eixos);

}
