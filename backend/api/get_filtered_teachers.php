<?php
// backend/api/get_filtered_teachers.php - FIXED VERSION WITH ACTIVITIES
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
        // Get teachers (users with role 'docente') with NO disciplines at all
        $sql = "SELECT u.id, u.name, u.email, u.phone, u.created_at, 
                u.document_number, u.document_emissor, u.document_uf, 
                u.special_needs, u.address_id 
                FROM user u
                INNER JOIN user_roles ur ON u.id = ur.user_id
                WHERE ur.role = 'docente'
                AND NOT EXISTS (
                    SELECT 1 FROM teacher_disciplines td 
                    WHERE td.user_id = u.id
                )";

        $params = [];

        // Apply category filter if present
        if ($category !== '') {
            $sql .= " AND EXISTS (
                SELECT 1 FROM teacher_activities ta 
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

    // Get teachers (users with role 'docente') matching filters (for all other cases)
    $sql = "SELECT DISTINCT u.id, u.name, u.email, u.phone, u.created_at,
            u.document_number, u.document_emissor, u.document_uf, 
            u.special_needs, u.address_id 
            FROM user u
            INNER JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN teacher_disciplines td ON u.id = td.user_id
            LEFT JOIN teacher_activities ta ON u.id = ta.user_id
            WHERE ur.role = 'docente'";
    
    $params = [];

    // Apply filters
    if ($category !== '') {
        $sql .= " AND ta.activity_id = :category";
        $params[':category'] = $category;
    }

    if ($course !== '') {
        $sql .= " AND td.discipline_id = :course";
        $params[':course'] = $course;
    }
    
    if ($name !== '') {
        $sql .= " AND u.name LIKE :name";
        $params[':name'] = '%' . $name . '%';
    }
    
    // Use BINARY for exact comparison to avoid type coercion issues
    if ($status === '1') {
        $sql .= " AND BINARY td.enabled = '1'";
    } else if ($status === '0') {
        $sql .= " AND BINARY td.enabled = '0'";
    } else if ($status === 'null') {
        // Use BINARY to ensure '0' doesn't match empty string
        $sql .= " AND (td.enabled IS NULL OR BINARY td.enabled = '')";
    }

    $sql .= ' ORDER BY u.created_at ASC';

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process each teacher to get their disciplines WITH ACTIVITIES
    $result = [];
    foreach ($teachers as $teacher) {
        // FIXED: Query now joins with activities table to get activity names
        $discSql = "
            SELECT 
                d.id,
                d.name,
                td.enabled,
                td.called_at,
                GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') as activities
            FROM teacher_disciplines td
            INNER JOIN disciplinas d ON td.discipline_id = d.id
            LEFT JOIN teacher_activities ta ON ta.user_id = td.user_id
            LEFT JOIN activities a ON a.id = ta.activity_id
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

        // Group by discipline to aggregate activities
        $discSql .= " GROUP BY d.id, d.name, td.enabled, td.called_at ORDER BY d.name";

        $discStmt = $conn->prepare($discSql);
        $discStmt->execute($discParams);
        $disciplines = $discStmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($disciplines)) {
            $disciplineStatuses = [];

            foreach ($disciplines as $disc) {
                // Clean name to avoid delimiter conflicts
                $cleanName = str_replace(['|~|', '|~~|'], ' ', $disc['name']);
                
                // Get activities or set default
                $activityName = !empty($disc['activities']) ? $disc['activities'] : 'Redação Oficial';

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

                // FIXED: Now including 5 parts with activity name at index 2
                $disciplineStatuses[] = $disc['id'] . '|~|' . $cleanName . '|~|' . $activityName . '|~|' . $statusValue . '|~|' . $calledAt;
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
} catch (PDOException $e) {
    // More specific database error handling
    error_log("Database Error in get_filtered_teachers.php: " . $e->getMessage());
    error_log("SQL Error Code: " . $e->getCode());
    error_log("SQL Query: " . $sql);
    error_log("SQL Parameters: " . print_r($params, true));
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error occurred',
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'sql' => $sql
    ]);
} catch (Exception $e) {
    error_log("General Error in get_filtered_teachers.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'error' => 'An unexpected error occurred',
        'message' => $e->getMessage()
    ]);
}