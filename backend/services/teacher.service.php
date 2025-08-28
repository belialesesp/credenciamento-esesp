<?php
// backend/services/teacher.service.php

require_once __DIR__ . '/../classes/address.class.php';
require_once __DIR__ . '/../classes/discipline.class.php';
require_once __DIR__ . '/../classes/education.class.php';
require_once __DIR__ . '/../classes/teacher.class.php';
require_once __DIR__ . '/../classes/lecture.class.php';

class TeacherService
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    function getTeacher($user_id)
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

            $disciplines = $this->getTeacherDisciplines($user_id);
            $educations = $this->getTeacherEducation($user_id);
            $activities = $this->getTeacherActivities($user_id);
            $lectures = $this->getTeacherLectures($user_id);

            $teacher = new Teacher(
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

    function getTeacherDisciplines($user_id)
    {

        $sql = "
            SELECT
                d.id AS discipline_id,
                d.name AS discipline_name,
                es.name AS estacao_name,
                ex.name AS eixo_name,
                dt.enabled AS discipline_status,
                dt.called_at AS discipline_called_at,
                GROUP_CONCAT(DISTINCT m.id ORDER BY m.id SEPARATOR ',') AS module_ids,
                GROUP_CONCAT(DISTINCT m.name ORDER BY m.id SEPARATOR '|') AS module_names
            FROM 
                disciplinas AS d
            LEFT JOIN 
                estacao AS es ON es.id = d.estacao_id
            LEFT JOIN 
                eixo AS ex ON ex.id = es.eixo_id
            LEFT JOIN 
                teacher_disciplines AS dt ON dt.discipline_id = d.id AND dt.user_id = :user_id
            LEFT JOIN (
                module AS m
                INNER JOIN teacher_module AS tm ON tm.module_id = m.id AND tm.user_id = :user_id
            ) ON m.discipline_id = d.id
            WHERE 
                EXISTS (SELECT 1 FROM teacher_disciplines AS dt2 WHERE dt2.discipline_id = d.id AND dt2.user_id = :user_id)
                OR EXISTS (SELECT 1 FROM teacher_module AS tm2 
                   INNER JOIN module AS m2 ON tm2.module_id = m2.id 
                   WHERE m2.discipline_id = d.id AND tm2.user_id = :user_id)
            GROUP BY d.id, d.name, es.name, ex.name, dt.enabled, dt.called_at
            ORDER BY ex.name, es.name, d.name
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
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
                $modules,
                $result["discipline_status"]
            );
        }

        return $disciplines;
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
                teacher_activities ta ON ta.activity_id = a.id
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

        $query_execute = $query_run->execute($data);

        return $query_execute;
    }
}
