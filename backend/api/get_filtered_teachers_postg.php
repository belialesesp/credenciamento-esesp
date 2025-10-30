<?php
// backend/api/get_filtered_postg_teachers.php - FIXED VERSION
// This file should replace the filtering logic in docentes-pos.php

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../classes/database.class.php';

try {
    $conection = new Database();
    $conn = $conection->connect();
    
    // Get parameters
    $category = $_GET['category'] ?? '';
    $course = $_GET['course'] ?? '';
    $status = $_GET['status'] ?? '';
    $name = $_GET['name'] ?? '';
    
    // Build main query - get all matching teachers
    $sql = "SELECT DISTINCT t.id, t.name, t.email, t.created_at, t.called_at 
            FROM postg_teacher t";
    
    $joins = [];
    $conditions = [];
    $params = [];
    
    // Handle name filter
    if ($name !== '') {
        $conditions[] = "t.name LIKE :name";
        $params[':name'] = '%' . $name . '%';
    }
    
    // Handle 'sem disciplinas' (no disciplines) filter
    if ($status === 'no-disciplines') {
        $joins[] = "LEFT JOIN postg_teacher_disciplines td ON t.id = td.teacher_id";
        $conditions[] = "td.teacher_id IS NULL";
        
        if ($category !== '') {
            $joins[] = "INNER JOIN postg_teacher_activities ta ON t.id = ta.teacher_id";
            $conditions[] = "ta.activity_id = :category";
            $params[':category'] = $category;
        }
    } else {
        // Handle category filter
        if ($category !== '') {
            $joins[] = "LEFT JOIN postg_teacher_activities ta ON t.id = ta.teacher_id";
            $conditions[] = "ta.activity_id = :category";
            $params[':category'] = $category;
        }
        
        // Handle status/course filters - need to join disciplines
        if ($status !== '' || $course !== '') {
            $joins[] = "INNER JOIN postg_teacher_disciplines td ON t.id = td.teacher_id";
            
            if ($course !== '') {
                $conditions[] = "td.discipline_id = :course";
                $params[':course'] = $course;
            }
            
            // CRITICAL FIX: Calculate final status from BOTH evaluations
            if ($status === '1') {
                // Apto: both evaluations must be approved (1)
                $conditions[] = "(td.gese_evaluation = 1 AND td.pedagogico_evaluation = 1)";
            } else if ($status === '0') {
                // Inapto: at least one evaluation is rejected (0)
                $conditions[] = "((td.gese_evaluation = 0 OR td.pedagogico_evaluation = 0) AND (td.gese_evaluation IS NOT NULL OR td.pedagogico_evaluation IS NOT NULL))";
            } else if ($status === 'null') {
                // Aguardando: both evaluations are NULL
                $conditions[] = "(td.gese_evaluation IS NULL AND td.pedagogico_evaluation IS NULL)";
            }
        }
    }
    
    // Build complete query
    if (!empty($joins)) {
        $sql .= ' ' . implode(' ', array_unique($joins));
    }
    if (!empty($conditions)) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY t.created_at DESC';
    
    // Execute main query
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process each teacher to get their disciplines with correct status
    $result = [];
    foreach ($teachers as $teacher) {
        // Special handling for 'no-disciplines' status
        if ($status === 'no-disciplines') {
            $teacher['discipline_statuses'] = '';
            $result[] = $teacher;
            continue;
        }
        
        // Get disciplines for this teacher
        $discSql = "
            SELECT 
                d.id,
                d.name,
                td.gese_evaluation,
                td.pedagogico_evaluation,
                td.called_at,
                GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') as activities
            FROM postg_teacher_disciplines td
            INNER JOIN postg_disciplinas d ON td.discipline_id = d.id
            LEFT JOIN postg_teacher_activities ta ON ta.teacher_id = td.teacher_id
            LEFT JOIN activities a ON a.id = ta.activity_id
            WHERE td.teacher_id = :user_id
        ";
        
        $discParams = [':user_id' => $teacher['id']];
        
        if ($course !== '') {
            $discSql .= " AND d.id = :course";
            $discParams[':course'] = $course;
        }
        
        // CRITICAL FIX: Apply status filter based on BOTH evaluations
        if ($status === '1') {
            // Apto: both evaluations must be approved
            $discSql .= " AND td.gese_evaluation = 1 AND td.pedagogico_evaluation = 1";
        } else if ($status === '0') {
            // Inapto: at least one evaluation is rejected
            $discSql .= " AND (td.gese_evaluation = 0 OR td.pedagogico_evaluation = 0) AND (td.gese_evaluation IS NOT NULL OR td.pedagogico_evaluation IS NOT NULL)";
        } else if ($status === 'null') {
            // Aguardando: both evaluations are NULL
            $discSql .= " AND td.gese_evaluation IS NULL AND td.pedagogico_evaluation IS NULL";
        }
        
        $discSql .= " GROUP BY d.id, d.name, td.gese_evaluation, td.pedagogico_evaluation, td.called_at ORDER BY d.name";
        
        $discStmt = $conn->prepare($discSql);
        $discStmt->execute($discParams);
        $disciplines = $discStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($disciplines)) {
            $disciplineStatuses = [];
            
            foreach ($disciplines as $disc) {
                // Clean name to avoid delimiter conflicts
                $cleanName = str_replace(['|~|', '|~~|'], ' ', $disc['name']);
                
                // Get activities or set default
                $activityName = !empty($disc['activities']) ? $disc['activities'] : 'Sem atividade';
                
                // Calculate final status from BOTH evaluations (matching display logic)
                $gese_eval = $disc['gese_evaluation'];
                $ped_eval = $disc['pedagogico_evaluation'];
                
                if ($gese_eval === null && $ped_eval === null) {
                    $statusValue = 'null'; // Aguardando
                } elseif ($gese_eval === 1 && $ped_eval === 1) {
                    $statusValue = '1'; // Apto
                } elseif ($gese_eval === 0 || $ped_eval === 0) {
                    $statusValue = '0'; // Inapto
                } else {
                    // Partial evaluation (one is set, the other is not)
                    $statusValue = 'partial'; // Em avaliação
                }
                
                // Format called_at date
                $calledAt = $disc['called_at'] ? date('d/m/Y', strtotime($disc['called_at'])) : '';
                
                // Build discipline status string
                $disciplineStatuses[] = $disc['id'] . '|~|' . $cleanName . '|~|' . $activityName . '|~|' . $statusValue . '|~|' . $calledAt;
            }
            
            $teacher['discipline_statuses'] = implode('|~~|', $disciplineStatuses);
            $result[] = $teacher;
        } else if ($status === '') {
            // Include teachers without matching disciplines only when no status filter is applied
            $teacher['discipline_statuses'] = '';
            $result[] = $teacher;
        }
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log("Database Error in get_filtered_postg_teachers.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error occurred',
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General Error in get_filtered_postg_teachers.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'An unexpected error occurred',
        'message' => $e->getMessage()
    ]);
}