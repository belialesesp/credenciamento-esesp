<?php
/**
 * test_passwords.php - Testar diferentes senhas MySQL
 * Upload para: /var/www/html/pesquisa/
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Teste de Senhas MySQL</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            text-align: center;
            margin-bottom: 30px;
        }
        .test-result {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 5px solid #ccc;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        .solution {
            background: #e7f3ff;
            border: 2px solid #2196F3;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .highlight {
            background: yellow;
            padding: 2px 5px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Teste de Senhas MySQL</h1>
        
        <?php
        // Senhas para testar
        $passwords_to_test = [
            '' => 'Senha vazia (comum em desenvolvimento)',
            'ead@GOV2015' => 'Senha atual no config.php',
            'root' => 'Senha padrão "root"',
            'password' => 'Senha padrão "password"',
            'esesp' => 'Senha relacionada ao projeto',
        ];
        
        $hosts_to_test = [
            '127.0.0.1' => 'TCP/IP',
            'localhost' => 'Unix Socket'
        ];
        
        $working_config = null;
        
        echo "<h2>🔍 Testando Combinações...</h2>";
        
        foreach ($hosts_to_test as $host => $host_label) {
            echo "<h3>Host: <code>$host</code> ($host_label)</h3>";
            
            foreach ($passwords_to_test as $password => $password_label) {
                $display_pass = empty($password) ? '(vazia)' : str_repeat('*', strlen($password));
                
                echo "<div class='test-result";
                
                try {
                    $pdo = new PDO(
                        "mysql:host=$host;dbname=esesp_pesquisas;charset=utf8mb4",
                        'root',
                        $password,
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_TIMEOUT => 2
                        ]
                    );
                    
                    echo " success'>";
                    echo "<strong>✅ SUCESSO!</strong><br>";
                    echo "Senha: <span class='highlight'>$display_pass</span> - $password_label<br>";
                    echo "Host: <code>$host</code><br>";
                    
                    // Contar tabelas
                    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                    echo "Tabelas no banco: " . count($tables);
                    
                    $working_config = [
                        'host' => $host,
                        'password' => $password,
                        'label' => $password_label
                    ];
                    
                    echo "</div>";
                    
                } catch (PDOException $e) {
                    echo " error'>";
                    echo "<strong>❌ Falhou</strong><br>";
                    echo "Senha: $display_pass - $password_label<br>";
                    echo "Erro: " . htmlspecialchars($e->getMessage());
                    echo "</div>";
                }
            }
            
            echo "<hr>";
        }
        
        // Se encontrou uma configuração que funciona
        if ($working_config) {
            $display_pass = empty($working_config['password']) ? '' : $working_config['password'];
            $display_pass_masked = empty($working_config['password']) ? '(vazia)' : str_repeat('*', strlen($working_config['password']));
            
            echo "<div class='solution'>";
            echo "<h2>🎉 Configuração Correta Encontrada!</h2>";
            echo "<p><strong>Host:</strong> <code>{$working_config['host']}</code></p>";
            echo "<p><strong>Senha:</strong> {$display_pass_masked} - {$working_config['label']}</p>";
            
            echo "<h3>✏️ Atualize seu config.php:</h3>";
           

define('DB_HOST', '{$working_config["host"]}');
define('DB_NAME', 'esesp_pesquisas');
define('DB_USER', 'root');
define('DB_PASS', '$display_pass');  // ← SENHA CORRETA
define('DB_CHARSET', 'utf8mb4');"); ?></pre>";
            
            echo "<h3>📋 Passo a Passo:</h3>";
            echo "<ol>";
            echo "<li>Acesse via SSH ou FTP o arquivo: <code>/var/www/html/pesquisa/php_system/includes/config.php</code></li>";
            echo "<li>Faça backup: <code>cp config.php config.php.backup</code></li>";
            echo "<li>Edite o arquivo e cole o código acima</li>";
            echo "<li>Salve e teste: <a href='public/login.php' target='_blank'>Login</a></li>";
            echo "</ol>";
            
            echo "</div>";
            
        } else {
            echo "<div class='test-result error'>";
            echo "<h2>⚠️ Nenhuma Senha Funcionou</h2>";
            echo "<p>Nenhuma das senhas testadas conseguiu conectar ao banco <code>esesp_pesquisas</code>.</p>";
            
            echo "<h3>Próximos Passos:</h3>";
            echo "<ol>";
            echo "<li><strong>Verifique a senha do credenciamento:</strong><br>";
            echo "<code>cat /var/www/html/credenciamento/config/database.php</code></li>";
            echo "<li><strong>Teste manual no terminal:</strong><br>";
            echo "<code>mysql -u root -p</code></li>";
            echo "<li><strong>Ou redefina a senha do root:</strong><br>";
            echo "<code>sudo mysql<br>ALTER USER 'root'@'localhost' IDENTIFIED BY 'nova_senha';<br>FLUSH PRIVILEGES;</code></li>";
            echo "</ol>";
            
            echo "</div>";
        }
        
        // Informações adicionais
        echo "<hr>";
        echo "<h2>ℹ️ Informações Úteis</h2>";
        echo "<p><strong>Caminho do config:</strong> <code>/var/www/html/pesquisa/php_system/includes/config.php</code></p>";
        echo "<p><strong>Socket MySQL:</strong> <code>/var/run/mysqld/mysqld.sock</code></p>";
        echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
        
        // Verificar credenciamento
        $cred_config = '/var/www/html/credenciamento/config/database.php';
        if (file_exists($cred_config)) {
            echo "<h3>📄 Configuração do Credenciamento:</h3>";
            echo "<pre>" . htmlspecialchars(file_get_contents($cred_config)) . "</pre>";
        }
        ?>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
            <p style="color: #666;">Script executado em: <?php echo date('d/m/Y H:i:s'); ?></p>
            <a href="?" style="display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;">🔄 Testar Novamente</a>
        </div>
    </div>
</body>
</html>
