<?php
// backend/api/export_technicians_pdf.php
require_once '../classes/database.class.php';
require_once '../../vendor/autoload.php';

// Use TCPDF for PDF generation
use \TCPDF;

// Get filter parameters
$status = $_GET['status'] ?? '';

try {
    $conection = new Database();
    $conn = $conection->connect();

    // Build query with filters
    $sql = "SELECT * FROM technician";
    $where = [];
    $params = [];

    // Apply status filter
    if ($status !== '') {
        if ($status === 'null') {
            $where[] = "(enabled IS NULL OR enabled = '')";
        } else {
            $where[] = "enabled = :status";
            $params[':status'] = intval($status);
        }
    }

    // Add WHERE clause if there are conditions
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    // Order by creation date
    $sql .= ' ORDER BY created_at ASC';

    // Execute query
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create PDF
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('ESESP');
    $pdf->SetAuthor('ESESP');
    $pdf->SetTitle('Lista de Técnicos');
    $pdf->SetSubject('Técnicos Credenciados');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 10);

    // Title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Lista de Técnicos', 0, 1, 'C');
    $pdf->Ln(5);

    // Filter information
    if ($status !== '') {
        $pdf->SetFont('helvetica', '', 10);
        $filterText = 'Filtros aplicados: ';

        $statusText = $status === '1' ? 'Apto' : ($status === '0' ? 'Inapto' : 'Aguardando');
        $filterText .= "Status: $statusText";

        $pdf->Cell(0, 6, $filterText, 0, 1, 'L');
        $pdf->Ln(3);
    }

    // Table header
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(240, 240, 240);

    // Define column widths
    $colWidths = [
        'name' => 60,
        'email' => 50,
        'date' => 35,
        'called' => 35,
        'status' => 25,
        'scholarship' => 30
    ];

    // Header cells
    $pdf->Cell($colWidths['name'], 8, 'Nome', 1, 0, 'L', true);
    $pdf->Cell($colWidths['email'], 8, 'Email', 1, 0, 'L', true);
    $pdf->Cell($colWidths['date'], 8, 'Data Inscrição', 1, 0, 'L', true);
    $pdf->Cell($colWidths['called'], 8, 'Chamada', 1, 0, 'L', true);
    $pdf->Cell($colWidths['scholarship'], 8, 'Escolaridade', 1, 0, 'L', true);
    $pdf->Cell($colWidths['status'], 8, 'Status', 1, 1, 'C', true);

    // Table content
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetFillColor(255, 255, 255);

    foreach ($technicians as $technician) {
        // Format date
        $date = new DateTime($technician['created_at']);
        $dateF = $date->format('d/m/Y');

        // Format called_at
        $calledF = '-';
        if (!empty($technician['called_at'])) {
            $calledDate = new DateTime($technician['called_at']);
            $calledF = $calledDate->format('d/m/Y');
        }
        // Format status
        $statusText = match ($technician['enabled']) {
            1 => 'Apto',
            0 => 'Inapto',
            default => 'Aguardando'
        };

        // Truncate long text
        $name = substr($technician['name'], 0, 35);
        $email = substr($technician['email'], 0, 30);
        $scholarship = isset($technician['scholarship']) ? substr($technician['scholarship'], 0, 20) : 'N/A';

        // Add row
        $pdf->Cell($colWidths['name'], 7, $name, 1, 0, 'L');
        $pdf->Cell($colWidths['email'], 7, strtolower($email), 1, 0, 'L');
        $pdf->Cell($colWidths['date'], 7, $dateF, 1, 0, 'C');
        $pdf->Cell($colWidths['called'], 7, $calledF, 1, 0, 'C');
        $pdf->Cell($colWidths['scholarship'], 7, $scholarship, 1, 0, 'L');
        $pdf->Cell($colWidths['status'], 7, $statusText, 1, 1, 'C');
    }

    // Add summary
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Total de técnicos: ' . count($technicians), 0, 1, 'L');
    $pdf->Cell(0, 6, 'Data de geração: ' . date('d/m/Y H:i'), 0, 1, 'L');

    // Output PDF
    $pdf->Output('tecnicos_' . date('Y-m-d') . '.pdf', 'D');
} catch (Exception $e) {
    // Return error as JSON
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
