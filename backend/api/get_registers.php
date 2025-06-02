<?php

// Selecionar todos os registros de credenciados
function get_docente($conn) {
  $query = "SELECT * FROM teacher ORDER BY called_at ASC, created_at ASC";
  $stmt = $conn->prepare($query);
  $stmt->execute();

  $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
  return $result;
}
function get_postg_docente($conn) {
  $query = "SELECT * FROM postg_teacher ORDER BY called_at ASC, created_at ASC";
  $stmt = $conn->prepare($query);
  $stmt->execute();

  $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
  return $result;
}

function get_technicians($conn) {
  $query = "SELECT * FROM technician ORDER BY created_at ASC";
  $stmt = $conn->prepare($query);
  $stmt->execute();

  $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
  return $result;
}
function get_interpreters($conn) {
  $query = "SELECT * FROM interpreter ORDER BY created_at ASC";
  $stmt = $conn->prepare($query);
  $stmt->execute();

  $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
  return $result;
}

// Selecionar os registros dos credenciados com curso para lista de chamada

function get_docentes_call($conn, $date) {
  $query = "
  SELECT
    t.id,
    t.name,
    t.enabled,
    d.name AS course,
    td.created_at,
    a.name AS category
  FROM 
    teacher AS t
  LEFT JOIN
    teacher_disciplines AS td
    ON td.teacher_id = t.id
  LEFT JOIN
    disciplinas AS d 
    ON td.discipline_id = d.id
  LEFT JOIN
    teacher_activities AS ta 
    ON ta.teacher_id = t.id
  LEFT JOIN
    activities AS a 
    ON a.id = ta.activity_id
  WHERE
    d.name is NOT NULL
  AND
    td.created_at < :date
  AND
    t.enabled = 1
  ORDER BY d.name ASC, a.name ASC, td.created_at ASC
  ";
  $stmt = $conn->prepare($query);
  $data = [
    ':date' => $date
  ];
  $stmt->execute($data);

  $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
  return $result;  
}
function get_postdocentes_call($conn, $date) {
  $query = "
  SELECT
    t.id,
    t.name,
    t.enabled,
    d.name AS course,
    td.created_at,
    a.name AS category
  FROM 
    postg_teacher AS t
  LEFT JOIN
    postg_teacher_disciplines AS td
      ON td.teacher_id = t.id
  LEFT JOIN
    postg_disciplinas AS d 
      ON td.discipline_id = d.id
   LEFT JOIN
   	postg_teacher_activities AS ta
    	ON ta.teacher_id = t.id
   LEFT JOIN
   	activities AS a
    	ON a.id = ta.activity_id
  WHERE
    d.name is NOT NULL
  AND
    td.created_at < :date
  AND
    t.enabled = 1
  ORDER BY d.name ASC, a.name ASC, td.created_at ASC
  ";
  $stmt = $conn->prepare($query);
  $data = [
    ':date' => $date
  ];
  $stmt->execute($data);

  $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
  return $result;  
}

function get_interpreter_call($conn, $date) {
  $query = "
  SELECT
    i.id,
    i.name,
    i.created_at,
    i.enabled
  FROM 
    interpreter AS i
  WHERE
    i.created_at < :date
  AND
    i.enabled = 1
  ORDER BY i.created_at ASC
  ";
  $stmt = $conn->prepare($query);
  $data = [
    ':date' => $date
  ];
  $stmt->execute($data);

  $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
  return $result;  
}
function get_technicians_call($conn, $date) {
  $query = "
  SELECT
    t.id,
    t.name,
    t.created_at,
    t.enabled
  FROM 
    technician AS t
  WHERE
    t.created_at < :date
  AND
    t.enabled = 1
  ORDER BY t.created_at ASC
  ";
  $stmt = $conn->prepare($query);
  $data = [
    ':date' => $date
  ];
  $stmt->execute($data);

  $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
  return $result;  
}

