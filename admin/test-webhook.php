<?php
/**
 * Página de teste do webhook e-flow
 * URL: /admin/test-webhook.php
 * 
 * Permite testar o webhook manualmente e ver os logs
 */

session_start();
require_once __DIR__ . '/../init.php';

// Verificar autenticação
if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

// TODO: Verificar se é admin
// if (!isAdmin()) { die('Acesso negado'); }

$resultado = null;
$erro = null;

// Processar envio de teste
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['testar'])) {
    $payloadTeste = [
        'numeroProcesso' => '2025/TESTE-' . time(),
        'cpfInteressado' => '12345678900',
        'nomeInteressado' => 'Teste Webhook',
        'status' => 'Em análise',
        'dataAbertura' => date('Y-m-d H:i:s'),
        'assunto' => 'Teste de integração webhook',
        'tipoEvento' => 'teste',
        'campos' => [
            ['nome' => 'disciplinas', 'valor' => 'Gestão Pública, Liderança'],
            ['nome' => 'atividades', 'valor' => 'Docência, Tutoria'],
            ['nome' => 'formacao_academica', 'valor' => 'Mestrado em Administração'],
            ['nome' => 'experiencia', 'valor' => '10 anos em gestão pública']
        ]
    ];
    
    // Enviar para o webhook
    $ch = curl_init();
    
    $apiKey = getenv('EFLOW_WEBHOOK_API_KEY') ?: 'sua_chave_secreta_aqui';
    
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://' . $_SERVER['HTTP_HOST'] . '/api/webhook-eflow.php',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payloadTeste),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-API-Key: ' . $apiKey
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $resultado = json_decode($response, true);
    } else {
        $erro = "Erro HTTP {$httpCode}: {$response}";
    }
}

// Buscar últimos logs
$db = getDbConnection();
$logs = $db->query("
    SELECT * FROM webhook_eflow_log 
    ORDER BY id DESC 
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Webhook e-flow</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        h1 {
            color: #003366;
            margin-bottom: 30px;
        }
        
        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        
        .section h2 {
            color: #0066cc;
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .btn {
            background: #0066cc;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #0052a3;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .info-box {
            background: white;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #0066cc;
            margin-bottom: 15px;
        }
        
        .info-box h3 {
            color: #003366;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .info-box code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #003366;
            color: white;
            font-weight: bold;
        }
        
        tr:hover {
            background: #f5f5f5;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 5px;
            border-left: 4px solid #0066cc;
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #003366;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🔗 Teste de Webhook e-flow</h1>
    
    <?php if ($resultado): ?>
    <div class="success">
        ✅ <strong>Webhook testado com sucesso!</strong><br>
        Processo: <?= htmlspecialchars($resultado['processo']) ?><br>
        Ação: <?= htmlspecialchars($resultado['action']) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($erro): ?>
    <div class="error">
        ❌ <strong>Erro ao testar webhook:</strong><br>
        <?= htmlspecialchars($erro) ?>
    </div>
    <?php endif; ?>
    
    <div class="section">
        <h2>📋 Informações de Configuração</h2>
        
        <div class="info-box">
            <h3>URL do Webhook</h3>
            <code>https://<?= $_SERVER['HTTP_HOST'] ?>/api/webhook-eflow.php</code>
        </div>
        
        <div class="info-box">
            <h3>Método HTTP</h3>
            <code>POST</code>
        </div>
        
        <div class="info-box">
            <h3>Autenticação</h3>
            API Key via header: <code>X-API-Key</code><br>
            Status: <?= getenv('EFLOW_WEBHOOK_API_KEY') ? 
                '<span style="color: green;">✅ Configurada</span>' : 
                '<span style="color: red;">❌ Não configurada</span>' ?>
        </div>
    </div>
    
    <div class="section">
        <h2>🧪 Testar Webhook</h2>
        <p style="margin-bottom: 15px;">
            Clique no botão abaixo para enviar um payload de teste ao webhook.
            Isso simulará o e-flow enviando dados de um novo formulário.
        </p>
        
        <form method="POST">
            <button type="submit" name="testar" class="btn">
                🚀 Enviar Teste
            </button>
        </form>
    </div>
    
    <?php
    // Estatísticas
    $stats = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN acao = 'created' THEN 1 ELSE 0 END) as novos,
            SUM(CASE WHEN acao = 'updated' THEN 1 ELSE 0 END) as atualizados,
            SUM(CASE WHEN acao = 'error' THEN 1 ELSE 0 END) as erros
        FROM webhook_eflow_log
    ")->fetch(PDO::FETCH_ASSOC);
    ?>
    
    <div class="section">
        <h2>📊 Estatísticas</h2>
        
        <div class="stats">
            <div class="stat-card">
                <h3>Total de Webhooks</h3>
                <div class="number"><?= $stats['total'] ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Novos Registros</h3>
                <div class="number"><?= $stats['novos'] ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Atualizações</h3>
                <div class="number"><?= $stats['atualizados'] ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Erros</h3>
                <div class="number" style="color: <?= $stats['erros'] > 0 ? '#d9534f' : '#5cb85c' ?>">
                    <?= $stats['erros'] ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="section">
        <h2>📝 Últimos Webhooks Recebidos</h2>
        
        <?php if (empty($logs)): ?>
        <p>Nenhum webhook recebido ainda.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Processo</th>
                    <th>Tipo Evento</th>
                    <th>Ação</th>
                    <th>Data</th>
                    <th>Detalhes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= $log['id'] ?></td>
                    <td><?= htmlspecialchars($log['numero_processo']) ?></td>
                    <td><?= htmlspecialchars($log['tipo_evento']) ?></td>
                    <td>
                        <span class="badge badge-<?= $log['acao'] === 'error' ? 'error' : 'success' ?>">
                            <?= htmlspecialchars($log['acao']) ?>
                        </span>
                    </td>
                    <td><?= date('d/m/Y H:i:s', strtotime($log['data_recebimento'])) ?></td>
                    <td>
                        <button onclick="verPayload(<?= htmlspecialchars(json_encode($log['payload'])) ?>)" 
                                class="btn" style="padding: 6px 12px; font-size: 12px;">
                            Ver Payload
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>📁 Localização dos Logs</h2>
        <p>Logs detalhados em arquivo:</p>
        <code style="display: block; padding: 10px; background: white; border-radius: 3px;">
            /var/www/html/credenciamento/logs/webhook-eflow.log
        </code>
        
        <p style="margin-top: 15px;">Para visualizar em tempo real:</p>
        <pre>tail -f /var/www/html/credenciamento/logs/webhook-eflow.log</pre>
    </div>
</div>

<!-- Modal para exibir payload -->
<div id="modalPayload" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="background: white; margin: 50px auto; padding: 30px; max-width: 800px; max-height: 80vh; overflow-y: auto; border-radius: 8px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">Payload Completo</h2>
            <button onclick="fecharModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <pre id="payloadContent" style="background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto;"></pre>
    </div>
</div>

<script>
function verPayload(payload) {
    const modal = document.getElementById('modalPayload');
    const content = document.getElementById('payloadContent');
    content.textContent = JSON.stringify(payload, null, 2);
    modal.style.display = 'block';
}

function fecharModal() {
    document.getElementById('modalPayload').style.display = 'none';
}

// Fechar modal ao clicar fora
document.getElementById('modalPayload').addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModal();
    }
});
</script>

</body>
</html>