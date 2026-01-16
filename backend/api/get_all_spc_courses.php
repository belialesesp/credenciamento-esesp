<?php

require_once("../classes/database.class.php");
$conection = new Database();
$conn = $conection->connect(); 

if(isset($_GET['institution_id'])) {
  $institution_id = intval($_GET['institution_id']);

  $query = "SELECT id, name FROM spcdemand_courses WHERE institution_id = :institution_id ORDER BY name";
  $stmt = $conn->prepare($query);
  $stmt->bindParam(":institution_id", $institution_id, PDO::PARAM_INT);
  $stmt->execute();

  $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($courses);

}
