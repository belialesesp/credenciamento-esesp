<?php
/**
 * diagnose_mysql.php - Diagnóstico Completo MySQL
 * INSTRUÇÕES:
 * 1. Faça upload deste arquivo para: /var/www/html/pesquisa/
 * 2. Acesse via navegador: https://credenciamento.esesp.es.gov.br/pesquisa/diagnose_mysql.php
 * 3. Veja os resultados e siga as instruções
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico MySQL - ESESP Pesquisas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 2.5em; margin-bottom: 10px; }
        .header p { font-size: 1.1em; opacity: 0.9; }
        .content { padding: 30px; }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 10px;
            border-left: 5px solid #667eea;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        .warning {
            background: #fff3cd;
            border-left-color: #ffc107;
        }
        .info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
        }
        .test-result {
            display: flex;
            align-items: center;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .test-result .icon {
            font-size: 2em;
            margin-right: 15px;
            min-width: 40px;
        }
        .test-result .details { flex: 1; }
        .test-result h3 { margin-bottom: 5px; color: #333; }
        .test-result p { color: #666; font-size: 0.95em; }
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 0.9em;
            line-height: 1.5;
        }
        .code-inline {
            background: #f4f4f4;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #d63384;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s;
            margin: 5px;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .solution-box {
            background: #e7f3ff;
            border: 2px solid #2196F3;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .solution-box h3 {
            color: #1976D2;
            margin-bottom: 15px;
        }
        ul { padding-left: 20px; margin: 10px 0; }
        li { margin: 8px 0; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Diagnóstico MySQL</h1>
            <p>Sistema de Pesquisas ESESP - Análise de Conexão com Banco de Dados</p>
        </div>
        
        <div class="content">
            
            <?php
            // Configurações para testar
            $tests = [
                [
                    'label' => 'Banco Funcionando (credenciamento)',
                    'host' => 'localhost',
                    'db' => 'credenciamento_esesp',
                    'user' => 'root',
                    'pass' => ''
                ],
                [
                    'label' => 'Banco Problema com localhost',
                    'host' => 'localhost',
                    'db' => 'esesp_pesquisas',
                    'user' => 'root',
                    'pass' => ''
                ],
                [
                    'label' => 'Banco Problema com 127.0.0.1 (TCP)',
                    'host' => '127.0.0.1',
                    'db' => 'esesp_pesquisas',
                    'user' => 'root',
                    'pass' => ''
                ],
                [
                    'label' => 'Banco Problema com 127.0.0.1:3306',
                    'host' => '127.0.0.1:3306',
                    'db' => 'esesp_pesquisas',
                    'user' => 'root',
                    'pass' => ''
                ]
            ];
            
            $working_config = null;
            
            foreach ($tests as $test) {
                echo "<div class='test-result'>";
                
                try {
                    $dsn = "mysql:host={$test['host']};dbname={$test['db']};charset=utf8mb4";
                    $pdo = new PDO($dsn, $test['user'], $test['pass'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 3
                    ]);
                    
                    echo "<div class='icon'>✅</div>";
                    echo "<div class='details'>";
                    echo "<h3>{$test['label']}</h3>";
                    echo "<p><strong>Sucesso!</strong> Conexão estabelecida com <code class='code-inline'>{$test['host']}</code></p>";
                    echo "<p>Database: <code class='code-inline'>{$test['db']}</code> | ";
                    echo "Driver: <code class='code-inline'>" . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "</code></p>";
                    
                    // Tentar contar tabelas
                    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                    echo "<p>📊 Tabelas encontradas: <strong>" . count($tables) . "</strong></p>";
                    
                    if ($test['db'] === 'esesp_pesquisas') {
                        $working_config = $test;
                    }
                    
                    echo "</div>";
                    
                } catch (PDOException $e) {
                    echo "<div class='icon'>❌</div>";
                    echo "<div class='details'>";
                    echo "<h3>{$test['label']}</h3>";
                    echo "<p><strong>Falha!</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "<p>Host: <code class='code-inline'>{$test['host']}</code> | ";
                    echo "Database: <code class='code-inline'>{$test['db']}</code></p>";
                    echo "</div>";
                }
                
                echo "</div>";
            }
            ?>
            
            <?php if ($working_config): ?>
            
            <div class="solution-box">
                <h3>🎉 Solução Encontrada!</h3>
                <p>A configuração abaixo funcionou perfeitamente:</p>
                
                <pre><?php echo htmlspecialchars("<?php
// php_system/includes/config.php

// Configurações do Banco de Dados MySQL
define('DB_HOST', '{$working_config['host']}');
define('DB_NAME', '{$working_config['db']}');
define('DB_USER', '{$working_config['user']}');
define('DB_PASS', '{$working_config['pass']}');
define('DB_CHARSET', 'utf8mb4');"); ?></pre>
                
                <p><strong>📝 Próximos passos:</strong></p>
                <ol>
                    <li>Edite o arquivo: <code class='code-inline'>/var/www/html/pesquisa/php_system/includes/config.php</code></li>
                    <li>Atualize as constantes conforme o código acima</li>
                    <li>Salve o arquivo</li>
                    <li>Teste o login novamente</li>
                </ol>
            </div>
            
            <?php else: ?>
            
            <div class="section error">
                <h2>⚠️ Nenhuma Configuração Funcionou</h2>
                <p>Não foi possível conectar ao banco <code class='code-inline'>esesp_pesquisas</code> com nenhuma das configurações testadas.</p>
                
                <h3 style="margin-top: 20px;">Possíveis Causas:</h3>
                <ul>
                    <li><strong>Banco não existe:</strong> Verifique se o banco <code class='code-inline'>esesp_pesquisas</code> existe no phpMyAdmin</li>
                    <li><strong>Senha incorreta:</strong> A senha pode estar incorreta</li>
                    <li><strong>Permissões:</strong> O usuário <code class='code-inline'>root</code> pode não ter permissão nesse banco</li>
                    <li><strong>MySQL desligado:</strong> O serviço MySQL pode não estar rodando</li>
                </ul>
                
                <h3 style="margin-top: 20px;">Soluções:</h3>
                <ol>
                    <li><strong>Criar o banco:</strong> No phpMyAdmin, crie o banco <code class='code-inline'>esesp_pesquisas</code> com collation <code class='code-inline'>utf8mb4_unicode_ci</code></li>
                    <li><strong>Verificar senha:</strong> Tente conectar manualmente via phpMyAdmin com essas credenciais</li>
                    <li><strong>Grant permissions:</strong> Execute no SQL:
                        <pre>GRANT ALL PRIVILEGES ON esesp_pesquisas.* TO 'root'@'localhost';
FLUSH PRIVILEGES;</pre>
                    </li>
                </ol>
            </div>
            
            <?php endif; ?>
            
            <div class="section info">
                <h2>ℹ️ Informações do Sistema</h2>
                <table>
                    <tr>
                        <th>Item</th>
                        <th>Valor</th>
                    </tr>
                    <tr>
                        <td><strong>PHP Version</strong></td>
                        <td><?php echo phpversion(); ?></td>
                    </tr>
                    <tr>
                        <td><strong>PDO MySQL</strong></td>
                        <td><?php echo extension_loaded('pdo_mysql') ? '✅ Instalado' : '❌ Não instalado'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>PDO Drivers</strong></td>
                        <td><?php echo implode(', ', PDO::getAvailableDrivers()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>MySQL Socket (PDO)</strong></td>
                        <td><?php echo ini_get('pdo_mysql.default_socket') ?: '(padrão)'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>MySQL Socket (MySQLi)</strong></td>
                        <td><?php echo ini_get('mysqli.default_socket') ?: '(padrão)'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Sistema Operacional</strong></td>
                        <td><?php echo php_uname('s') . ' ' . php_uname('r'); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="section warning">
                <h2>📖 Por que 'localhost' vs '127.0.0.1' faz diferença?</h2>
                
                <h3>🔌 localhost (Unix Socket)</h3>
                <ul>
                    <li>Usa arquivo de socket Unix (ex: <code class='code-inline'>/var/run/mysqld/mysqld.sock</code>)</li>
                    <li>Mais rápido (comunicação direta via filesystem)</li>
                    <li>Pode falhar se:
                        <ul>
                            <li>O arquivo de socket não existe</li>
                            <li>O caminho do socket está incorreto</li>
                            <li>Permissões de arquivo estão erradas</li>
                        </ul>
                    </li>
                </ul>
                
                <h3>🌐 127.0.0.1 (TCP/IP)</h3>
                <ul>
                    <li>Usa conexão de rede TCP/IP na porta 3306</li>
                    <li>Ligeiramente mais lento, mas mais confiável</li>
                    <li>Funciona mesmo se o socket Unix tiver problemas</li>
                    <li>Recomendado para aplicações web</li>
                </ul>
                
                <div style="margin-top: 15px; padding: 15px; background: #fff; border-radius: 6px;">
                    <strong>💡 Recomendação:</strong> Use <code class='code-inline'>127.0.0.1</code> ao invés de <code class='code-inline'>localhost</code> em aplicações web para evitar problemas com sockets.
                </div>
            </div>
            
            <div class="section">
                <h2>🚀 Próximos Passos</h2>
                <ol style="line-height: 2;">
                    <li>Anote qual configuração funcionou acima</li>
                    <li>Edite o arquivo de configuração: <code class='code-inline'>/var/www/html/pesquisa/php_system/includes/config.php</code></li>
                    <li>Atualize o valor de <code class='code-inline'>DB_HOST</code></li>
                    <li>Salve e teste o login</li>
                    <li>Se ainda não funcionar, verifique se o banco existe no phpMyAdmin</li>
                </ol>
            </div>
            
            <div style="text-align: center; padding: 20px; color: #666; border-top: 2px solid #eee; margin-top: 30px;">
                <p>Script executado em: <strong><?php echo date('d/m/Y H:i:s'); ?></strong></p>
                <p style="margin-top: 10px;">
                    <a href="?" class="btn">🔄 Executar Novamente</a>
                    <a href="public/login.php" class="btn">🏠 Voltar ao Login</a>
                </p>
            </div>
            
        </div>
    </div>
</body>
</html>
