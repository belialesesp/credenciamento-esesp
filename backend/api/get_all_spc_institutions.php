<?php

require_once("../classes/database.class.php");
$conection = new Database();
$conn = $conection->connect();

if(isset($_GET)) {

  $query_institutions = "SELECT id, name FROM spcdemand_institution ORDER BY name";
  $stmt_institutions = $conn->prepare($query_institutions);
  $stmt_institutions->execute();

  $institutions = $stmt_institutions->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($institutions);

}