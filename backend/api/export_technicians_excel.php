<?php
// backend/api/export_technicians_excel.php
require_once '../classes/database.class.php';

try {
    // Clear any previous output
    ob_clean();
    
    $conection = new Database();
    $conn = $conection->connect();
    
    $status = $_GET['status'] ?? '';
    
    // Build query
    $sql = "SELECT id, name, email, created_at, called_at, enabled FROM technician";
    $params = [];
    
    if ($status !== '') {
        if ($status === '1') {
            $sql .= " WHERE enabled = 1";
        } else if ($status === '0') {
            $sql .= " WHERE enabled = 0";
        } else if ($status === 'null') {
            $sql .= " WHERE enabled IS NULL OR enabled = ''";
        }
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for CSV
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="tecnicos_' . date('Y-m-d') . '.csv"');
    
    // UTF-8 BOM for Excel
    echo "\xEF\xBB\xBF";
    
    // Output
    $output = fopen('php://output', 'w');
    
    // Headers
    fputcsv($output, ['Nome', 'Email', 'Data de Inscrição', 'Chamado em', 'Status'], ';');
    
    // Data
    foreach ($technicians as $technician) {
        $enabled = $technician['enabled'];
        if ($enabled === null || $enabled === '') {
            $statusText = 'Aguardando';
        } elseif ($enabled == 1) {
            $statusText = 'Apto';
        } else {
            $statusText = 'Inapto';
        }
        
        fputcsv($output, [
            $technician['name'],
            $technician['email'],
            date('d/m/Y H:i', strtotime($technician['created_at'])),
            $technician['called_at'] ? date('d/m/Y', strtotime($technician['called_at'])) : '-',
            $statusText
        ], ';');
    }
    
    fclose($output);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Export failed: ' . $e->getMessage()]);
}
exit;
?>