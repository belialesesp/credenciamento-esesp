<?php
/**
 * Webhook com suporte a múltiplas variações de header
 */

$debugLog = __DIR__ . '/../logs/webhook-debug.log';
$logDir = dirname($debugLog);
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

file_put_contents($debugLog, "\n" . str_repeat('=', 80) . "\n", FILE_APPEND);
file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] WEBHOOK CHAMADO\n", FILE_APPEND);
file_put_contents($debugLog, "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n", FILE_APPEND);
file_put_contents($debugLog, "Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown') . "\n", FILE_APPEND);

// Log TODOS os headers
file_put_contents($debugLog, "\n=== TODOS OS HEADERS ===\n", FILE_APPEND);
foreach (getallheaders() as $name => $value) {
    file_put_contents($debugLog, "{$name}: {$value}\n", FILE_APPEND);
}

header('Content-Type: application/json');

// Ler body
$rawData = file_get_contents('php://input');
file_put_contents($debugLog, "\n=== BODY ===\n" . $rawData . "\n", FILE_APPEND);

// BUSCAR API KEY EM TODAS AS VARIAÇÕES POSSÍVEIS
$apiKey = null;

// Variação 1: HTTP_X_API_KEY
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
if ($apiKey) file_put_contents($debugLog, "✅ API Key encontrada em HTTP_X_API_KEY\n", FILE_APPEND);

// Variação 2: HTTP_X_Api_Key (case insensitive)
if (!$apiKey) {
    foreach ($_SERVER as $key => $value) {
        if (strtolower($key) === 'http_x_api_key') {
            $apiKey = $value;
            file_put_contents($debugLog, "✅ API Key encontrada em {$key}\n", FILE_APPEND);
            break;
        }
    }
}

// Variação 3: getallheaders() case insensitive
if (!$apiKey) {
    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
    $apiKey = $headers['x-api-key'] ?? null;
    if ($apiKey) file_put_contents($debugLog, "✅ API Key encontrada via getallheaders()\n", FILE_APPEND);
}

// Variação 4: Query string
if (!$apiKey) {
    $apiKey = $_GET['api_key'] ?? null;
    if ($apiKey) file_put_contents($debugLog, "✅ API Key encontrada na query string\n", FILE_APPEND);
}

// Variação 5: Body JSON
if (!$apiKey && $rawData) {
    $tempData = json_decode($rawData, true);
    if (is_array($tempData)) {
        $apiKey = $tempData['api_key'] ?? $tempData['apiKey'] ?? null;
        if ($apiKey) file_put_contents($debugLog, "✅ API Key encontrada no body JSON\n", FILE_APPEND);
    }
}

file_put_contents($debugLog, "API Key final: " . ($apiKey ?? 'NENHUMA') . "\n", FILE_APPEND);

$expectedApiKey = getenv('EFLOW_WEBHOOK_API_KEY') ?: 'esesp_webhook_2025_a3k9m2l5p8';

// Validar
if ($apiKey !== $expectedApiKey) {
    file_put_contents($debugLog, "❌ REJEITADO - API Key inválida\n", FILE_APPEND);
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized',
        'received' => $apiKey,
        'expected' => substr($expectedApiKey, 0, 15) . '...'
    ]);
    exit;
}

file_put_contents($debugLog, "✅ Autenticação OK!\n", FILE_APPEND);

// Decodificar dados
$data = json_decode($rawData, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    file_put_contents($debugLog, "❌ JSON inválido: " . json_last_error_msg() . "\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit;
}

file_put_contents($debugLog, "\n=== DADOS PROCESSADOS ===\n" . print_r($data, true) . "\n", FILE_APPEND);

// Incluir database
try {
    require_once __DIR__ . '/../config/database.php';
    file_put_contents($debugLog, "✅ Conexão com banco OK\n", FILE_APPEND);
} catch (Exception $e) {
    file_put_contents($debugLog, "❌ Erro database: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}

// Extrair dados (suporta múltiplos formatos)
$numeroProcesso = $data['numeroProcesso'] ?? $data['numero_processo'] ?? $data['NumeroProcesso'] ?? 'PROC-' . time();
$nome = $data['nomeInteressado'] ?? $data['nome'] ?? $data['Nome'] ?? null;
$cpf = $data['cpfInteressado'] ?? $data['cpf'] ?? $data['CPF'] ?? null;
$status = $data['status'] ?? $data['Status'] ?? 'Em análise';

file_put_contents($debugLog, "Extraído - Processo: {$numeroProcesso}, Nome: {$nome}, CPF: {$cpf}\n", FILE_APPEND);

// Categoria
$categoria = 'docente';
if (isset($data['categoria'])) {
    $cat = strtolower($data['categoria']);
    if (strpos($cat, 'pós') !== false || strpos($cat, 'pos') !== false) {
        $categoria = 'docente_pos';
    } elseif (strpos($cat, 'técnico') !== false || strpos($cat, 'tecnico') !== false) {
        $categoria = 'tecnico';
    } elseif (strpos($cat, 'intérprete') !== false || strpos($cat, 'interprete') !== false) {
        $categoria = 'interprete';
    }
}

try {
    // Verificar se existe
    $stmt = $pdo->prepare("SELECT id FROM docentes_eflow WHERE numero_processo = ?");
    $stmt->execute([$numeroProcesso]);
    $existe = $stmt->fetch();
    
    if ($existe) {
        // Atualizar
        $pdo->prepare("
            UPDATE docentes_eflow SET 
                nome = ?, cpf = ?, status = ?, categoria = ?,
                ultima_atualizacao = NOW(), webhook_payload = ?
            WHERE numero_processo = ?
        ")->execute([
            $nome, $cpf, $status, $categoria,
            json_encode($data, JSON_UNESCAPED_UNICODE),
            $numeroProcesso
        ]);
        $action = 'updated';
        file_put_contents($debugLog, "✅ Registro ATUALIZADO (ID: {$existe['id']})\n", FILE_APPEND);
    } else {
        // Inserir
        $pdo->prepare("
            INSERT INTO docentes_eflow (
                numero_processo, cpf, nome, status, categoria,
                data_envio, ultima_atualizacao, webhook_payload
            ) VALUES (?, ?, ?, ?, ?, NOW(), NOW(), ?)
        ")->execute([
            $numeroProcesso, $cpf, $nome, $status, $categoria,
            json_encode($data, JSON_UNESCAPED_UNICODE)
        ]);
        $insertId = $pdo->lastInsertId();
        $action = 'created';
        file_put_contents($debugLog, "✅ Registro CRIADO (ID: {$insertId})\n", FILE_APPEND);
    }
    
    // Log
    $pdo->prepare("
        INSERT INTO webhook_eflow_log (
            numero_processo, tipo_evento, payload, acao, data_recebimento
        ) VALUES (?, ?, ?, ?, NOW())
    ")->execute([
        $numeroProcesso,
        $data['tipoEvento'] ?? 'eflow_webhook',
        json_encode($data, JSON_UNESCAPED_UNICODE),
        $action
    ]);
    
    file_put_contents($debugLog, "✅✅✅ SUCESSO TOTAL! ✅✅✅\n", FILE_APPEND);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Dados processados com sucesso',
        'processo' => $numeroProcesso,
        'action' => $action,
        'categoria' => $categoria
    ]);
    
} catch (Exception $e) {
    file_put_contents($debugLog, "❌ Erro ao salvar: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

file_put_contents($debugLog, str_repeat('=', 80) . "\n", FILE_APPEND);