<?php
// backend/api/export_docentes_pos_excel.php - FINAL FIX
error_reporting(0);

require_once '../classes/database.class.php';

$conection = new Database();
$conn = $conection->connect();

// Get parameters
$category = $_GET['category'] ?? '';
$course = $_GET['course'] ?? '';
$status = $_GET['status'] ?? '';
$name = $_GET['name'] ?? '';

// Build query with proper joins
$sql = "SELECT DISTINCT t.id, t.name, t.email, t.created_at, t.called_at FROM postg_teacher t";
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
    // Find teachers with NO disciplines at all
    $joins[] = "LEFT JOIN postg_teacher_disciplines td ON t.id = td.teacher_id";
    $conditions[] = "td.teacher_id IS NULL";
    
    // Still apply category filter if present
    if ($category !== '') {
        $joins[] = "INNER JOIN postg_teacher_activities ta ON t.id = ta.teacher_id";
        $conditions[] = "ta.activity_id = :category";
        $params[':category'] = $category;
    }
} else {
    // Handle category filter (keep as is - working fine)
    if ($category !== '') {
        $joins[] = "LEFT JOIN postg_teacher_activities ta ON t.id = ta.teacher_id";
        $conditions[] = "ta.activity_id = :category";
        $params[':category'] = $category;
    }

    // Handle status/course filters
    if ($status !== '' || $course !== '') {
        // Use INNER JOIN when filtering by status to only get teachers with disciplines
        $joins[] = "INNER JOIN postg_teacher_disciplines td ON t.id = td.teacher_id";
        
        if ($course !== '') {
            $conditions[] = "td.discipline_id = :course";
            $params[':course'] = $course;
        }
        
        if ($status === '1') {
            $conditions[] = "(td.enabled = '1' OR td.enabled = 1)";
        } else if ($status === '0') {
            // Explicitly check for 0, not NULL
            $conditions[] = "(td.enabled = '0' OR td.enabled = 0) AND td.enabled IS NOT NULL";
        } else if ($status === 'null') {
            // FIXED: Exclude numeric zeros by checking data type
            $conditions[] = "(td.enabled IS NULL OR (td.enabled = '' AND td.enabled != 0) OR UPPER(td.enabled) = 'NULL')";
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

// Execute query
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Export Query Error: " . $e->getMessage());
    $teachers = [];
}

// Get filter labels
$filterLabels = [];

if ($category !== '') {
    $catStmt = $conn->prepare("SELECT name FROM activities WHERE id = :id");
    $catStmt->execute([':id' => $category]);
    $catName = $catStmt->fetchColumn();
    $filterLabels[] = "Categoria: " . ($catName ?: "ID $category");
}

if ($course !== '') {
    $courseStmt = $conn->prepare("SELECT name FROM postg_disciplinas WHERE id = :id");
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

// Output CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="docentes_pos_' . date('Y-m-d_H-i-s') . '.csv"');
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
    // Special handling for 'no-disciplines' status
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
    
    // Get disciplines with prepared statement
    $discSql = "SELECT d.name, td.enabled 
                FROM postg_teacher_disciplines td 
                INNER JOIN postg_disciplinas d ON td.discipline_id = d.id 
                WHERE td.teacher_id = :teacher_id";
    
    $discParams = [':teacher_id' => $teacher['id']];
    
    if ($course !== '') {
        $discSql .= " AND d.id = :course";
        $discParams[':course'] = $course;
    }
    
    // CRITICAL FIX: Apply status filter to only show matching disciplines
    if ($status === '1') {
        $discSql .= " AND (td.enabled = '1' OR td.enabled = 1)";
    } else if ($status === '0') {
        $discSql .= " AND (td.enabled = '0' OR td.enabled = 0) AND td.enabled IS NOT NULL";
    } else if ($status === 'null') {
        // FIXED: Properly filter for NULL/empty, excluding numeric zeros
        $discSql .= " AND (td.enabled IS NULL OR (td.enabled = '' AND td.enabled != 0) OR UPPER(td.enabled) = 'NULL')";
    }
    
    $discSql .= " ORDER BY d.name";
    
    try {
        $discStmt = $conn->prepare($discSql);
        $discStmt->execute($discParams);
        $disciplines = $discStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Discipline Query Error: " . $e->getMessage());
        $disciplines = [];
    }
    
    $discList = [];
    foreach ($disciplines as $disc) {
        $enabled = $disc['enabled'];
        
        // Determine status - be explicit about types
        if ($enabled === null) {
            $statusText = 'Aguardando';
        } else if ($enabled === '' && $enabled !== 0 && $enabled !== '0') {
            // True empty string, not numeric zero
            $statusText = 'Aguardando';
        } else if (is_string($enabled) && strtoupper(trim($enabled)) === 'NULL') {
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
?>