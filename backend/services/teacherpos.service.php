<?php
// backend/services/teacherpos.service.php

require_once __DIR__ . '/../classes/address.class.php';
require_once __DIR__ . '/../classes/postg_discipline.class.php';
require_once __DIR__ . '/../classes/education.class.php';
require_once __DIR__ . '/../classes/postg_teacher.class.php';
require_once __DIR__ . '/../classes/lecture.class.php';

class TeacherPostGService
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    function getTeacherPostG($user_id)
    {
        $sql = "
            SELECT
                u.id,
                u.name,
                u.email,
                u.special_needs,
                u.document_number,
                u.document_emissor,
                u.document_uf,
                u.phone,
                u.cpf,
                u.created_at,
                u.enabled,
                u.street,
                u.city,
                u.state,
                u.zip_code as zip,
                u.complement,
                u.number,
                u.neighborhood,
                d.path AS file_path
            FROM
        user AS u
    INNER JOIN
        user_roles AS ur ON ur.user_id = u.id
    LEFT JOIN
        documents AS d ON d.user_id = u.id
    WHERE 
        u.id = :user_id
        AND ur.role IN ('docente', 'docente_pos')
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $address = new Address(
                null,
                $result["street"] ?? '',
                $result["city"] ?? '',
                $result["state"] ?? '',
                $result["zip"] ?? '',
                $result["complement"] ?? '',
                $result["number"] ?? '',
                $result["neighborhood"] ?? ''
            );

            $disciplines = $this->getTeacherPostGDisciplines($user_id);
            $educations = $this->getTeacherEducation($user_id);
            $activities = $this->getTeacherActivities($user_id);
            $lectures = $this->getTeacherLectures($user_id);

            $teacher = new TeacherPostG(
                $user_id,
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

        return null;
    }

    function getTeacherPostGDisciplines($user_id)
{
    $sql = "
        SELECT
            d.id AS discipline_id,
            d.name AS discipline_name,
            ex.name AS eixo_name,
            pg.name AS postg_name,
            dt.enabled AS discipline_status,
            dt.called_at AS discipline_called_at,
            dt.gese_evaluation,
            dt.gese_evaluated_at,
            dt.gese_evaluated_by,
            dt.pedagogico_evaluation,
            dt.pedagogico_evaluated_at,
            dt.pedagogico_evaluated_by
        FROM 
            postg_disciplinas AS d
        LEFT JOIN 
            postg_eixo AS ex ON ex.id = d.eixo_id
        LEFT JOIN
            postgraduation AS pg ON ex.postg_id = pg.id
        LEFT JOIN 
            postg_teacher_disciplines AS dt ON dt.discipline_id = d.id AND dt.user_id = :user_id
        WHERE 
            EXISTS (SELECT 1 FROM postg_teacher_disciplines WHERE discipline_id = d.id AND user_id = :user_id)
        GROUP BY d.id, d.name, ex.name, pg.name, dt.enabled, dt.called_at, 
                 dt.gese_evaluation, dt.gese_evaluated_at, dt.gese_evaluated_by,
                 dt.pedagogico_evaluation, dt.pedagogico_evaluated_at, dt.pedagogico_evaluated_by
        ORDER BY ex.name, d.name
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $disciplines = [];
    $post_graduation = [];

    foreach ($results as $result) {
        $disciplines[] = new DisciplinePostg(
            $result["discipline_id"],
            $result["discipline_name"],
            $result["postg_name"],
            $result["eixo_name"],
            $result["discipline_status"],
            $result["discipline_called_at"],
            $result["gese_evaluation"] ?? null,
            $result["gese_evaluated_at"] ?? null,
            $result["gese_evaluated_by"] ?? null,
            $result["pedagogico_evaluation"] ?? null,
            $result["pedagogico_evaluated_at"] ?? null,
            $result["pedagogico_evaluated_by"] ?? null
        );

        if (!in_array($result['postg_name'], $post_graduation)) {
            $post_graduation[] = $result['postg_name'];
        }
    }

    return array($disciplines, $post_graduation);
}

    function getTeacherLectures($user_id)
    {
        $sql = "
            SELECT 
                l.id AS lecture_id,
                l.name AS lecture_name,
                l.details AS lecture_details
            FROM 
                lecture AS l
            WHERE 
                l.user_id = :user_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
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

    function getTeacherEducation($user_id)
    {
        $sql = "
            SELECT 
                e.id as id, 
                e.course_name as name, 
                e.degree as degree, 
                e.institution as institution 
            FROM 
                education_degree as e 
            WHERE e.user_id = :user_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
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

    function getTeacherActivities($user_id)
    {
        $sql = "
            SELECT 
                a.name as name
            FROM 
                activities as a
            LEFT JOIN
                postg_teacher_activities ta ON ta.activity_id = a.id
            WHERE ta.user_id = :user_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $activities = [];

        foreach ($results as $result) {
            $activities[] = $result;
        }

        return $activities;
    }

    function updateStatus($user_id, $status)
    {
        $sql = "
            UPDATE user 
            SET enabled = :status
            WHERE id = :id 
            AND EXISTS (SELECT 1 FROM user_roles WHERE user_id = :id AND role IN ('docente', 'docente_pos'))
        ";

        $query_run = $this->db->prepare($sql);
        $data = [
            ':status' => $status,
            ':id' => $user_id
        ];

        return $query_run->execute($data);
    }
}
