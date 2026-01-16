
<?php

require_once("../classes/database.class.php");
$conection = new Database();
$conn = $conection->connect(); 

if(isset($_GET['disciplina_id'])) {
  $discipline_id = intval($_GET['disciplina_id']);

  $query = "SELECT id, name FROM module WHERE discipline_id = :discipline_id ORDER BY name";
  $stmt = $conn->prepare($query);
  $stmt->bindParam(":discipline_id", $discipline_id, PDO::PARAM_INT);
  $stmt->execute();

  $disciplines = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($disciplines);

}