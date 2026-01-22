<?php
/**
 * Endpoint API para listar docentes (VERSÃO CORRIGIDA)
 * URL: /api/docentes.php
 */

// Incluir arquivo de inicialização do projeto
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

/**
 * Conectar ao banco de dados
 */
function getDbConnection() {
    $configFile = __DIR__ . '/../config/database.php';
    
    if (!file_exists($configFile)) {
        throw new Exception("Arquivo de configuração do banco não encontrado");
    }
    
    $config = require $configFile;
    
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        
        return $pdo;
        
    } catch (PDOException $e) {
        throw new Exception("Erro ao conectar ao banco: " . $e->getMessage());
    }
}

// Verificar autenticação (comentar se não tiver sistema de autenticação ainda)
// if (!isset($_SESSION['user'])) {
//     http_response_code(401);
//     echo json_encode(['success' => false, 'error' => 'Não autenticado']);
//     exit;
// }

try {
    // Conectar ao banco
    $db = getDbConnection();
    
    // Parâmetros de filtro e paginação
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
    $offset = ($page - 1) * $perPage;
    
    $status = $_GET['status'] ?? null;
    $busca = $_GET['busca'] ?? null;
    $disciplina = $_GET['disciplina'] ?? null;
    $categoria = $_GET['categoria'] ?? null;
    
    // Construir query
    $where = [];
    $params = [];
    
    if ($status) {
        $where[] = "status = ?";
        $params[] = $status;
    }
    
    if ($busca) {
        $where[] = "(nome LIKE ? OR cpf LIKE ? OR numero_processo LIKE ?)";
        $params[] = "%{$busca}%";
        $params[] = "%{$busca}%";
        $params[] = "%{$busca}%";
    }
    
    if ($disciplina) {
        $where[] = "JSON_SEARCH(disciplinas, 'one', ?) IS NOT NULL";
        $params[] = "%{$disciplina}%";
    }
    
    if ($categoria) {
        $where[] = "categoria = ?";
        $params[] = $categoria;
    }
    
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    // Contar total
    $stmtCount = $db->prepare("SELECT COUNT(*) as total FROM docentes_eflow {$whereClause}");
    $stmtCount->execute($params);
    $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Buscar docentes
    $sql = "SELECT d.*
            FROM docentes_eflow d
            {$whereClause}
            ORDER BY d.data_envio DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $docentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Processar dados (decodificar JSON)
    foreach ($docentes as &$docente) {
        $docente['disciplinas'] = json_decode($docente['disciplinas'], true) ?: [];
        $docente['atividades'] = json_decode($docente['atividades'], true) ?: [];
        $docente['formacao_academica'] = json_decode($docente['formacao_academica'], true) ?: [];
        $docente['experiencia'] = json_decode($docente['experiencia'], true) ?: [];
        $docente['documentos'] = json_decode($docente['documentos'], true) ?: [];
        $docente['processos_anteriores'] = json_decode($docente['processos_anteriores'], true) ?: [];
        
        // Usar email do Acesso Cidadão se disponível
        if (!empty($docente['email_acesso_cidadao'])) {
            $docente['email'] = $docente['email_acesso_cidadao'];
        }
        
        // Adicionar nome amigável da categoria
        $categorias = [
            'docente' => 'Docente',
            'docente_pos' => 'Docente Pós-Graduação',
            'tecnico' => 'Técnico',
            'interprete' => 'Intérprete'
        ];
        $docente['categoria_nome'] = $categorias[$docente['categoria']] ?? $docente['categoria'];
    }
    
    // Estatísticas por categoria
    $stmtStats = $db->query("
        SELECT categoria, COUNT(*) as total 
        FROM docentes_eflow 
        GROUP BY categoria
    ");
    $estatisticas = $stmtStats->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Resposta
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $docentes,
        'pagination' => [
            'total' => (int)$total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ],
        'estatisticas' => $estatisticas
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    
    error_log("Erro ao listar docentes: " . $e->getMessage());
}