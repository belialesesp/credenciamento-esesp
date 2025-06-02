<?php

require_once __DIR__ . '/../classes/address.class.php';
require_once __DIR__ . '/../classes/discipline.class.php';
require_once __DIR__ . '/../classes/education.class.php';
require_once __DIR__ . '/../classes/teacher.class.php';
require_once __DIR__ . '/../classes/lecture.class.php';

class TeacherService {
  private $db;

  public function __construct($db) {
    $this->db = $db;
  }

  function getTeacher($teacher_id) {
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
        teacher AS t
      LEFT JOIN
        address AS a
        ON a.id = t.address_id
      LEFT JOIN
      	documents AS d
       	ON d.teacher_id = t.id
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

      $disciplines = $this->getTeacherDisciplines($teacher_id);
      $educations = $this->getTeacherEducation($teacher_id);
      $activities = $this->getTeacherActivities($teacher_id);
      $lectures = $this->getTeacherLectures($teacher_id);

      $teacher = new Teacher(
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
        $result['enabled']
      );

      return $teacher;
    }   
  }

  function getTeacherDisciplines($teacher_id) {
    $sql = "
      SELECT
        d.id AS discipline_id,
        d.name AS discipline_name,
        es.name AS estacao_name,
        ex.name AS eixo_name,
        GROUP_CONCAT(DISTINCT m.id ORDER BY m.id SEPARATOR ',') AS module_ids,
        GROUP_CONCAT(DISTINCT m.name ORDER BY m.id SEPARATOR '|') AS module_names
      FROM 
        disciplinas AS d
      LEFT JOIN 
        estacao AS es 
        ON es.id = d.estacao_id
      LEFT JOIN 
        eixo AS ex 
        ON ex.id = es.eixo_id
      LEFT JOIN (
        module AS m
        INNER JOIN teacher_module AS tm ON tm.module_id = m.id AND tm.teacher_id = :teacher_id
      ) ON m.discipline_id = d.id
      WHERE 
        EXISTS (SELECT 1 FROM teacher_disciplines AS dt WHERE dt.discipline_id = d.id AND dt.teacher_id = :teacher_id)
        OR EXISTS (SELECT 1 FROM teacher_module AS tm2 
          INNER JOIN module AS m2 ON tm2.module_id = m2.id 
          WHERE m2.discipline_id = d.id AND tm2.teacher_id = :teacher_id)
      GROUP BY
          d.id, d.name, es.name, ex.name ";

    
    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(":teacher_id", $teacher_id, PDO::PARAM_INT);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $disciplines = [];

    foreach ($results as $result) {

      $modules = [];
      if (!empty($result['module_names'])) {
        if (strpos($result['module_names'], '|') !== false) {
          $modules = explode('|', $result['module_names']);
        } else {
          $modules = [$result['module_names']];
        }
      }

      $disciplines[] = new Discipline(
        $result["discipline_id"],
        $result["discipline_name"],
        $result["eixo_name"],
        $result["estacao_name"],
        $modules
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
        l.teacher_id = :teacher_id
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
      WHERE e.teacher_id = :teacher_id;
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
      teacher_activities ta ON ta.activity_id = a.id
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
    UPDATE teacher 
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