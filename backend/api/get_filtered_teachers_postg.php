<?php
// backend/api/get_filtered_teachers_postg.php - UPDATED VERSION
require_once '../classes/database.class.php';

header('Content-Type: application/json; charset=utf-8');

$name = $_GET['name'] ?? '';
$category = $_GET['category'] ?? '';
$course = $_GET['course'] ?? '';
$status = $_GET['status'] ?? '';

try {
    $conection = new Database();
    $conn = $conection->connect();

    // Handle 'sem disciplinas' (no-disciplines) filter separately
    if ($status === 'no-disciplines') {
        // Get postgraduate teachers (users with role 'docente_pos') with NO disciplines at all
        $sql = "SELECT u.id, u.name, u.email, u.phone, u.created_at, 
                u.document_number, u.document_emissor, u.document_uf, 
                u.special_needs, u.address_id 
                FROM user u
                INNER JOIN user_roles ur ON u.id = ur.user_id
                WHERE ur.role = 'docente_pos'
                AND NOT EXISTS (
                    SELECT 1 FROM postg_teacher_disciplines td 
                    WHERE td.user_id = u.id
                )";

        $params = [];

        // Apply category filter if present
        if ($category !== '') {
            $sql .= " AND EXISTS (
                SELECT 1 FROM postg_teacher_activities ta 
                WHERE ta.user_id = u.id AND ta.activity_id = :category
            )";
            $params[':category'] = $category;
        }

        // Apply name filter if present
        if ($name !== '') {
            $sql .= " AND u.name LIKE :name";
            $params[':name'] = '%' . $name . '%';
        }

        $sql .= " ORDER BY u.created_at ASC";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add empty discipline_statuses field for consistency
        foreach ($teachers as &$teacher) {
            $teacher['discipline_statuses'] = '';
        }

        echo json_encode($teachers, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Get postgraduate teachers (users with role 'docente_pos') matching filters
    $sql = "SELECT DISTINCT u.id, u.name, u.email, u.phone, u.created_at,
            u.document_number, u.document_emissor, u.document_uf, 
            u.special_needs, u.address_id 
            FROM user u
            INNER JOIN user_roles ur ON u.id = ur.user_id
            WHERE ur.role = 'docente_pos'";
    
    $joins = [];
    $where = [];
    $params = [];

    if ($category !== '') {
        $joins[] = "INNER JOIN postg_teacher_activities ta ON u.id = ta.user_id";
        $where[] = "ta.activity_id = :category";
        $params[':category'] = $category;
    }

    // Always use LEFT JOIN for disciplines to include teachers without disciplines
    $joins[] = "LEFT JOIN postg_teacher_disciplines td ON u.id = td.user_id";

    if ($course !== '') {
        $where[] = "td.discipline_id = :course";
        $params[':course'] = $course;
    }
    
    if ($name !== '') {
        $where[] = "u.name LIKE :name";
        $params[':name'] = '%' . $name . '%';
    }
    
    // Use BINARY for exact comparison to avoid type coercion issues
    if ($status === '1') {
        $where[] = "BINARY td.enabled = '1'";
    } else if ($status === '0') {
        $where[] = "BINARY td.enabled = '0'";
    } else if ($status === 'null') {
        // Use BINARY to ensure '0' doesn't match empty string
        $where[] = "(td.enabled IS NULL OR BINARY td.enabled = '')";
    }

    if (!empty($joins)) {
        $sql .= ' ' . implode(' ', $joins);
    }
    
    if (!empty($where)) {
        $sql .= ' AND ' . implode(' AND ', $where);
    }
    
    $sql .= ' ORDER BY u.created_at ASC';

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process each teacher to get their disciplines
    $result = [];
    foreach ($teachers as $teacher) {
        $discSql = "
            SELECT 
                d.id,
                d.name,
                td.enabled,
                td.called_at
            FROM postg_teacher_disciplines td
            INNER JOIN postg_disciplinas d ON td.discipline_id = d.id
            WHERE td.user_id = :user_id
        ";

        $discParams = [':user_id' => $teacher['id']];

        if ($course !== '') {
            $discSql .= " AND d.id = :course";
            $discParams[':course'] = $course;
        }

        // Apply status filter with BINARY for exact comparison
        if ($status === '1') {
            $discSql .= " AND BINARY td.enabled = '1'";
        } else if ($status === '0') {
            $discSql .= " AND BINARY td.enabled = '0'";
        } else if ($status === 'null') {
            $discSql .= " AND (td.enabled IS NULL OR BINARY td.enabled = '')";
        }

        $discSql .= " ORDER BY d.name";

        $discStmt = $conn->prepare($discSql);
        $discStmt->execute($discParams);
        $disciplines = $discStmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($disciplines)) {
            $disciplineStatuses = [];

            foreach ($disciplines as $disc) {
                // Clean name to avoid delimiter conflicts
                $cleanName = str_replace(['|~|', '|~~|'], ' ', $disc['name']);

                // Determine status value with strict type checking
                $enabledRaw = $disc['enabled'];

                if ($enabledRaw === null) {
                    $statusValue = 'null';
                } elseif ($enabledRaw === '') {
                    $statusValue = 'null';
                } elseif ($enabledRaw === '0' || $enabledRaw === 0) {
                    $statusValue = '0';
                } elseif ($enabledRaw === '1' || $enabledRaw === 1) {
                    $statusValue = '1';
                } else {
                    $statusValue = 'null';
                }

                // Format called_at date
                $calledAt = $disc['called_at'] ? date('d/m/Y', strtotime($disc['called_at'])) : '';

                $disciplineStatuses[] = $disc['id'] . '|~|' . $cleanName . '|~|' . $statusValue . '|~|' . $calledAt;
            }

            $teacher['discipline_statuses'] = implode('|~~|', $disciplineStatuses);
            $result[] = $teacher;
        } else if ($status === '') {
            // Include teachers without disciplines only when no status filter is applied
            $teacher['discipline_statuses'] = '';
            $result[] = $teacher;
        }
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Error in get_filtered_teachers_postg.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}