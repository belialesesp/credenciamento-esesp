<?php

require_once __DIR__ . '/../classes/address.class.php';
require_once __DIR__ . '/../classes/postg_discipline.class.php';
require_once __DIR__ . '/../classes/education.class.php';
require_once __DIR__ . '/../classes/postg_teacher.class.php';

class TeacherPostGService {
  private $db;

  public function __construct($db) {
    $this->db = $db;
  }

  function getTeacherPostG($teacher_id) {
    $sql = "
      SELECT
        t.name,
        t.email,
        t.special_needs,
        t.document_number,
        t.document_emissor,
        t.document_uf,
        t.phone,
        t.cpf,
        t.address_id,
        t.created_at,
        t.enabled,
        a.street,
        a.city,
        a.state,
        a.zip_code,
        a.complement,
        a.address_number,
        a.neighborhood,
        d.path AS file_path
      FROM
        postg_teacher AS t
      LEFT JOIN
        address AS a
        ON a.id = t.address_id
      LEFT JOIN
      	documents AS d
       	ON d.teacher_postg_id = t.id
      WHERE 
        t.id = :teacher_id
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(":teacher_id", $teacher_id, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if($result) {
      $address = new Address(
        $result["address_id"],
        $result["street"],
        $result["city"],
        $result["state"],
        $result["zip_code"],
        $result["complement"],
        $result["address_number"],
        $result["neighborhood"],
      );

      list($disciplines, $post_graduation) = $this->getTeacherPostGDisciplines($teacher_id);
      $educations = $this->getTeacherEducation($teacher_id);
      $activities = $this->getTeacherActivities($teacher_id);
      $lectures = $this->getTeacherLectures($teacher_id);

      $teacher = new TeacherPostG(
        $teacher_id,
        $result["name"],
        $result["email"],
        $result["special_needs"],
        $result["document_number"],
        $result["document_emissor"],
        $result["document_uf"],
        $result["phone"],
        $result["cpf"],
        $result['created_at'],
        $address,
        $result['file_path'],
        $disciplines,
        $educations,
        $activities,
        $lectures,
        $result['enabled'],
        $post_graduation
      );

      return $teacher;
    }   
  }


// Replace the entire getTeacherDisciplines method in backend/services/teacherpos.service.php

// In backend/services/teacherpos.service.php
// Update the getTeacherPostGDisciplines method to include called_at:

function getTeacherPostGDisciplines($teacher_id) {
  $sql = "
    SELECT
      d.id AS discipline_id,
      d.name AS discipline_name,
      ex.name AS eixo_name,
      dt.enabled AS discipline_status,
      dt.called_at AS discipline_called_at
    FROM 
      postg_disciplinas AS d
    LEFT JOIN 
      postg_eixo AS ex 
      ON ex.id = d.eixo_id
    LEFT JOIN 
      postg_teacher_disciplines AS dt 
      ON dt.discipline_id = d.id AND dt.teacher_id = :teacher_id
    WHERE 
      EXISTS (SELECT 1 FROM postg_teacher_disciplines AS dt2 WHERE dt2.discipline_id = d.id AND dt2.teacher_id = :teacher_id)
    GROUP BY d.id, d.name, ex.name, dt.enabled, dt.called_at
    ORDER BY ex.name, d.name
  ";

  $stmt = $this->db->prepare($sql);
  $stmt->bindParam(":teacher_id", $teacher_id, PDO::PARAM_INT);
  $stmt->execute();

  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $disciplines = [];

  foreach ($results as $result) {
    $disciplines[] = new Discipline(
      $result["discipline_id"],
      $result["discipline_name"],
      null, // estacao_name not used in postg
      $result["eixo_name"],
      $result["discipline_status"],
      $result["discipline_called_at"] // Add this field
    );
  }

  return $disciplines;
}

  function getTeacherLectures($teacher_id) {
    $sql = "
      SELECT 
        l.id AS lecture_id,
        l.name AS lecture_name,
        l.details AS lecture_details
      FROM 
        lecture AS l
      WHERE 
        l.postg_teacher_id = :teacher_id
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(":teacher_id", $teacher_id, PDO::PARAM_INT);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $lectures = [];

    foreach ($results as $result) {
      $lectures[] = new Lecture(
        $result["lecture_name"],
        $result["lecture_details"]
      );
    }

    return $lectures;
  }

  function getTeacherEducation($teacher_id) {
    $sql = "
      SELECT 
        e.id as id, 
        e.course_name as name, 
        e.degree as degree, 
        e.institution as institution 
      FROM 
        education_degree as e 
      WHERE e.teacher_postg_id = :teacher_id;
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(":teacher_id", $teacher_id, PDO::PARAM_INT);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $educations = [];

    foreach ($results as $result) {
      $educations[] = new Education(
        $result["id"],
        $result["name"],
        $result["degree"],
        $result["institution"]
      );
    }

    return $educations;

  }

  function getTeacherActivities($teacher_id) {
    $sql = "
    SELECT 
      a.name as name
    FROM 
      activities as a
    LEFT JOIN
      postg_teacher_activities ta ON ta.activity_id = a.id
    WHERE ta.teacher_id = :teacher_id;
  ";

  $stmt = $this->db->prepare($sql);
  $stmt->bindParam(":teacher_id", $teacher_id, PDO::PARAM_INT);
  $stmt->execute();

  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $activities = [];

  foreach ($results as $result) {
    $activities[] = $result;
  }

  return $activities;
  }

  function updateStatus($teacher_id, $status) {
    $sql = "
    UPDATE postg_teacher 
      SET enabled = :status
    WHERE id = :id
    ";

    $query_run = $this->db->prepare($sql);

    $data = [
    ':status' => $status,
    ':id' => $teacher_id
    ];

    $query_execute = $query_run->execute($data);
  
  }
}