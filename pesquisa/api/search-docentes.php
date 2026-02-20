<?php
/**
 * API: Search Docentes from e-flow
 * Returns docentes from both teacher and postg_teacher tables for autocomplete
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/init.php';

// Get search term
$searchTerm = $_GET['q'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Validate search term
if (strlen($searchTerm) < 2) {
    echo json_encode([
        'results' => [],
        'pagination' => ['more' => false]
    ]);
    exit;
}

try {
    // Connect to e-flow database (credenciamento)
    $eflowDb = getEflowConnection();
    
    $searchPattern = '%' . $searchTerm . '%';
    
    // Search in both tables with UNION
    $sql = "
        (
            SELECT 
                cpf,
                name,
                'docente' as source
            FROM teacher
            WHERE (name LIKE :search1 OR cpf LIKE :search2)
            AND cpf IS NOT NULL
            AND name IS NOT NULL
        )
        UNION
        (
            SELECT 
                cpf,
                name,
                'postg_docente' as source
            FROM postg_teacher
            WHERE (name LIKE :search3 OR cpf LIKE :search4)
            AND cpf IS NOT NULL
            AND name IS NOT NULL
        )
        ORDER BY name
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $eflowDb->prepare($sql);
    $stmt->bindValue(':search1', $searchPattern, PDO::PARAM_STR);
    $stmt->bindValue(':search2', $searchPattern, PDO::PARAM_STR);
    $stmt->bindValue(':search3', $searchPattern, PDO::PARAM_STR);
    $stmt->bindValue(':search4', $searchPattern, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $perPage + 1, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $docentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if there are more results
    $hasMore = count($docentes) > $perPage;
    if ($hasMore) {
        array_pop($docentes); // Remove the extra result
    }
    
    // Format results for Select2
    $results = array_map(function($docente) {
        $cpf = preg_replace('/[^0-9]/', '', $docente['cpf']);
        return [
            'id' => $cpf,
            'text' => $docente['name'],
            'name' => $docente['name'],
            'cpf' => $cpf,
            'cpf_formatted' => formatCPF($cpf),
            'source' => $docente['source']
        ];
    }, $docentes);
    
    // Remove duplicates by CPF (in case same person is in both tables)
    $uniqueResults = [];
    $seenCPFs = [];
    foreach ($results as $result) {
        if (!in_array($result['cpf'], $seenCPFs)) {
            $uniqueResults[] = $result;
            $seenCPFs[] = $result['cpf'];
        }
    }
    
    echo json_encode([
        'results' => $uniqueResults,
        'pagination' => [
            'more' => $hasMore
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error searching docentes: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao buscar docentes',
        'results' => [],
        'pagination' => ['more' => false]
    ]);
}

/**
 * Get connection to e-flow database
 */
function getEflowConnection() {
    // Replace with your actual e-flow database credentials
    $host = DB_HOST; // Same host as pesquisa
    $dbname = 'credenciamento'; // e-flow database name
    $username = DB_USER;
    $password = DB_PASS;
    
    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log("E-flow DB Connection Error: " . $e->getMessage());
        throw new Exception("Erro ao conectar com banco de dados e-flow");
    }
}

/**
 * Format CPF with dots and dash
 */
function formatCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) == 11) {
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }
    return $cpf;
}
?>