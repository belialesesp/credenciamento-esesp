<?php
// backend/api/export_technicians_pdf.php - FIXED VERSION V2

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

    $status = $_GET['status'] ?? '';

    $sql = "SELECT id, name, email, created_at, enabled, called_at, scholarship 
            FROM technician";
    $where = [];
    $params = [];

    // Add status filter - FIXED with proper type checking
    if ($status !== '') {
        if ($status === 'null') {
            // For aguardando: only NULL or empty string, NOT numeric 0
            // Use BINARY to avoid MySQL type coercion where 0 = ''
            $where[] = "(enabled IS NULL OR (BINARY enabled = '' AND enabled != 0))";
        } else if ($status === '1') {
            $where[] = "(enabled = 1 OR enabled = '1')";
        } else if ($status === '0') {
            // For inapto: only 0, not NULL
            $where[] = "(enabled = 0 OR enabled = '0') AND enabled IS NOT NULL";
        }
    }

    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= " ORDER BY 
              CASE WHEN called_at IS NULL THEN 1 ELSE 0 END,
              called_at DESC,
              created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('ESESP');
    $pdf->SetAuthor('ESESP');
    $pdf->SetTitle('Lista de Técnicos');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(TRUE, 10);
    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Lista de Técnicos', 0, 1, 'C');
    $pdf->Ln(5);

    if ($status !== '') {
        $pdf->SetFont('helvetica', '', 10);
        $statusText = match ($status) {
            '1' => 'Apto',
            '0' => 'Inapto',
            'null' => 'Aguardando',
            default => 'Aguardando'
        };
        $pdf->Cell(0, 6, 'Filtro: Status = ' . $statusText, 0, 1, 'L');
        $pdf->Ln(3);
    }

    // Table without Phone column
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);

    $pdf->Cell(70, 8, 'Nome', 1, 0, 'L', true);
    $pdf->Cell(60, 8, 'Email', 1, 0, 'L', true);
    $pdf->Cell(35, 8, 'Inscrição', 1, 0, 'C', true);
    $pdf->Cell(35, 8, 'Chamada', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Escolaridade', 1, 0, 'L', true);
    $pdf->Cell(25, 8, 'Status', 1, 1, 'C', true);

    $pdf->SetFont('helvetica', '', 8);

    foreach ($technicians as $tech) {
        $dateF = date('d/m/Y', strtotime($tech['created_at']));
        $calledF = $tech['called_at'] ? date('d/m/Y', strtotime($tech['called_at'])) : '-';
        
        // Check actual value, not intval
        $enabled = $tech['enabled'];
        
        // Strict type checking for status
        if ($enabled === null || $enabled === '' || (is_string($enabled) && trim($enabled) === '')) {
            $statusText = 'Aguardando';
        } else if ($enabled === '1' || $enabled === 1) {
            $statusText = 'Apto';
        } else if ($enabled === '0' || $enabled === 0) {
            $statusText = 'Inapto';
        } else {
            $statusText = 'Aguardando';
        }

        $pdf->Cell(70, 7, substr($tech['name'], 0, 40), 1, 0, 'L');
        $pdf->Cell(60, 7, substr(strtolower($tech['email']), 0, 35), 1, 0, 'L');
        $pdf->Cell(35, 7, $dateF, 1, 0, 'C');
        $pdf->Cell(35, 7, $calledF, 1, 0, 'C');
        $pdf->Cell(40, 7, substr($tech['scholarship'] ?? 'N/A', 0, 25), 1, 0, 'L');
        $pdf->Cell(25, 7, $statusText, 1, 1, 'C');
    }

    $pdf->Ln(5);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Total: ' . count($technicians) . ' técnicos', 0, 1, 'L');
    $pdf->Cell(0, 6, 'Data de geração: ' . date('d/m/Y H:i'), 0, 1, 'L');

    $pdf->Output('tecnicos_' . date('Y-m-d') . '.pdf', 'D');

} catch (Exception $e) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
exit;
?>