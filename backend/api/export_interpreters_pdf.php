<?php
// backend/api/export_interpreters_pdf.php - FIXED VERSION V2

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
    $name = $_GET['name'] ?? '';

    $sql = "SELECT id, name, email, created_at, enabled, called_at, scholarship 
            FROM interpreter";
    $where = [];
    $params = [];

    // Add name filter
    if ($name !== '') {
        $where[] = "name LIKE :name";
        $params[':name'] = '%' . $name . '%';
    }

    // Add status filter - FIXED with proper type checking (same as technicians)
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
    $interpreters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('ESESP');
    $pdf->SetAuthor('ESESP');
    $pdf->SetTitle('Lista de Intérpretes');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(TRUE, 10);
    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Lista de Intérpretes de Libras', 0, 1, 'C');
    $pdf->Ln(5);

    // Filter information
    $filterInfo = [];
    if ($status !== '') {
        $statusText = match ($status) {
            '1' => 'Apto',
            '0' => 'Inapto',
            'null' => 'Aguardando',
            default => 'Aguardando'
        };
        $filterInfo[] = 'Status: ' . $statusText;
    }
    if ($name !== '') {
        $filterInfo[] = 'Nome: ' . $name;
    }

    if (!empty($filterInfo)) {
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, 'Filtros: ' . implode(' | ', $filterInfo), 0, 1, 'L');
        $pdf->Ln(3);
    }

    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);

    $pdf->Cell(55, 8, 'Nome', 1, 0, 'L', true);
    $pdf->Cell(45, 8, 'Email', 1, 0, 'L', true);
    $pdf->Cell(30, 8, 'Inscrição', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Chamada', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Escolaridade', 1, 0, 'L', true);
    $pdf->Cell(20, 8, 'Status', 1, 1, 'C', true);

    $pdf->SetFont('helvetica', '', 8);

    foreach ($interpreters as $interp) {
        $dateF = date('d/m/Y', strtotime($interp['created_at']));
        $calledF = $interp['called_at'] ? date('d/m/Y', strtotime($interp['called_at'])) : '-';
        
        // Check actual value, not intval
        $enabled = $interp['enabled'];
        
        // Strict type checking for status (same as technicians)
        if ($enabled === null || $enabled === '' || (is_string($enabled) && trim($enabled) === '')) {
            $statusText = 'Aguardando';
        } else if ($enabled === '1' || $enabled === 1) {
            $statusText = 'Apto';
        } else if ($enabled === '0' || $enabled === 0) {
            $statusText = 'Inapto';
        } else {
            $statusText = 'Aguardando';
        }

        $pdf->Cell(55, 7, substr($interp['name'], 0, 35), 1, 0, 'L');
        $pdf->Cell(45, 7, substr(strtolower($interp['email']), 0, 30), 1, 0, 'L');
        $pdf->Cell(30, 7, $dateF, 1, 0, 'C');
        $pdf->Cell(30, 7, $calledF, 1, 0, 'C');
        $pdf->Cell(30, 7, substr($interp['scholarship'] ?? 'N/A', 0, 20), 1, 0, 'L');
        $pdf->Cell(20, 7, $statusText, 1, 1, 'C');
    }

    $pdf->Ln(5);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Total: ' . count($interpreters) . ' intérpretes', 0, 1, 'L');
    $pdf->Cell(0, 6, 'Data de geração: ' . date('d/m/Y H:i'), 0, 1, 'L');

    $pdf->Output('interpretes_' . date('Y-m-d') . '.pdf', 'D');

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