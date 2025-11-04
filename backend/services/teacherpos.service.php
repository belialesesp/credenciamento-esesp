<?php
// backend/services/teacher_postg.service.php - FINAL VERSION

require_once __DIR__ . '/../classes/address.class.php';
require_once __DIR__ . '/../classes/postg_discipline.class.php';
require_once __DIR__ . '/../classes/education.class.php';
require_once __DIR__ . '/../classes/postg_teacher.class.php';

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
                AND ur.role = 'docente_pos'
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

            // TeacherPostG extends Teacher and has additional $post_graduation property
            // $disciplines returns [$regular_disciplines, $post_graduations]
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
                $disciplines[0], // regular disciplines
                $educations,
                $activities,
                [], // lectures (not used in postgrad)
                $result['enabled'],
                $disciplines[1] // post_graduations
            );

            return $teacher;
        }

        return null;
    }

    function getTeacherPostGDisciplines($user_id)
    {
        // ===================================================================
        // CRITICAL: Initialize return arrays FIRST, before any logic
        // ===================================================================
        $disciplines = [];
        $post_graduations = [];

        // Fetch activity information for each discipline approval
        // Fixed to properly JOIN through postg_eixo to get postgraduation name
        $sql = "
    SELECT
        d.id AS discipline_id,
        d.name AS discipline_name,
        pg.name AS post_graduation,
        pe.name AS eixo_name,
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
        postg_teacher_disciplines AS dt
    INNER JOIN
        postg_disciplinas AS d ON dt.discipline_id = d.id
    LEFT JOIN
        postg_eixo AS pe ON d.eixo_id = pe.id
    LEFT JOIN
        postgraduation AS pg ON pe.postg_id = pg.id
    LEFT JOIN
        activities AS a ON a.id = dt.activity_id
    WHERE 
        dt.user_id = :user_id
    ORDER BY d.name, a.name
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        $disciplineMap = []; // To group by discipline ID

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $discipline_id = $row["discipline_id"];

            // Initialize discipline if not exists
            if (!isset($disciplineMap[$discipline_id])) {
                // Create discipline object
                $discipline = new DisciplinePostg(
                    $row["discipline_id"],
                    $row["discipline_name"],
                    $row["post_graduation"] ?? '', // From postgraduation table via JOIN
                    $row["eixo_name"] ?? '',       // From postg_eixo table via JOIN
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
                $discipline->activities = [];

                $disciplineMap[$discipline_id] = $discipline;
            }

            // Add activity to this discipline if it exists
            if (!empty($row["activity_id"]) && !empty($row["activity_name"])) {
                $disciplineMap[$discipline_id]->activities[] = [
                    'id' => $row["activity_id"],
                    'name' => $row["activity_name"],
                    'status' => $row["discipline_status"],
                    'gese_evaluation' => $row["gese_evaluation"],
                    'pedagogico_evaluation' => $row["pedagogico_evaluation"],
                    'called_at' => $row["discipline_called_at"]
                ];
            }
        }

        // Convert map to arrays - separate disciplines and post-graduations
        // Note: $disciplines and $post_graduations already initialized above
        foreach ($disciplineMap as $discipline) {
            if (!empty($discipline->post_graduation)) {
                $post_graduations[] = $discipline;
            } else {
                $disciplines[] = $discipline;
            }
        }

        // ===================================================================
        // CRITICAL: Always return array with exactly 2 elements
        // Even if both are empty arrays, we return [$disciplines, $post_graduations]
        // This prevents "Undefined array key 0/1" errors
        // ===================================================================
        return [$disciplines, $post_graduations];
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
            INNER JOIN postg_teacher_activities ta ON ta.activity_id = a.id
            WHERE ta.teacher_id = :user_id
            ORDER BY a.name
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
