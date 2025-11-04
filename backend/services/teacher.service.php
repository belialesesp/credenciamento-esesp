<?php
// backend/services/teacher.service.php - FINAL VERSION

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
    // Fetch activity information for each discipline approval
    $sql = "
    SELECT
        d.id AS discipline_id,
        d.name AS discipline_name,
        eixo.name AS eixo_name,
        est.name AS estacao_name,
        dt.activity_id,
        a.name AS activity_name,
        dt.enabled AS discipline_status,
        dt.called_at AS discipline_called_at,
        dt.gese_evaluation,
        dt.gese_evaluated_at,
        dt.gese_evaluated_by,
        dt.pedagogico_evaluation,
        dt.pedagogico_evaluated_at,
        dt.pedagogico_evaluated_by
    FROM 
        teacher_disciplines AS dt
    INNER JOIN
        disciplinas AS d ON dt.discipline_id = d.id
    LEFT JOIN 
        estacao AS est ON est.id = d.estacao_id
    LEFT JOIN 
        eixo ON eixo.id = est.eixo_id
    LEFT JOIN
        activities AS a ON a.id = dt.activity_id
    WHERE 
        dt.user_id = :user_id
    ORDER BY d.name, a.name
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    $disciplines = [];
    $disciplineMap = []; // To group by discipline ID

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $discipline_id = $row["discipline_id"];
        
        // Initialize discipline if not exists
        if (!isset($disciplineMap[$discipline_id])) {
            // Get modules for this discipline
            $moduleSql = "
                SELECT DISTINCT m.name
                FROM teacher_module tm
                INNER JOIN module m ON tm.module_id = m.id
                WHERE tm.user_id = :user_id
                AND m.discipline_id = :discipline_id
                ORDER BY m.name
            ";
            
            $moduleStmt = $this->db->prepare($moduleSql);
            $moduleStmt->bindParam(':user_id', $user_id);
            $moduleStmt->bindParam(':discipline_id', $discipline_id);
            $moduleStmt->execute();
            
            $modules = [];
            while ($moduleRow = $moduleStmt->fetch(PDO::FETCH_ASSOC)) {
                $modules[] = $moduleRow['name'];
            }

            // Create discipline object
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
            
            // CRITICAL: Initialize activities array explicitly
            // This ensures the property exists even if it's not in the constructor
            $discipline->activities = [];
            
            $disciplineMap[$discipline_id] = $discipline;
        }
        
        // Add activity to this discipline's activities array
        // CRITICAL FIX: Check if activity_id is not null before adding
        if (!empty($row["activity_id"]) && !empty($row["activity_name"])) {
            $newActivity = [
                'id' => $row["activity_id"],
                'name' => $row["activity_name"],
                'status' => $row["discipline_status"],
                'gese_evaluation' => $row["gese_evaluation"],
                'pedagogico_evaluation' => $row["pedagogico_evaluation"],
                'called_at' => $row["discipline_called_at"]
            ];
            
            // Avoid duplicates (check if this activity ID already exists)
            $exists = false;
            foreach ($disciplineMap[$discipline_id]->activities as $existingActivity) {
                if ($existingActivity['id'] === $newActivity['id']) {
                    $exists = true;
                    break;
                }
            }
            
            if (!$exists) {
                $disciplineMap[$discipline_id]->activities[] = $newActivity;
            }
        }
    }

    // Convert map to array
    foreach ($disciplineMap as $discipline) {
        $disciplines[] = $discipline;
    }

    return $disciplines;
}

    function getTeacherEducation($user_id)
    {
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
            $education = new Education(
                $row["id"],
                $row["course_name"],
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
            SELECT a.id, a.name 
            FROM activities a
            INNER JOIN teacher_activities ta ON ta.activity_id = a.id
            WHERE ta.user_id = :user_id
            ORDER BY a.name
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getTeacherLectures($user_id)
    {
        $sql = "SELECT name, details FROM lecture WHERE teacher_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $lectures = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lecture = new Lecture($row["name"], $row["details"]);
            $lectures[] = $lecture;
        }

        return $lectures;
    }
}