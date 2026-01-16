<?php
/**
 * Webhook Endpoint - Recebe dados do e-flow
 * URL: https://credenciamento.esesp.es.gov.br/api/webhook-eflow.php
 * 
 * Configure no e-flow:
 * - Método HTTP: POST
 * - URL do Endpoint: https://credenciamento.esesp.es.gov.br/api/webhook-eflow.php
 * - Autenticação: API Key (opcional, mas recomendado)
 */

// Headers
header('Content-Type: application/json');

// Arquivo de log
define('LOG_FILE', __DIR__ . '/../logs/webhook-eflow.log');

/**
 * Função para registrar logs
 */
function logWebhook($message, $data = null) {
    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}";
    
    if ($data !== null) {
        $logMessage .= "\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    $logMessage .= "\n" . str_repeat('-', 80) . "\n";
    
    file_put_contents(LOG_FILE, $logMessage, FILE_APPEND);
}

try {
    // 1. VALIDAR AUTENTICAÇÃO (OPCIONAL MAS RECOMENDADO)
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;
    $expectedApiKey = getenv('EFLOW_WEBHOOK_API_KEY') ?: 'sua_chave_secreta_aqui';
    
    if ($apiKey !== $expectedApiKey) {
        logWebhook("ERRO: Chave API inválida", ['received' => $apiKey]);
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized'
        ]);
        exit;
    }
    
    // 2. LER DADOS RECEBIDOS DO E-FLOW
    $rawData = file_get_contents('php://input');
    logWebhook("Webhook recebido", ['raw' => $rawData]);
    
    $data = json_decode($rawData, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON inválido: " . json_last_error_msg());
    }
    
    logWebhook("Dados decodificados", $data);
    
    // 3. VALIDAR ESTRUTURA DOS DADOS
    if (!isset($data['numeroProcesso'])) {
        throw new Exception("Dados incompletos: numeroProcesso não encontrado");
    }
    
    // 4. CONECTAR AO BANCO DE DADOS
    require_once __DIR__ . '/../config/database.php';
    $db = getDbConnection();
    
    // 5. PROCESSAR DADOS CONFORME O TIPO DE EVENTO
    $numeroProcesso = $data['numeroProcesso'];
    $eventoTipo = $data['tipoEvento'] ?? 'novo_processo'; // novo_processo, mudanca_status, etc
    
    logWebhook("Processando evento", [
        'processo' => $numeroProcesso,
        'tipo' => $eventoTipo
    ]);
    
    // Extrair informações do payload
    $cpf = $data['cpfInteressado'] ?? null;
    $nome = $data['nomeInteressado'] ?? null;
    $status = $data['status'] ?? 'Em análise';
    $dataAbertura = $data['dataAbertura'] ?? date('Y-m-d H:i:s');
    $assunto = $data['assunto'] ?? null;
    
    // Extrair campos do formulário (se presentes)
    $disciplinas = [];
    $atividades = [];
    $formacaoAcademica = [];
    $experiencia = [];
    
    if (isset($data['campos']) && is_array($data['campos'])) {
        foreach ($data['campos'] as $campo) {
            $nomeCampo = strtolower($campo['nome'] ?? '');
            $valor = $campo['valor'] ?? null;
            
            if (strpos($nomeCampo, 'disciplina') !== false) {
                $disciplinas[] = $valor;
            } elseif (strpos($nomeCampo, 'atividade') !== false) {
                $atividades[] = $valor;
            } elseif (strpos($nomeCampo, 'formacao') !== false || strpos($nomeCampo, 'academica') !== false) {
                $formacaoAcademica[] = $valor;
            } elseif (strpos($nomeCampo, 'experiencia') !== false) {
                $experiencia[] = $valor;
            }
        }
    }
    
    // Extrair documentos
    $documentos = [];
    if (isset($data['documentos']) && is_array($data['documentos'])) {
        foreach ($data['documentos'] as $doc) {
            $documentos[] = [
                'id' => $doc['id'] ?? null,
                'nome' => $doc['nome'] ?? 'Documento',
                'tipo' => $doc['tipo'] ?? null,
                'url' => $doc['url'] ?? null,
                'data_upload' => $doc['dataUpload'] ?? null
            ];
        }
    }
    
    // Verificar data de chamada
    $dataChamada = null;
    if (isset($data['movimentacoes']) && is_array($data['movimentacoes'])) {
        foreach ($data['movimentacoes'] as $mov) {
            $descricao = strtolower($mov['descricao'] ?? '');
            if (strpos($descricao, 'chamada') !== false || 
                strpos($descricao, 'convocação') !== false ||
                strpos($descricao, 'convocado') !== false) {
                $dataChamada = $mov['data'] ?? null;
                break;
            }
        }
    }
    
    // 6. VERIFICAR SE PROCESSO JÁ EXISTE
    $stmt = $db->prepare("SELECT id FROM docentes_eflow WHERE numero_processo = ?");
    $stmt->execute([$numeroProcesso]);
    $existe = $stmt->fetch();
    
    if ($existe) {
        // ATUALIZAR
        logWebhook("Atualizando processo existente", ['processo' => $numeroProcesso]);
        
        $sql = "UPDATE docentes_eflow SET 
                nome = ?,
                cpf = ?,
                data_envio = ?,
                data_chamada = ?,
                disciplinas = ?,
                atividades = ?,
                formacao_academica = ?,
                experiencia = ?,
                documentos = ?,
                status = ?,
                ultima_atualizacao = NOW(),
                webhook_payload = ?
                WHERE numero_processo = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $nome,
            $cpf,
            $dataAbertura,
            $dataChamada,
            json_encode($disciplinas, JSON_UNESCAPED_UNICODE),
            json_encode($atividades, JSON_UNESCAPED_UNICODE),
            json_encode($formacaoAcademica, JSON_UNESCAPED_UNICODE),
            json_encode($experiencia, JSON_UNESCAPED_UNICODE),
            json_encode($documentos, JSON_UNESCAPED_UNICODE),
            $status,
            json_encode($data, JSON_UNESCAPED_UNICODE),
            $numeroProcesso
        ]);
        
        $action = 'updated';
        
    } else {
        // INSERIR
        logWebhook("Inserindo novo processo", ['processo' => $numeroProcesso]);
        
        $sql = "INSERT INTO docentes_eflow (
                numero_processo, cpf, nome, data_envio, data_chamada,
                disciplinas, atividades, formacao_academica, experiencia,
                documentos, status, ultima_atualizacao, webhook_payload
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $numeroProcesso,
            $cpf,
            $nome,
            $dataAbertura,
            $dataChamada,
            json_encode($disciplinas, JSON_UNESCAPED_UNICODE),
            json_encode($atividades, JSON_UNESCAPED_UNICODE),
            json_encode($formacaoAcademica, JSON_UNESCAPED_UNICODE),
            json_encode($experiencia, JSON_UNESCAPED_UNICODE),
            json_encode($documentos, JSON_UNESCAPED_UNICODE),
            $status,
            json_encode($data, JSON_UNESCAPED_UNICODE)
        ]);
        
        $action = 'created';
    }
    
    // 7. BUSCAR EMAIL DO ACESSO CIDADÃO (SE DISPONÍVEL)
    if ($cpf) {
        $stmtEmail = $db->prepare("
            SELECT email FROM usuarios WHERE cpf = ? LIMIT 1
        ");
        $stmtEmail->execute([$cpf]);
        $usuario = $stmtEmail->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario && !empty($usuario['email'])) {
            $db->prepare("
                UPDATE docentes_eflow 
                SET email_acesso_cidadao = ? 
                WHERE numero_processo = ?
            ")->execute([$usuario['email'], $numeroProcesso]);
        }
    }
    
    // 8. REGISTRAR LOG NO BANCO
    $db->prepare("
        INSERT INTO webhook_eflow_log (
            numero_processo, tipo_evento, payload, acao, data_recebimento
        ) VALUES (?, ?, ?, ?, NOW())
    ")->execute([
        $numeroProcesso,
        $eventoTipo,
        json_encode($data, JSON_UNESCAPED_UNICODE),
        $action
    ]);
    
    logWebhook("Processo salvo com sucesso", [
        'processo' => $numeroProcesso,
        'acao' => $action
    ]);
    
    // 9. RESPONDER AO E-FLOW
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Dados processados com sucesso',
        'processo' => $numeroProcesso,
        'action' => $action
    ]);
    
} catch (Exception $e) {
    logWebhook("ERRO ao processar webhook", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}