<?php

require_once __DIR__ . '/../classes/address.class.php';
require_once __DIR__ . '/../classes/spc_course.class.php';
require_once __DIR__ . '/../classes/education.class.php';
require_once __DIR__ . '/../classes/teacher.class.php';

class SpcTeacherService {
  private $db;

  public function __construct($db) {
    $this->db = $db;
  }

  function getSpcDemandTeacher($teacher_id) {
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
        spcdemand_teacher AS t
      LEFT JOIN
        address AS a
        ON a.id = t.address_id
      LEFT JOIN
      	documents AS d
       	ON d.spc_teacher_id = t.id
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
    }

    $courses = $this->getSpcCourses($teacher_id);
    $educations = $this->getSpcTeacherEducation($teacher_id);
    $activities = $this->getSpcTeacherActivities($teacher_id);

    $teacher = new Teacher(
      $teacher_id,
      $result['name'],
      $result['email'],
      $result['special_needs'],
      $result['document_number'],
      $result['document_emissor'],
      $result['document_uf'],
      $result['phone'],
      $result['cpf'],
      $result['created_at'],
      $address,
      $result['file_path'],
      $courses,
      $educations,
      $activities,
      $result['enabled']
    );

    return $teacher;


  }

  public function getSpcCourses($teacher_id) {
    $sql = "
      SELECT 
        c.id AS course_id,
        c.name AS course_name,
        i.name AS institution_name
      FROM
        spcdemand_courses AS c
      LEFT JOIN
        spcdemand_institution AS i
        ON c.institution_id = i.id
      INNER JOIN
        spcdemand_teacher_courses AS tc
        ON tc.course_id = c.id
      WHERE
        tc.teacher_id = :teacher_id
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(":teacher_id", $teacher_id, PDO::PARAM_INT);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $courses = [];
    
    foreach ($results as $result) {
      $courses[] = new SpcCourse(
        $result['course_id'],
        $result['course_name'],
        $result['institution_name']
      );
    }

    return $courses;
  }

  public function getSpcTeacherEducation($teacher_id) {
    $sql = "
      SELECT 
        e.id as id, 
        e.course_name as name, 
        e.degree as degree, 
        e.institution as institution 
      FROM 
        education_degree as e 
      WHERE e.spc_teacher_id = :teacher_id;
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

  public function getSpcTeacherActivities($teacher_id) {
    $sql = "
      SELECT 
        a.name as name
      FROM 
        activities as a
      LEFT JOIN
        spcdemand_teacher_activities ta ON ta.activity_id = a.id
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

  public function updateStatus($teacher_id, $status) {
    $sql = "
    UPDATE spcdemand_teacher 
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