<?php
// backend/api/export_docentes_excel.php - FIXED VERSION
require_once '../classes/database.class.php';

try {
    // Clear any previous output
    ob_clean();
    
    $conection = new Database();
    $conn = $conection->connect();
    
    $category = $_GET['category'] ?? '';
    $course = $_GET['course'] ?? '';
    $status = $_GET['status'] ?? '';
    $name = $_GET['name'] ?? '';
    
    // Build query
    $sql = "SELECT DISTINCT t.id, t.name, t.email, t.created_at, t.called_at FROM teacher t";
    $joins = [];
    $where = [];
    $params = [];
    
    if ($name !== '') {
        $where[] = "t.name LIKE :name";
        $params[':name'] = '%' . $name . '%';
    }
    
    if ($category !== '') {
        $joins[] = "INNER JOIN teacher_activities ta ON t.id = ta.teacher_id";
        $where[] = "ta.activity_id = :category";
        $params[':category'] = $category;
    }
    
    if ($status === 'no-disciplines') {
        $joins[] = "LEFT JOIN teacher_disciplines td ON t.id = td.teacher_id";
        $where[] = "td.teacher_id IS NULL";
    } else if ($status !== '') {
        $joins[] = "INNER JOIN teacher_disciplines td_filter ON t.id = td_filter.teacher_id";
        
        if ($course !== '') {
            $where[] = "td_filter.discipline_id = :course";
            $params[':course'] = $course;
        }
        
        if ($status === '1') {
            $where[] = "(td_filter.enabled = '1' OR td_filter.enabled = 1)";
        } else if ($status === '0') {
            $where[] = "(td_filter.enabled = '0' OR td_filter.enabled = 0) AND td_filter.enabled IS NOT NULL";
        } else if ($status === 'null') {
            // FIXED: Exclude numeric zeros
            $where[] = "(td_filter.enabled IS NULL OR (td_filter.enabled = '' AND td_filter.enabled != 0))";
        }
    } else if ($course !== '') {
        $joins[] = "INNER JOIN teacher_disciplines td_filter ON t.id = td_filter.teacher_id";
        $where[] = "td_filter.discipline_id = :course";
        $params[':course'] = $course;
    }
    
    $sql .= ' ' . implode(' ', $joins);
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY t.created_at DESC';
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get filter labels
    $filterLabels = [];
    
    if ($category !== '') {
        $catStmt = $conn->prepare("SELECT name FROM activities WHERE id = :id");
        $catStmt->execute([':id' => $category]);
        $catName = $catStmt->fetchColumn();
        $filterLabels[] = "Categoria: " . ($catName ?: "ID $category");
    }
    
    if ($course !== '') {
        $courseStmt = $conn->prepare("SELECT name FROM disciplinas WHERE id = :id");
        $courseStmt->execute([':id' => $course]);
        $courseName = $courseStmt->fetchColumn();
        $filterLabels[] = "Curso: " . ($courseName ?: "ID $course");
    }
    
    if ($status !== '') {
        $statusMap = [
            '1' => 'Apto',
            '0' => 'Inapto',
            'null' => 'Aguardando',
            'no-disciplines' => 'Sem disciplinas'
        ];
        $filterLabels[] = "Status: " . ($statusMap[$status] ?? $status);
    }
    
    if ($name !== '') {
        $filterLabels[] = "Nome: " . $name;
    }
    
    // Set headers for CSV
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="docentes_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Content-Description: File Transfer');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Expires: 0');
    header('Pragma: public');
    
    // UTF-8 BOM for Excel
    echo "\xEF\xBB\xBF";
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Set proper locale for Excel semicolon delimiter
    setlocale(LC_ALL, 'pt_BR.UTF-8');
    
    // Write filter info
    if (!empty($filterLabels)) {
        $filterText = 'FILTROS APLICADOS: ' . implode(' | ', $filterLabels);
        fputcsv($output, [$filterText, '', '', '', ''], ';', '"');
        fputcsv($output, ['', '', '', '', ''], ';', '"');
    }
    
    // Headers
    fputcsv($output, ['Nome', 'Email', 'Data de Inscrição', 'Chamado em', 'Disciplinas'], ';', '"');
    
    // Process teachers
    foreach ($teachers as $teacher) {
        if ($status === 'no-disciplines') {
            fputcsv($output, [
                $teacher['name'],
                $teacher['email'],
                date('d/m/Y H:i', strtotime($teacher['created_at'])),
                $teacher['called_at'] ? date('d/m/Y', strtotime($teacher['called_at'])) : '-',
                'Sem disciplinas'
            ], ';', '"');
            continue;
        }
        
        // Get disciplines
        $discSql = "SELECT d.name, td.enabled 
                    FROM teacher_disciplines td 
                    INNER JOIN disciplinas d ON td.discipline_id = d.id 
                    WHERE td.teacher_id = :teacher_id";
        
        $discParams = [':teacher_id' => $teacher['id']];
        
        if ($course !== '') {
            $discSql .= " AND d.id = :course";
            $discParams[':course'] = $course;
        }
        
        // FIXED: Apply status filter
        if ($status === '1') {
            $discSql .= " AND (td.enabled = '1' OR td.enabled = 1)";
        } else if ($status === '0') {
            $discSql .= " AND (td.enabled = '0' OR td.enabled = 0) AND td.enabled IS NOT NULL";
        } else if ($status === 'null') {
            $discSql .= " AND (td.enabled IS NULL OR (td.enabled = '' AND td.enabled != 0))";
        }
        
        $discSql .= " ORDER BY d.name";
        
        $discStmt = $conn->prepare($discSql);
        $discStmt->execute($discParams);
        $disciplines = $discStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $discList = [];
        foreach ($disciplines as $disc) {
            $enabled = $disc['enabled'];
            
            // Determine status
            if ($enabled === null) {
                $statusText = 'Aguardando';
            } else if ($enabled === '' && $enabled !== 0 && $enabled !== '0') {
                $statusText = 'Aguardando';
            } else if ($enabled === '0' || $enabled === 0) {
                $statusText = 'Inapto';
            } else if ($enabled === '1' || $enabled === 1) {
                $statusText = 'Apto';
            } else {
                $statusText = 'Aguardando';
            }
            
            $discList[] = $disc['name'] . ' (' . $statusText . ')';
        }
        
        // Write row
        fputcsv($output, [
            $teacher['name'],
            $teacher['email'],
            date('d/m/Y H:i', strtotime($teacher['created_at'])),
            $teacher['called_at'] ? date('d/m/Y', strtotime($teacher['called_at'])) : '-',
            !empty($discList) ? implode(' | ', $discList) : 'Sem disciplinas'
        ], ';', '"');
    }
    
    fclose($output);
    exit;
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Export failed: ' . $e->getMessage()]);
}
?>