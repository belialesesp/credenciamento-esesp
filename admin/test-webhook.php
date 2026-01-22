<?php
/**
 * Teste - Usando conexão do database.php
 */

session_start();

// Incluir database.php - ele já cria $pdo automaticamente
require_once '/var/www/html/config/database.php';

// Agora $pdo já está disponível!

$resultado = null;
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['testar'])) {
    $payload = [
        'numeroProcesso' => '2025/TESTE-' . time(),
        'cpfInteressado' => '12345678900',
        'nomeInteressado' => 'Teste Webhook',
        'status' => 'Em análise',
        'dataAbertura' => date('Y-m-d H:i:s'),
        'categoria' => 'docente'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://' . $_SERVER['HTTP_HOST'] . '/api/webhook-eflow.php',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-API-Key: esesp_webhook_2025_a3k9m2l5p8'
        ],
        CURLOPT_SSL_VERIFYPEER => false
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

try {
    // $pdo já existe do database.php!
    
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'webhook_eflow_log'")->fetch();
    
    if (!$tableCheck) {
        $logs = [];
        $stats = ['total' => 0, 'novos' => 0, 'atualizados' => 0, 'erros' => 0];
    } else {
        $logs = $pdo->query("SELECT * FROM webhook_eflow_log ORDER BY id DESC LIMIT 20")->fetchAll();
        $stats = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN acao = 'created' THEN 1 ELSE 0 END) as novos,
                SUM(CASE WHEN acao = 'updated' THEN 1 ELSE 0 END) as atualizados,
                SUM(CASE WHEN acao = 'error' THEN 1 ELSE 0 END) as erros
            FROM webhook_eflow_log
        ")->fetch();
    }
} catch (Exception $e) {
    $logs = [];
    $stats = ['total' => 0, 'novos' => 0, 'atualizados' => 0, 'erros' => 0];
    $erro = $erro ?: $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Teste Webhook e-flow</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #003366; margin-bottom: 30px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .btn { background: #0066cc; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #0052a3; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
        .stat-card { background: #f9f9f9; padding: 20px; border-radius: 5px; text-align: center; }
        .stat-card .number { font-size: 32px; font-weight: bold; color: #003366; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #003366; color: white; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔗 Teste Webhook e-flow</h1>
    
    <div class="info">
        ✅ <strong>Usando \$pdo do database.php</strong> (conexão automática)
    </div>
    
    <?php if ($resultado): ?>
    <div class="success">
        ✅ <strong>Sucesso!</strong><br>
        Processo: <?= htmlspecialchars($resultado['processo'] ?? 'N/A') ?><br>
        Ação: <?= htmlspecialchars($resultado['action'] ?? 'N/A') ?>
    </div>
    <?php endif; ?>
    
    <?php if ($erro): ?>
    <div class="error">❌ <?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <button type="submit" name="testar" class="btn">🚀 Enviar Teste</button>
    </form>
    
    <div class="stats">
        <div class="stat-card"><div>Total</div><div class="number"><?= $stats['total'] ?></div></div>
        <div class="stat-card"><div>Novos</div><div class="number"><?= $stats['novos'] ?></div></div>
        <div class="stat-card"><div>Atualizados</div><div class="number"><?= $stats['atualizados'] ?></div></div>
        <div class="stat-card"><div>Erros</div><div class="number"><?= $stats['erros'] ?></div></div>
    </div>
    
    <?php if (!empty($logs)): ?>
    <table>
        <thead><tr><th>ID</th><th>Processo</th><th>Ação</th><th>Data</th></tr></thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= $log['id'] ?></td>
                <td><?= htmlspecialchars($log['numero_processo']) ?></td>
                <td><?= htmlspecialchars($log['acao']) ?></td>
                <td><?= $log['data_recebimento'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</body>
</html>