<?php

function get_docente($conn)
{
  $query = "SELECT u.id, u.name, u.email, u.created_at, 
                     GROUP_CONCAT(
                         CONCAT_WS('|~|', 
                             d.id, 
                             d.name,
                             a.name,
                             td.enabled,
                             DATE_FORMAT(td.called_at, '%d/%m/%Y')
                         ) SEPARATOR '|~~|'
                     ) AS discipline_statuses
              FROM user u
              INNER JOIN user_roles ur ON u.id = ur.user_id AND ur.role = 'docente'
              LEFT JOIN teacher_disciplines td ON u.id = td.user_id
              LEFT JOIN disciplinas d ON td.discipline_id = d.id
              LEFT JOIN activities a ON td.activity_id = a.id
              GROUP BY u.id";

  $stmt = $conn->prepare($query);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_postg_docente($conn)
{
  $query = "SELECT u.id, u.name, u.email, u.created_at,
                     GROUP_CONCAT(
                         CONCAT_WS('|~|',
                             d.id,
                             d.name,
                             a.name,
                             td.enabled,
                             DATE_FORMAT(td.called_at, '%d/%m/%Y')
                          ) SEPARATOR '|~~|'
                     ) AS discipline_statuses
              FROM user u
              INNER JOIN user_roles ur ON u.id = ur.user_id
              LEFT JOIN postg_teacher_disciplines td ON u.id = td.user_id
              LEFT JOIN postg_disciplinas d ON td.discipline_id = d.id
              LEFT JOIN activities a ON td.activity_id = a.id
              WHERE ur.role = 'docente_pos'
              GROUP BY u.id, u.name, u.email, u.created_at
              ORDER BY u.name";

  $stmt = $conn->prepare($query);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function get_technicians($conn)
{
  $stmt = $conn->prepare("
        SELECT u.* 
        FROM user u 
        JOIN user_roles ur ON u.id = ur.user_id 
        WHERE ur.role = 'tecnico'
        ORDER BY u.name
    ");
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_interpreters($conn)
{
  $stmt = $conn->prepare("
        SELECT u.* 
        FROM user u 
        JOIN user_roles ur ON u.id = ur.user_id 
        WHERE ur.role = 'interprete'
        ORDER BY u.name
    ");
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function get_docentes_call($conn, $date)
{
  $query = "
  SELECT
    u.id,
    u.name,
    td.enabled,
    td.called_at,
    d.name AS course,
    d.id AS course_id,
    td.created_at,
    a.name AS category
  FROM 
    user u
  INNER JOIN
    user_roles ur ON u.id = ur.user_id AND ur.role = 'docente'
  LEFT JOIN
    teacher_disciplines td ON u.id = td.user_id
  LEFT JOIN
    disciplinas d ON td.discipline_id = d.id
  LEFT JOIN
    teacher_activities ta ON u.id = ta.user_id
  LEFT JOIN
    activities a ON a.id = ta.activity_id
  WHERE
    d.name IS NOT NULL
    AND td.created_at < :date
  ORDER BY d.name ASC, a.name ASC, td.created_at ASC
  ";
  $stmt = $conn->prepare($query);
  $data = [':date' => $date];
  $stmt->execute($data);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_postdocentes_call($conn, $date)
{
  $query = "
  SELECT
    u.id,
    u.name,
    td.enabled,
    td.called_at,
    d.name AS course,
    d.id AS course_id,
    td.created_at,
    a.name AS category
  FROM 
    user u
  INNER JOIN
    user_roles ur ON u.id = ur.user_id AND ur.role = 'docente_pos'
  LEFT JOIN
    postg_teacher_disciplines td ON u.id = td.user_id
  LEFT JOIN
    postg_disciplinas d ON td.discipline_id = d.id  // This one is correct
  LEFT JOIN
    postg_teacher_activities ta ON u.id = ta.user_id
  LEFT JOIN
    activities a ON a.id = ta.activity_id
  WHERE
    d.name IS NOT NULL
    AND td.created_at < :date
  ORDER BY d.name ASC, a.name ASC, td.created_at ASC
  ";
  $stmt = $conn->prepare($query);
  $data = [':date' => $date];
  $stmt->execute($data);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
