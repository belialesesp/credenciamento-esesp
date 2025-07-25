<?php

// Selecionar todos os registros de credenciados
function get_docente($conn) {
  $query = "
    SELECT DISTINCT
      t.id,
      t.name,
      t.email,
      t.phone,
      t.created_at,
      t.document_number,
      t.document_emissor,
      t.document_uf,
      t.special_needs,
      t.address_id,
      CASE 
        WHEN d.id IS NULL THEN NULL
        ELSE GROUP_CONCAT(
          CONCAT(
            d.id, '|~|', 
            d.name, '|~|', 
            COALESCE(td.enabled, 'null'), '|~|',
            COALESCE(DATE_FORMAT(td.called_at, '%d/%m/%Y'), '')
          ) SEPARATOR '|~~|'
        )
      END as discipline_statuses
    FROM teacher t
    LEFT JOIN teacher_disciplines td ON t.id = td.teacher_id
    LEFT JOIN disciplinas d ON td.discipline_id = d.id
    GROUP BY t.id
    ORDER BY t.created_at ASC
  ";
  $stmt = $conn->prepare($query);
  $stmt->execute();

  $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
  return $result;
}

function get_postg_docente($conn) {
  $query = "
    SELECT DISTINCT
      t.id,
      t.name,
      t.email,
      t.phone,
      t.created_at,
      t.document_number,
      t.document_emissor,
      t.document_uf,
      t.special_needs,
      t.address_id,
      CASE 
        WHEN d.id IS NULL THEN NULL
        ELSE GROUP_CONCAT(
          CONCAT(
            d.id, '|~|', 
            d.name, '|~|', 
            COALESCE(td.enabled, 'null'), '|~|',
            COALESCE(DATE_FORMAT(td.called_at, '%d/%m/%Y'), '')
          ) SEPARATOR '|~~|'
        )
      END as discipline_statuses
    FROM postg_teacher t
    LEFT JOIN postg_teacher_disciplines td ON t.id = td.teacher_id
    LEFT JOIN postg_disciplinas d ON td.discipline_id = d.id
    GROUP BY t.id
    ORDER BY t.created_at ASC
  ";
  $stmt = $conn->prepare($query);
  $stmt->execute();

  $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
  return $result;
}

// These functions remain the same as they use their own enabled field
function get_technicians($conn) {
    $sql = "SELECT id, name, email, created_at, enabled, called_at FROM technician ORDER BY name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_interpreters($conn) {
    $sql = "SELECT id, name, email, created_at, enabled, called_at FROM interpreter ORDER BY name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Updated call functions to check discipline-specific status
function get_docentes_call($conn, $date) {
  $query = "
  SELECT
    t.id,
    t.name,
    td.enabled,
    td.called_at,
    d.name AS course,
    d.id AS course_id,
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
    td.enabled,
    td.called_at,
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

?>