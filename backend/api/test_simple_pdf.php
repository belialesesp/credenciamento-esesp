<?php
// backend/api/test_simple_pdf.php
// Very simple test to verify PDF generation works

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test 1: Without TCPDF (using built-in PHP)
if (isset($_GET['test']) && $_GET['test'] === 'simple') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="test_simple.pdf"');
    
    // Very basic PDF structure
    echo "%PDF-1.4\n";
    echo "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    echo "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    echo "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources << >> /MediaBox [0 0 612 792] >>\nendobj\n";
    echo "xref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000058 00000 n\n0000000115 00000 n\n";
    echo "trailer\n<< /Size 4 /Root 1 0 R >>\nstartxref\n203\n%%EOF";
    exit;
}

// Test 2: With TCPDF
try {
    require_once '../../vendor/autoload.php';
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Test Script');
    $pdf->SetAuthor('ESESP');
    $pdf->SetTitle('Test PDF');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', 'B', 16);
    
    // Add content
    $pdf->Cell(0, 10, 'PDF Test Successful!', 0, 1, 'C');
    $pdf->Ln(10);
    
    $pdf->SetFont('helvetica', '', 12);
    $pdf->MultiCell(0, 10, 'If you can read this, PDF generation is working correctly.', 0, 'C');
    $pdf->Ln(10);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
    
    // Output PDF
    $pdf->Output('test_tcpdf.pdf', 'D');
    
} catch (Exception $e) {
    // Return error as JSON
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'PDF generation failed',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>