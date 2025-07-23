<?php
// backend/api/export_docentes_excel.php
require_once '../classes/database.class.php';

try {
    // Clear any previous output
    ob_clean();
    
    $conection = new Database();
    $conn = $conection->connect();
    
    $category = $_GET['category'] ?? '';
    $course = $_GET['course'] ?? '';
    $status = $_GET['status'] ?? '';
    
    // Build query
    $sql = "SELECT DISTINCT t.id, t.name, t.email, t.created_at, t.called_at FROM teacher t";
    $joins = [];
    $where = [];
    $params = [];
    
    if ($category !== '') {
        $joins[] = "INNER JOIN teacher_activities ta ON t.id = ta.teacher_id";
        $where[] = "ta.activity_id = :category";
        $params[':category'] = $category;
    }
    
    if ($status !== '' && $status !== 'no-disciplines') {
        $joins[] = "INNER JOIN teacher_disciplines td_filter ON t.id = td_filter.teacher_id";
        
        if ($course !== '') {
            $where[] = "td_filter.discipline_id = :course";
            $params[':course'] = $course;
        }
        
        if ($status === '1') {
            $where[] = "td_filter.enabled = 1";
        } else if ($status === '0') {
            $where[] = "td_filter.enabled = 0";
        } else if ($status === 'null') {
            $where[] = "(td_filter.enabled IS NULL OR td_filter.enabled = '')";
        }
    } else if ($status === 'no-disciplines') {
        $joins[] = "LEFT JOIN teacher_disciplines td ON t.id = td.teacher_id";
        $where[] = "td.teacher_id IS NULL";
    }
    
    $sql .= ' ' . implode(' ', $joins);
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY t.created_at DESC';
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for CSV (not XLSX)
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="docentes_' . date('Y-m-d') . '.csv"');
    
    // UTF-8 BOM for Excel
    echo "\xEF\xBB\xBF";
    
    // Output
    $output = fopen('php://output', 'w');
    
    // Headers
    fputcsv($output, ['Nome', 'Email', 'Data de Inscrição', 'Chamado em', 'Disciplinas'], ';');
    
    // Data
    foreach ($teachers as $teacher) {
        // Get disciplines
        $discSql = "SELECT d.name, td.enabled 
                    FROM teacher_disciplines td
                    INNER JOIN disciplinas d ON td.discipline_id = d.id
                    WHERE td.teacher_id = :teacher_id";
        $discStmt = $conn->prepare($discSql);
        $discStmt->execute([':teacher_id' => $teacher['id']]);
        $disciplines = $discStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $discList = [];
        foreach ($disciplines as $disc) {
            // PHP 7 compatible version (no match expression)
            $enabled = intval($disc['enabled']);
            if ($enabled === 1) {
                $status = 'Apto';
            } elseif ($enabled === 0) {
                $status = 'Inapto';
            } else {
                $status = 'Aguardando';
            }
            $discList[] = $disc['name'] . ' (' . $status . ')';
        }
        
        fputcsv($output, [
            $teacher['name'],
            $teacher['email'],
            date('d/m/Y H:i', strtotime($teacher['created_at'])),
            $teacher['called_at'] ? date('d/m/Y', strtotime($teacher['called_at'])) : '-',
            implode(' | ', $discList)
        ], ';');
    }
    
    fclose($output);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Export failed: ' . $e->getMessage()]);
}
exit;
?>