<?php
// backend/api/export_docentes_pos_pdf.php - FIXED VERSION
require_once '../classes/database.class.php';
require_once '../../vendor/autoload.php';

// Clear any previous output
ob_clean();

$conection = new Database();
$conn = $conection->connect();

// Get filter parameters
$category = $_GET['category'] ?? '';
$course = $_GET['course'] ?? '';
$status = $_GET['status'] ?? '';
$name = $_GET['name'] ?? '';

// Build query
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

    // Handle status/course filters
    if ($status !== '' || $course !== '') {
        $joins[] = "INNER JOIN postg_teacher_disciplines td ON t.id = td.teacher_id";
        
        if ($course !== '') {
            $conditions[] = "td.discipline_id = :course";
            $params[':course'] = $course;
        }
        
        if ($status === '1') {
            $conditions[] = "(td.enabled = '1' OR td.enabled = 1)";
        } else if ($status === '0') {
            $conditions[] = "(td.enabled = '0' OR td.enabled = 0) AND td.enabled IS NOT NULL";
        } else if ($status === 'null') {
            // FIXED: Exclude numeric zeros
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
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create PDF
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('ESESP');
$pdf->SetAuthor('ESESP');
$pdf->SetTitle('Docentes - Pós-Graduação');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 10);

// Add a page
$pdf->AddPage();

// Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Docentes - Pós-Graduação', 0, 1, 'C');

// Date
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Gerado em: ' . date('d/m/Y H:i'), 0, 1, 'C');

// Filter info
if ($category || $course || $status || $name) {
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 5, 'Filtros Aplicados:', 0, 1);
    $pdf->SetFont('helvetica', '', 9);
    
    if ($category !== '') {
        $catStmt = $conn->prepare("SELECT name FROM activities WHERE id = :id");
        $catStmt->execute([':id' => $category]);
        $catName = $catStmt->fetchColumn();
        $pdf->Cell(0, 5, 'Categoria: ' . ($catName ?: "ID $category"), 0, 1);
    }
    
    if ($course !== '') {
        $courseStmt = $conn->prepare("SELECT name FROM postg_disciplinas WHERE id = :id");
        $courseStmt->execute([':id' => $course]);
        $courseName = $courseStmt->fetchColumn();
        $pdf->Cell(0, 5, 'Curso: ' . ($courseName ?: "ID $course"), 0, 1);
    }
    
    if ($status !== '') {
        $statusMap = [
            '1' => 'Apto',
            '0' => 'Inapto',
            'null' => 'Aguardando',
            'no-disciplines' => 'Sem disciplinas'
        ];
        $pdf->Cell(0, 5, 'Status: ' . ($statusMap[$status] ?? $status), 0, 1);
    }
    
    if ($name !== '') {
        $pdf->Cell(0, 5, 'Nome: ' . $name, 0, 1);
    }
}

// Table header
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(50, 7, 'Nome', 1, 0, 'L', true);
$pdf->Cell(50, 7, 'Email', 1, 0, 'L', true);
$pdf->Cell(30, 7, 'Data Inscrição', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Chamado em', 1, 0, 'C', true);
$pdf->Cell(110, 7, 'Disciplinas', 1, 1, 'L', true);

// Table content
$pdf->SetFont('helvetica', '', 8);
$pdf->SetFillColor(255, 255, 255);

foreach ($teachers as $teacher) {
    // Get disciplines for this teacher
    if ($status === 'no-disciplines') {
        $disciplineText = 'Sem disciplinas';
    } else {
        $discSql = "SELECT d.name, td.enabled 
                    FROM postg_teacher_disciplines td 
                    INNER JOIN postg_disciplinas d ON td.discipline_id = d.id 
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
            $discSql .= " AND (td.enabled IS NULL OR (td.enabled = '' AND td.enabled != 0) OR UPPER(td.enabled) = 'NULL')";
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
        
        $disciplineText = !empty($discList) ? implode(' | ', $discList) : 'Sem disciplinas';
    }
    
    // Calculate row height based on content
    $nameWidth = 50;
    $emailWidth = 50;
    $dateWidth = 30;
    $disciplineWidth = 110;
    
    $nameHeight = $pdf->getStringHeight($nameWidth, $teacher['name']);
    $emailHeight = $pdf->getStringHeight($emailWidth, $teacher['email']);
    $disciplineHeight = $pdf->getStringHeight($disciplineWidth, $disciplineText);
    
    $rowHeight = max(7, $nameHeight, $emailHeight, $disciplineHeight);
    
    // Add new page if needed
    if ($pdf->GetY() + $rowHeight > 190) {
        $pdf->AddPage();
        // Repeat header
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(50, 7, 'Nome', 1, 0, 'L', true);
        $pdf->Cell(50, 7, 'Email', 1, 0, 'L', true);
        $pdf->Cell(30, 7, 'Data Inscrição', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Chamado em', 1, 0, 'C', true);
        $pdf->Cell(110, 7, 'Disciplinas', 1, 1, 'L', true);
        $pdf->SetFont('helvetica', '', 8);
    }
    
    // Print row
    $pdf->MultiCell($nameWidth, $rowHeight, $teacher['name'], 1, 'L', false, 0);
    $pdf->MultiCell($emailWidth, $rowHeight, $teacher['email'], 1, 'L', false, 0);
    $pdf->MultiCell($dateWidth, $rowHeight, date('d/m/Y', strtotime($teacher['created_at'])), 1, 'C', false, 0);
    $pdf->MultiCell($dateWidth, $rowHeight, $teacher['called_at'] ? date('d/m/Y', strtotime($teacher['called_at'])) : '-', 1, 'C', false, 0);
    $pdf->MultiCell($disciplineWidth, $rowHeight, $disciplineText, 1, 'L', false, 1);
}

// Output PDF
$pdf->Output('docentes_pos_' . date('Y-m-d') . '.pdf', 'D');
exit;
?>