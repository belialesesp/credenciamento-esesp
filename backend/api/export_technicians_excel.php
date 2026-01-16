<?php
// backend/api/export_technicians_excel.php - FIXED VERSION V2
require_once '../classes/database.class.php';

try {
    // Clear any previous output
    ob_clean();
    
    $conection = new Database();
    $conn = $conection->connect();
    
    // Get filter parameters
    $status = $_GET['status'] ?? '';
    $name = $_GET['name'] ?? '';
    
    // Build query
    $sql = "SELECT id, name, email, created_at, enabled, called_at, scholarship 
            FROM technician";
    $where = [];
    $params = [];
    
    // Add name filter
    if ($name !== '') {
        $where[] = "name LIKE :name";
        $params[':name'] = '%' . $name . '%';
    }
    
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
    
    // Get filter labels
    $filterLabels = [];
    if ($status !== '') {
        $statusLabel = match($status) {
            '1' => 'Apto',
            '0' => 'Inapto', 
            'null' => 'Aguardando',
            default => 'Todos'
        };
        $filterLabels[] = "Status: " . $statusLabel;
    }
    
    if ($name !== '') {
        $filterLabels[] = "Nome: " . $name;
    }
    
    // Set headers for CSV
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="tecnicos_' . date('Y-m-d') . '.csv"');
    
    // UTF-8 BOM for Excel
    echo "\xEF\xBB\xBF";
    
    // Output
    $output = fopen('php://output', 'w');
    
    // Add filter information at the top
    if (!empty($filterLabels)) {
        fputcsv($output, ['FILTROS APLICADOS: ' . implode(' | ', $filterLabels)], ';');
        fputcsv($output, [''], ';'); // Empty line
    }
    
    // Headers (without CPF/phone)
    fputcsv($output, ['Nome', 'Email', 'Data de Inscrição', 'Chamado em', 'Status'], ';');
    
    // Data
    foreach ($technicians as $tech) {
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
        
        fputcsv($output, [
            $tech['name'],
            strtolower($tech['email']),
            date('d/m/Y', strtotime($tech['created_at'])),
            $tech['called_at'] ? date('d/m/Y', strtotime($tech['called_at'])) : '-',
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