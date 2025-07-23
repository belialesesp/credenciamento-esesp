<?php
// backend/api/export_docentes_pdf.php
require_once '../classes/database.class.php';
require_once '../../vendor/autoload.php';

try {
    // Clear any previous output
    ob_clean();
    
    $conection = new Database();
    $conn = $conection->connect();
    
    // Get filter parameters
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
    $pdf->Ln(5);
    
    // Table header
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(200, 200, 200);
    
    $pdf->Cell(60, 8, 'Nome', 1, 0, 'L', true);
    $pdf->Cell(50, 8, 'Email', 1, 0, 'L', true);
    $pdf->Cell(30, 8, 'Inscrição', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Chamado em', 1, 0, 'C', true);
    $pdf->Cell(107, 8, 'Disciplinas', 1, 1, 'L', true);
    
    // Table content
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(240, 240, 240);
    $fill = false;
    
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
        
        $disciplinesText = empty($discList) ? 'Sem disciplinas' : implode(' | ', $discList);
        
        // Calculate row height based on content
        $nameHeight = $pdf->getStringHeight(60, $teacher['name'], false, true, '', 1);
        $emailHeight = $pdf->getStringHeight(50, $teacher['email'], false, true, '', 1);
        $discHeight = $pdf->getStringHeight(107, $disciplinesText, false, true, '', 1);
        $rowHeight = max(8, $nameHeight, $emailHeight, $discHeight);
        
        // Check if we need a new page
        if ($pdf->GetY() + $rowHeight > 190) {
            $pdf->AddPage();
            // Repeat header
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetFillColor(200, 200, 200);
            $pdf->Cell(60, 8, 'Nome', 1, 0, 'L', true);
            $pdf->Cell(50, 8, 'Email', 1, 0, 'L', true);
            $pdf->Cell(30, 8, 'Inscrição', 1, 0, 'C', true);
            $pdf->Cell(30, 8, 'Chamado em', 1, 0, 'C', true);
            $pdf->Cell(107, 8, 'Disciplinas', 1, 1, 'L', true);
            $pdf->SetFont('helvetica', '', 8);
        }
        
        // Name
        $pdf->MultiCell(60, $rowHeight, $teacher['name'], 1, 'L', $fill, 0);
        
        // Email
        $pdf->MultiCell(50, $rowHeight, $teacher['email'], 1, 'L', $fill, 0);
        
        // Registration date
        $pdf->MultiCell(30, $rowHeight, date('d/m/Y', strtotime($teacher['created_at'])), 1, 'C', $fill, 0);
        
        // Called date
        $calledDate = $teacher['called_at'] ? date('d/m/Y', strtotime($teacher['called_at'])) : '-';
        $pdf->MultiCell(30, $rowHeight, $calledDate, 1, 'C', $fill, 0);
        
        // Disciplines
        $pdf->MultiCell(107, $rowHeight, $disciplinesText, 1, 'L', $fill, 1);
        
        $fill = !$fill;
    }
    
    // Summary
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 8, 'Total de docentes: ' . count($teachers), 0, 1, 'L');
    
    // Output PDF
    $pdf->Output('docentes_' . date('Y-m-d') . '.pdf', 'D');
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'PDF generation failed: ' . $e->getMessage()]);
}
?>