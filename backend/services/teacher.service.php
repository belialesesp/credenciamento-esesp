<?php
// backend/services/teacher.service.php - CORRECTED WITH PROPER TABLE NAMES

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
            eixo.name AS eixo_name,
            est.name AS estacao_name,
            dt.enabled AS discipline_status,
            dt.called_at AS discipline_called_at,
            dt.gese_evaluation,
            dt.gese_evaluated_at,
            dt.gese_evaluated_by,
            dt.pedagogico_evaluation,
            dt.pedagogico_evaluated_at,
            dt.pedagogico_evaluated_by
        FROM 
            disciplinas AS d
        LEFT JOIN 
            estacao AS est ON est.id = d.estacao_id
        LEFT JOIN 
            eixo ON eixo.id = est.eixo_id
        LEFT JOIN 
            teacher_disciplines AS dt ON dt.discipline_id = d.id AND dt.user_id = :user_id
        WHERE 
            EXISTS (SELECT 1 FROM teacher_disciplines WHERE discipline_id = d.id AND user_id = :user_id)
        GROUP BY d.id, d.name, eixo.name, est.name, 
                 dt.enabled, dt.called_at, 
                 dt.gese_evaluation, dt.gese_evaluated_at, dt.gese_evaluated_by,
                 dt.pedagogico_evaluation, dt.pedagogico_evaluated_at, dt.pedagogico_evaluated_by
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        $disciplines = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Get modules for this discipline
            $module_sql = "
            SELECT 
                m.name AS module_name 
            FROM 
                module AS m
            INNER JOIN 
                teacher_module AS tm ON tm.module_id = m.id
            WHERE 
                tm.user_id = :user_id 
                AND m.discipline_id = :discipline_id
        ";

            $module_stmt = $this->db->prepare($module_sql);
            $module_stmt->bindParam(':user_id', $user_id);
            $module_stmt->bindParam(':discipline_id', $row["discipline_id"]);
            $module_stmt->execute();

            $modules = [];
            while ($module_row = $module_stmt->fetch(PDO::FETCH_ASSOC)) {
                $modules[] = $module_row["module_name"];
            }

            // Create discipline object with ALL evaluation fields
            $discipline = new Discipline(
                $row["discipline_id"],
                $row["discipline_name"],
                $row["eixo_name"] ?? '',
                $row["estacao_name"] ?? '',
                $modules,
                $row["discipline_status"],
                $row["discipline_called_at"] ?? null,
                $row["gese_evaluation"] ?? null,
                $row["gese_evaluated_at"] ?? null,
                $row["gese_evaluated_by"] ?? null,
                $row["pedagogico_evaluation"] ?? null,
                $row["pedagogico_evaluated_at"] ?? null,
                $row["pedagogico_evaluated_by"] ?? null
            );

            $disciplines[] = $discipline;
        }

        return $disciplines;
    }

    function getTeacherEducation($user_id)
    {
        // CORRECTED: Using education_degree table instead of education
        $sql = "
            SELECT
                ed.id,
                ed.course_name,
                ed.degree,
                ed.institution,
                ed.graduation_year,
                ed.created_at,
                ed.updated_at
            FROM
                education_degree AS ed
            WHERE
                ed.user_id = :user_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        $educations = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Using the Education class constructor
            // Note: The Education class expects (id, name, degree, institution)
            $education = new Education(
                $row["id"],
                $row["course_name"],  // course_name is the "name"
                $row["degree"],
                $row["institution"]
            );
            $educations[] = $education;
        }

        return $educations;
    }

    function getTeacherActivities($user_id)
    {
        $sql = "
            SELECT
                a.id,
                a.name
            FROM
                activities AS a
            INNER JOIN
                teacher_activities AS ta ON ta.activity_id = a.id
            WHERE
                ta.user_id = :user_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        $activities = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = [
                'id' => $row["id"],
                'name' => $row["name"]
            ];
        }

        return $activities;
    }

    function getTeacherLectures($user_id)
    {
        $sql = "
            SELECT
                l.id,
                l.name,
                l.details
            FROM
                lecture AS l
            WHERE
                l.user_id = :user_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        $lectures = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lecture = new Lecture(
                $row["id"],
                $row["name"],
                $row["details"]
            );
            $lectures[] = $lecture;
        }

        return $lectures;
    }
}
