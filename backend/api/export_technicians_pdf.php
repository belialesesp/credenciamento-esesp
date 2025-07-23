<?php
// =================================================================
// backend/api/export_technicians_pdf.php
// FIXED VERSION WITH PROPER OUTPUT BUFFERING
// =================================================================

// Prevent ANY output before PDF
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

require_once '../classes/database.class.php';
require_once '../../vendor/autoload.php';

try {
    // Clean all output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    $conection = new Database();
    $conn = $conection->connect();

    // Get filter parameters
    $status = $_GET['status'] ?? '';

    // Build query
    $sql = "SELECT id, name, email, phone, created_at, enabled, called_at, scholarship 
            FROM technician";
    $where = [];
    $params = [];

    if ($status !== '') {
        if ($status === 'null') {
            $where[] = "(enabled IS NULL OR enabled = '')";
        } else {
            $where[] = "enabled = :status";
            $params[':status'] = intval($status);
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

    // Create new PDF document
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('ESESP');
    $pdf->SetAuthor('ESESP');
    $pdf->SetTitle('Lista de Técnicos');

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
    $pdf->Cell(0, 10, 'Lista de Técnicos', 0, 1, 'C');
    $pdf->Ln(5);

    // Filter info
    if ($status !== '') {
        $pdf->SetFont('helvetica', '', 10);
        $statusText = match ($status) {
            '1' => 'Apto',
            '0' => 'Inapto',
            default => 'Aguardando'
        };
        $pdf->Cell(0, 6, 'Filtro: Status = ' . $statusText, 0, 1, 'L');
        $pdf->Ln(3);
    }

    // Table header
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);

    $pdf->Cell(55, 8, 'Nome', 1, 0, 'L', true);
    $pdf->Cell(45, 8, 'Email', 1, 0, 'L', true);
    $pdf->Cell(30, 8, 'Telefone', 1, 0, 'L', true);
    $pdf->Cell(30, 8, 'Inscrição', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Chamada', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Escolaridade', 1, 0, 'L', true);
    $pdf->Cell(20, 8, 'Status', 1, 1, 'C', true);

    // Table data
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(255, 255, 255);

    foreach ($technicians as $tech) {
        $dateF = date('d/m/Y', strtotime($tech['created_at']));
        $calledF = $tech['called_at'] ? date('d/m/Y', strtotime($tech['called_at'])) : '-';
        
        $statusText = match (intval($tech['enabled'])) {
            1 => 'Apto',
            0 => 'Inapto',
            default => 'Aguardando'
        };

        $pdf->Cell(55, 7, substr($tech['name'], 0, 35), 1, 0, 'L');
        $pdf->Cell(45, 7, substr(strtolower($tech['email']), 0, 30), 1, 0, 'L');
        $pdf->Cell(30, 7, $tech['phone'], 1, 0, 'L');
        $pdf->Cell(30, 7, $dateF, 1, 0, 'C');
        $pdf->Cell(30, 7, $calledF, 1, 0, 'C');
        $pdf->Cell(30, 7, substr($tech['scholarship'] ?? 'N/A', 0, 20), 1, 0, 'L');
        $pdf->Cell(20, 7, $statusText, 1, 1, 'C');
    }

    // Summary
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Total: ' . count($technicians) . ' técnicos', 0, 1, 'L');

    // Close and output PDF
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