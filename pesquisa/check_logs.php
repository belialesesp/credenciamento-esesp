<?php
/**
 * check_logs.php - Verificar Logs de Erro
 * Upload para: /var/www/html/pesquisa/
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Verificar Logs de Erro</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #667eea; }
        h2 { 
            color: #333; 
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
            margin-top: 30px;
        }
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            max-height: 400px;
            overflow-y: auto;
        }
        .info {
            background: #d1ecf1;
            padding: 15px;
            border-left: 5px solid #17a2b8;
            border-radius: 5px;
            margin: 15px 0;
        }
        .warning {
            background: #fff3cd;
            padding: 15px;
            border-left: 5px solid #ffc107;
            border-radius: 5px;
            margin: 15px 0;
        }
        .error {
            background: #f8d7da;
            padding: 15px;
            border-left: 5px solid #dc3545;
            border-radius: 5px;
            margin: 15px 0;
        }
        .success {
            background: #d4edda;
            padding: 15px;
            border-left: 5px solid #28a745;
            border-radius: 5px;
            margin: 15px 0;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificar Logs de Erro</h1>
        
        <?php
        // Locais comuns de logs
        $log_locations = [
            '/var/log/apache2/error.log',
            '/var/log/nginx/error.log',
            '/var/log/php_errors.log',
            '/var/log/php/error.log',
            ini_get('error_log'),
        ];
        
        echo "<div class='info'>";
        echo "<h3>ℹ️ Configuração PHP</h3>";
        echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
        echo "<p><strong>Error Log:</strong> " . (ini_get('error_log') ?: '(não configurado)') . "</p>";
        echo "<p><strong>Display Errors:</strong> " . (ini_get('display_errors') ? 'On' : 'Off') . "</p>";
        echo "<p><strong>Error Reporting:</strong> " . error_reporting() . "</p>";
        echo "</div>";
        
        // Verificar se o test_passwords.php existe e pode estar causando erro
        $test_file = __DIR__ . '/test_passwords.php';
        if (file_exists($test_file)) {
            echo "<h2>📄 Testando test_passwords.php</h2>";
            echo "<div class='info'>";
            echo "<p>O arquivo existe. Tentando verificar se há erros de sintaxe...</p>";
            
            // Tentar incluir e capturar erro
            ob_start();
            $error = null;
            try {
                include_once($test_file);
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
            $output = ob_get_clean();
            
            if ($error) {
                echo "<div class='error'>";
                echo "<p><strong>❌ Erro encontrado:</strong></p>";
                echo "<pre>" . htmlspecialchars($error) . "</pre>";
                echo "</div>";
            } else {
                echo "<p>✅ Sem erros de sintaxe detectados</p>";
            }
            echo "</div>";
        }
        
        // Tentar ler logs
        echo "<h2>📋 Logs Recentes</h2>";
        
        $found_logs = false;
        
        foreach ($log_locations as $log) {
            if (empty($log)) continue;
            
            if (file_exists($log) && is_readable($log)) {
                $found_logs = true;
                
                echo "<h3>📄 " . basename(dirname($log)) . "/" . basename($log) . "</h3>";
                
                // Ler últimas 50 linhas
                $lines = [];
                $fp = fopen($log, 'r');
                if ($fp) {
                    // Ir para o final do arquivo
                    fseek($fp, -1, SEEK_END);
                    $pos = ftell($fp);
                    $line_count = 0;
                    
                    // Ler de trás para frente
                    while ($pos > 0 && $line_count < 50) {
                        fseek($fp, $pos, SEEK_SET);
                        $char = fgetc($fp);
                        
                        if ($char === "\n" && ftell($fp) > 1) {
                            $line = fgets($fp);
                            if (!empty(trim($line))) {
                                array_unshift($lines, $line);
                                $line_count++;
                            }
                        }
                        $pos--;
                    }
                    fclose($fp);
                    
                    if (!empty($lines)) {
                        echo "<pre>";
                        foreach ($lines as $line) {
                            // Destacar erros do PHP
                            if (stripos($line, 'error') !== false || 
                                stripos($line, 'fatal') !== false ||
                                stripos($line, 'warning') !== false) {
                                echo "<span style='color:#ff6b6b;'>" . htmlspecialchars($line) . "</span>";
                            } else {
                                echo htmlspecialchars($line);
                            }
                        }
                        echo "</pre>";
                    } else {
                        echo "<p>Log vazio ou sem conteúdo recente.</p>";
                    }
                }
            }
        }
        
        if (!$found_logs) {
            echo "<div class='warning'>";
            echo "<p><strong>⚠️ Não foi possível acessar os logs</strong></p>";
            echo "<p>Verifique manualmente com SSH:</p>";
            echo "<pre>";
            echo "# Ver últimas linhas do log do Apache\n";
            echo "sudo tail -50 /var/log/apache2/error.log\n\n";
            echo "# Ver últimas linhas do log do Nginx\n";
            echo "sudo tail -50 /var/log/nginx/error.log\n\n";
            echo "# Ver erros PHP em tempo real\n";
            echo "sudo tail -f /var/log/apache2/error.log\n";
            echo "</pre>";
            echo "</div>";
        }
        
        // Instruções adicionais
        echo "<h2>🛠️ Solução para HTTP 500</h2>";
        echo "<div class='info'>";
        echo "<p><strong>O erro HTTP 500 geralmente é causado por:</strong></p>";
        echo "<ol>";
        echo "<li><strong>Erro de sintaxe PHP</strong> - Verifique os logs acima</li>";
        echo "<li><strong>Permissões incorretas</strong> - Arquivos devem ser legíveis pelo servidor web</li>";
        echo "<li><strong>Diretivas .htaccess inválidas</strong> - Se usar Apache</li>";
        echo "<li><strong>Memória PHP insuficiente</strong> - Aumente em php.ini</li>";
        echo "</ol>";
        
        echo "<p><strong>Comandos úteis via SSH:</strong></p>";
        echo "<pre>";
        echo "# Verificar sintaxe PHP\n";
        echo "php -l /var/www/html/pesquisa/test_passwords.php\n\n";
        echo "# Verificar permissões\n";
        echo "ls -la /var/www/html/pesquisa/\n\n";
        echo "# Corrigir permissões (se necessário)\n";
        echo "sudo chown -R www-data:www-data /var/www/html/pesquisa/\n";
        echo "sudo chmod -R 755 /var/www/html/pesquisa/\n";
        echo "</pre>";
        echo "</div>";
        
        // Alternativa: usar o simple_test.php
        echo "<div class='success'>";
        echo "<h3>✅ Alternativa Mais Simples</h3>";
        echo "<p>Se o test_passwords.php não funcionar, tente o <code>simple_test.php</code> que é mais leve:</p>";
        echo "<p><a href='simple_test.php' style='display:inline-block;padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;'>🔗 Abrir simple_test.php</a></p>";
        echo "</div>";
        
        ?>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
            <p style="color: #666;">Executado em: <?php echo date('d/m/Y H:i:s'); ?></p>
            <a href="?" style="display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;">🔄 Recarregar</a>
        </div>
    </div>
</body>
</html>
