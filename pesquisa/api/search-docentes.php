<?php
/**
 * API Endpoint: Search Docentes
 * Returns list of registered docentes for autocomplete
 * 
 * Usage: /pesquisa/api/search-docentes.php?q=maria
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/init.php';

// Check authentication
if (!function_exists('getCurrentUserCPF') || !getCurrentUserCPF()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

// Get search query
$search = $_GET['q'] ?? '';

if (strlen($search) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $db = PesquisaDatabase::getInstance();
    
    // Search for docentes in the user table
    // These are users who came from e-flow registration
    $docentes = $db->fetchAll("
        SELECT DISTINCT 
            u.cpf as value,
            u.name as label,
            u.cpf as cpf,
            u.email,
            u.user_type as type
        FROM user u
        WHERE u.user_type IN ('teacher', 'postg_teacher')
          AND u.enabled = 1
          AND (
              u.name LIKE ? OR
              u.cpf LIKE ? OR
              u.email LIKE ?
          )
        ORDER BY u.name
        LIMIT 20
    ", [
        '%' . $search . '%',
        '%' . preg_replace('/[^0-9]/', '', $search) . '%',
        '%' . $search . '%'
    ]);
    
    // Format CPF for display
    foreach ($docentes as &$docente) {
        if (!empty($docente['cpf']) && strlen($docente['cpf']) === 11) {
            $docente['cpf_formatted'] = substr($docente['cpf'], 0, 3) . '.' . 
                                        substr($docente['cpf'], 3, 3) . '.' . 
                                        substr($docente['cpf'], 6, 3) . '-' . 
                                        substr($docente['cpf'], 9, 2);
        } else {
            $docente['cpf_formatted'] = $docente['cpf'];
        }
        
        // Add type description
        $docente['type_label'] = $docente['type'] === 'teacher' ? 'Docente' : 'Docente Pós-Graduação';
        
        // Make label more informative
        $docente['label'] = $docente['label'] . ' - CPF: ' . $docente['cpf_formatted'];
    }
    
    echo json_encode($docentes);
    
} catch (Exception $e) {
    error_log("Error in search-docentes: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar docentes']);
}
?>