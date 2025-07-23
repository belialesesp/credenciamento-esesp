<?php
// =================================================================
// backend/api/export_docentes_pdf.php - WITHOUT CPF/PHONE
// =================================================================

if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

require_once '../classes/database.class.php';
require_once '../../vendor/autoload.php';

try {
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    $conection = new Database();
    $conn = $conection->connect();
    
    $category = $_GET['category'] ?? '';
    $course = $_GET['course'] ?? '';
    $status = $_GET['status'] ?? '';
    
    // Get teachers (without phone and cpf in SELECT)
    if ($status === 'no-disciplines') {
        $sql = "
            SELECT DISTINCT
                t.id, t.name, t.email, t.called_at, t.created_at
            FROM teacher t
            LEFT JOIN teacher_disciplines td ON t.id = td.teacher_id
        ";
        
        $where = ["td.teacher_id IS NULL"];
        $params = [];
        
        if ($category !== '') {
            $sql .= " INNER JOIN teacher_activities ta ON t.id = ta.teacher_id";
            $where[] = "ta.activity_id = :category";
            $params[':category'] = $category;
        }
        
        $sql .= " WHERE " . implode(" AND ", $where);
        $sql .= " ORDER BY t.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $sql = "SELECT DISTINCT t.id, t.name, t.email, t.called_at, t.created_at FROM teacher t";
        $joins = [];
        $where = [];
        $params = [];
        
        if ($category !== '') {
            $joins[] = "INNER JOIN teacher_activities ta ON t.id = ta.teacher_id";
            $where[] = "ta.activity_id = :category";
            $params[':category'] = $category;
        }
        
        if ($status !== '' || $course !== '') {
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
        }
        
        $sql .= ' ' . implode(' ', $joins);
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY t.created_at DESC';
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get disciplines for each teacher
        foreach ($teachers as &$teacher) {
            $disciplinesSql = "
                SELECT d.id, d.name, td.enabled
                FROM teacher_disciplines td
                INNER JOIN disciplinas d ON td.discipline_id = d.id
                WHERE td.teacher_id = :teacher_id
                ORDER BY d.name ASC
            ";
            
            $disciplinesStmt = $conn->prepare($disciplinesSql);
            $disciplinesStmt->execute([':teacher_id' => $teacher['id']]);
            $teacher['disciplines'] = $disciplinesStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Sistema de Docentes');
    $pdf->SetAuthor('Sistema de Docentes');
    $pdf->SetTitle('Lista de Docentes - ' . date('d/m/Y'));
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(true);
    $pdf->setFooterData(array(0,64,0), array(0,64,128));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetFooterMargin(10);
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->AddPage();
    
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Lista de Docentes', 0, 1, 'C');
    
    if ($category || $course || $status) {
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Ln(5);
        $filterText = 'Filtros aplicados: ';
        $filters = [];
        if ($category) $filters[] = 'Categoria';
        if ($course) $filters[] = 'Curso';
        if ($status) {
            $statusText = match ($status) {
                '1' => 'Apto',
                '0' => 'Inapto',
                'null' => 'Aguardando',
                'no-disciplines' => 'Sem disciplinas',
                default => ''
            };
            if ($statusText) $filters[] = 'Status: ' . $statusText;
        }
        $filterText .= implode(', ', $filters);
        $pdf->Cell(0, 6, $filterText, 0, 1, 'L');
    }
    
    $pdf->Ln(5);
    
    // Table without phone column
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(230, 230, 230);
    
    $pdf->Cell(60, 8, 'Nome', 1, 0, 'L', true);
    $pdf->Cell(50, 8, 'Email', 1, 0, 'L', true);
    $pdf->Cell(20, 8, 'Chamado', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Inscrição', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Status', 1, 1, 'C', true);
    
    $pdf->SetFont('helvetica', '', 9);
    
    foreach ($teachers as $teacher) {
        $created_at = new DateTime($teacher['created_at']);
        $dateF = $created_at->format('d/m/Y');
        $called_at = $teacher['called_at'] ? (new DateTime($teacher['called_at']))->format('d/m/Y') : '-';
        
        // Determine status
        $statusText = 'N/A';
        if (isset($teacher['disciplines']) && count($teacher['disciplines']) > 0) {
            $hasApto = false;
            $hasInapto = false;
            $hasAguardando = false;
            
            foreach ($teacher['disciplines'] as $disc) {
                if ($disc['enabled'] == 1) $hasApto = true;
                elseif ($disc['enabled'] == 0) $hasInapto = true;
                else $hasAguardando = true;
            }
            
            if ($hasApto && !$hasInapto && !$hasAguardando) $statusText = 'Apto';
            elseif ($hasInapto && !$hasApto && !$hasAguardando) $statusText = 'Inapto';
            elseif ($hasAguardando && !$hasApto && !$hasInapto) $statusText = 'Aguardando';
            else $statusText = 'Misto';
        } else {
            $statusText = 'Sem disc.';
        }
        
        $pdf->Cell(60, 7, substr($teacher['name'], 0, 35), 1, 0, 'L');
        $pdf->Cell(50, 7, substr($teacher['email'], 0, 30), 1, 0, 'L');
        $pdf->Cell(20, 7, $called_at, 1, 0, 'C');
        $pdf->Cell(25, 7, $dateF, 1, 0, 'C');
        $pdf->Cell(25, 7, $statusText, 1, 1, 'C');
    }
    
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Total de docentes: ' . count($teachers), 0, 1, 'L');
    $pdf->Cell(0, 6, 'Data de geração: ' . date('d/m/Y H:i'), 0, 1, 'L');
    
    $pdf->Output('docentes_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
    
} catch (Exception $e) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'PDF generation error: ' . $e->getMessage()]);
}
exit;
?>