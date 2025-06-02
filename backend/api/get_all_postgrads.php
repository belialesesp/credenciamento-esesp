<?php

require_once("../classes/database.class.php");
$conection = new Database();
$conn = $conection->connect();

if(isset($_GET)) {

  $query_postGrad = "SELECT id, name FROM postgraduation ORDER BY name";
  $stmt_postGrad = $conn->prepare($query_postGrad);
  $stmt_postGrad->execute();

  $postGrads = $stmt_postGrad->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($postGrads);

}