<?php
/**
 * Webhook DEBUG - Mostra todas as variáveis recebidas
 */

$log = '/var/www/html/logs/webhook-debug.log';

file_put_contents($log, "\n" . str_repeat('=', 80) . "\n", FILE_APPEND);
file_put_contents($log, "[" . date('Y-m-d H:i:s') . "] DEBUG DE VARIÁVEIS\n", FILE_APPEND);

$body = file_get_contents('php://input');
$data = json_decode($body, true);

file_put_contents($log, "\n=== BODY RAW ===\n{$body}\n", FILE_APPEND);
file_put_contents($log, "\n=== ANÁLISE DE CAMPOS ===\n", FILE_APPEND);

if (is_array($data)) {
    foreach ($data as $campo => $valor) {
        $tipo = gettype($valor);
        $temVariavel = (strpos($valor, '{$|') !== false);
        $temTemplate = (strpos($valor, '{{') !== false && strpos($valor, '}}') !== false);
        
        file_put_contents($log, sprintf(
            "%-30s | Tipo: %-10s | Valor: %s %s\n",
            $campo,
            $tipo,
            is_string($valor) ? substr($valor, 0, 50) : json_encode($valor),
            $temVariavel ? "⚠️ VARIÁVEL NÃO SUBSTITUÍDA" : ($temTemplate ? "⚠️ TEMPLATE" : "✅")
        ), FILE_APPEND);
    }
    
    // Contar problemas
    $problemas = 0;
    foreach ($data as $campo => $valor) {
        if (is_string($valor) && strpos($valor, '{$|') !== false) {
            $problemas++;
        }
    }
    
    file_put_contents($log, "\n📊 RESUMO:\n", FILE_APPEND);
    file_put_contents($log, "Total de campos: " . count($data) . "\n", FILE_APPEND);
    file_put_contents($log, "Campos com problema: {$problemas}\n", FILE_APPEND);
    
    if ($problemas > 0) {
        file_put_contents($log, "\n❌ AÇÃO NECESSÁRIA:\n", FILE_APPEND);
        file_put_contents($log, "O e-flow está enviando nomes de variáveis em vez de valores.\n", FILE_APPEND);
        file_put_contents($log, "Corrija o mapeamento no painel do e-flow!\n", FILE_APPEND);
    } else {
        file_put_contents($log, "\n✅ Campos parecem corretos!\n", FILE_APPEND);
        
        // Tentar salvar no banco
        try {
            require_once __DIR__ . '/../config/database.php';
            
            $numeroProcesso = $data['numeroProcesso'] ?? $data['edocs'] ?? 'PROC-' . time();
            $nome = $data['nomeCompleto'] ?? $data['nome'] ?? null;
            
            $pdo->prepare("
                INSERT INTO webhook_eflow_log (
                    numero_processo, tipo_evento, payload, acao, data_recebimento
                ) VALUES (?, ?, ?, ?, NOW())
            ")->execute([
                $numeroProcesso,
                'debug_test',
                $body,
                'received'
            ]);
            
            $id = $pdo->lastInsertId();
            file_put_contents($log, "✅ Salvo no banco! ID: {$id}\n", FILE_APPEND);
        } catch (Exception $e) {
            file_put_contents($log, "❌ Erro ao salvar: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
} else {
    file_put_contents($log, "❌ Body não é JSON válido!\n", FILE_APPEND);
}

file_put_contents($log, str_repeat('=', 80) . "\n", FILE_APPEND);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Debug recebido',
    'campos_recebidos' => is_array($data) ? count($data) : 0,
    'timestamp' => date('Y-m-d H:i:s')
]);