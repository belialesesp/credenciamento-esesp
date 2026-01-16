<?php


function get_all_courses($conn) {
  $query = "SELECT id, name FROM disciplinas ORDER BY name";
  $stmt = $conn->prepare($query);
  $stmt->execute();
  
  $disciplines = $stmt->fetchAll(PDO::FETCH_ASSOC);
  return $disciplines;
}

function get_all_postg_courses($conn) {
  $query = "SELECT id, name FROM postg_disciplinas ORDER BY name";
  $stmt = $conn->prepare($query);
  $stmt->execute();
  
  $disciplines = $stmt->fetchAll(PDO::FETCH_ASSOC);
  return $disciplines;
}


