<?php
// backend/api/export_docentes_pdf.php - FIXED VERSION
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
    
    // Create PDF
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('ESESP');
    $pdf->SetAuthor('ESESP');
    $pdf->SetTitle('Docentes');
    
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
    $pdf->Cell(0, 10, 'Docentes', 0, 1, 'C');
    
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
            $courseStmt = $conn->prepare("SELECT name FROM disciplinas WHERE id = :id");
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