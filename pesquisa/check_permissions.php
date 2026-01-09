<?php
/**
 * check_permissions.php - Verificar Permissões e Configuração MySQL
 * Coloque na raiz do projeto e acesse via navegador
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.success { background: #d4edda; padding: 15px; margin: 10px 0; border-left: 5px solid #28a745; }
.error { background: #f8d7da; padding: 15px; margin: 10px 0; border-left: 5px solid #dc3545; }
.warning { background: #fff3cd; padding: 15px; margin: 10px 0; border-left: 5px solid #ffc107; }
.info { background: #d1ecf1; padding: 15px; margin: 10px 0; border-left: 5px solid #17a2b8; }
pre { background: #f8f9fa; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #f0f0f0; }
</style>";

echo "<h1>🔍 Diagnóstico Completo de Permissões MySQL</h1>";
echo "<p>Verificando configuração e permissões do banco de dados...</p>";
echo "<hr>";

// Configurações a testar
$configs = [
    ['host' => 'localhost', 'db' => 'credenciamento_esesp', 'label' => 'Banco FUNCIONANDO (credenciamento)'],
    ['host' => 'localhost', 'db' => 'esesp_pesquisas', 'label' => 'Banco PROBLEMA (esesp_pesquisas)'],
    ['host' => '127.0.0.1', 'db' => 'esesp_pesquisas', 'label' => 'Banco problema com IP'],
];

foreach ($configs as $config) {
    echo "<h2>📊 Testando: {$config['label']}</h2>";
    echo "<p>Host: <code>{$config['host']}</code> | Database: <code>{$config['db']}</code></p>";
    
    try {
        // Tentar conectar
        $pdo = new PDO(
            "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4",
            'root',
            '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 3
            ]
        );
        
        echo "<div class='success'>";
        echo "<strong>✅ CONEXÃO ESTABELECIDA!</strong><br><br>";
        
        // 1. Verificar tabelas
        echo "<strong>1️⃣ Tabelas no banco:</strong><br>";
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "Total: " . count($tables) . " tabelas<br>";
        if (count($tables) > 0) {
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>$table</li>";
            }
            echo "</ul>";
        }
        
        // 2. Verificar permissões do usuário atual
        echo "<br><strong>2️⃣ Permissões do usuário 'root':</strong><br>";
        try {
            $grants = $pdo->query("SHOW GRANTS FOR CURRENT_USER()")->fetchAll(PDO::FETCH_COLUMN);
            echo "<ul>";
            foreach ($grants as $grant) {
                echo "<li><code>" . htmlspecialchars($grant) . "</code></li>";
            }
            echo "</ul>";
        } catch (PDOException $e) {
            echo "⚠️ Não foi possível verificar grants: " . $e->getMessage() . "<br>";
        }
        
        // 3. Verificar variáveis de conexão
        echo "<br><strong>3️⃣ Variáveis de conexão MySQL:</strong><br>";
        $vars = [
            'socket' => "SHOW VARIABLES LIKE 'socket'",
            'port' => "SHOW VARIABLES LIKE 'port'",
            'bind_address' => "SHOW VARIABLES LIKE 'bind_address'",
            'datadir' => "SHOW VARIABLES LIKE 'datadir'",
        ];
        
        echo "<table>";
        echo "<tr><th>Variável</th><th>Valor</th></tr>";
        foreach ($vars as $name => $query) {
            try {
                $result = $pdo->query($query)->fetch();
                echo "<tr><td><strong>$name</strong></td><td><code>{$result['Value']}</code></td></tr>";
            } catch (PDOException $e) {
                echo "<tr><td><strong>$name</strong></td><td>Erro ao consultar</td></tr>";
            }
        }
        echo "</table>";
        
        // 4. Testar query simples
        echo "<br><strong>4️⃣ Teste de Query:</strong><br>";
        if ($config['db'] === 'esesp_pesquisas') {
            try {
                $count = $pdo->query("SELECT COUNT(*) as count FROM courses")->fetch();
                echo "✅ Query funcionou! Total de cursos: {$count['count']}<br>";
            } catch (PDOException $e) {
                echo "❌ Erro na query: " . $e->getMessage() . "<br>";
            }
        }
        
        // 5. Informações da conexão
        echo "<br><strong>5️⃣ Informações da Conexão:</strong><br>";
        echo "DSN usado: <code>mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4</code><br>";
        echo "Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "<br>";
        echo "Server info: " . $pdo->getAttribute(PDO::ATTR_SERVER_INFO) . "<br>";
        echo "Connection status: " . $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "<br>";
        
        echo "</div>";
        
    } catch (PDOException $e) {
        echo "<div class='error'>";
        echo "<strong>❌ FALHA NA CONEXÃO</strong><br><br>";
        echo "<strong>Erro:</strong> " . $e->getMessage() . "<br>";
        echo "<strong>Código:</strong> " . $e->getCode() . "<br>";
        
        // Analisar o erro
        echo "<br><strong>📋 Análise do Erro:</strong><br>";
        
        if (strpos($e->getMessage(), 'No such file or directory') !== false) {
            echo "<div class='warning'>";
            echo "<p>🔍 <strong>Problema:</strong> Socket file não encontrado</p>";
            echo "<p><strong>Isso significa:</strong></p>";
            echo "<ul>";
            echo "<li>O MySQL está configurado para usar socket Unix</li>";
            echo "<li>Mas o arquivo de socket não existe ou está em local diferente</li>";
            echo "<li>Ou o PHP não tem permissão para acessar o socket</li>";
            echo "</ul>";
            echo "<p><strong>Soluções possíveis:</strong></p>";
            echo "<ol>";
            echo "<li>Use IP direto: <code>define('DB_HOST', '127.0.0.1');</code></li>";
            echo "<li>Especifique o socket: <code>define('DB_HOST', 'localhost:/var/run/mysqld/mysqld.sock');</code></li>";
            echo "<li>Configure o PHP para encontrar o socket correto</li>";
            echo "</ol>";
            echo "</div>";
        }
        
        if (strpos($e->getMessage(), 'Connection refused') !== false) {
            echo "<div class='warning'>";
            echo "<p>🔍 <strong>Problema:</strong> Conexão recusada</p>";
            echo "<p><strong>Isso significa:</strong></p>";
            echo "<ul>";
            echo "<li>MySQL não está escutando nesse IP/porta</li>";
            echo "<li>Firewall bloqueando</li>";
            echo "<li>MySQL configurado para não aceitar conexões TCP</li>";
            echo "</ul>";
            echo "</div>";
        }
        
        if (strpos($e->getMessage(), 'Access denied') !== false) {
            echo "<div class='warning'>";
            echo "<p>🔍 <strong>Problema:</strong> Acesso negado</p>";
            echo "<p><strong>Isso significa:</strong></p>";
            echo "<ul>";
            echo "<li>Usuário ou senha incorretos</li>";
            echo "<li>Usuário não tem permissão nesse banco</li>";
            echo "<li>Host de conexão não permitido</li>";
            echo "</ul>";
            echo "</div>";
        }
        
        echo "</div>";
    }
    
    echo "<hr>";
}

// Comparação final
echo "<h2>🔬 Análise Comparativa</h2>";
echo "<div class='info'>";
echo "<p><strong>Se o banco credenciamento_esesp funcionou mas esesp_pesquisas não:</strong></p>";
echo "<ol>";
echo "<li>Ambos estão no mesmo MySQL (mesma instância)</li>";
echo "<li>O problema é específico do banco esesp_pesquisas</li>";
echo "<li>Pode ser:</li>";
echo "<ul>";
echo "<li>Permissões diferentes entre os bancos</li>";
echo "<li>Configuração de socket diferente</li>";
echo "<li>Cache de DNS/conexões do PHP</li>";
echo "</ul>";
echo "</ol>";
echo "</div>";

// Solução recomendada
echo "<h2>💡 Solução Recomendada</h2>";
echo "<div class='success'>";
echo "<p>Baseado nos testes acima, use a configuração que funcionou.</p>";
echo "<p><strong>Se credenciamento_esesp funcionou com 'localhost':</strong></p>";
echo "<pre>";
echo "// config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'esesp_pesquisas');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
</pre>";

echo "<p><strong>Se apenas 127.0.0.1 funcionou:</strong></p>";
echo "<pre>";
echo "// config.php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'esesp_pesquisas');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
</pre>";
echo "</div>";

// Informações do sistema
echo "<h2>ℹ️ Informações do Sistema</h2>";
echo "<table>";
echo "<tr><th>Item</th><th>Valor</th></tr>";
echo "<tr><td>PHP Version</td><td>" . phpversion() . "</td></tr>";
echo "<tr><td>PDO MySQL</td><td>" . (extension_loaded('pdo_mysql') ? '✅ Instalado' : '❌ Não instalado') . "</td></tr>";
echo "<tr><td>Sistema Operacional</td><td>" . php_uname() . "</td></tr>";
echo "<tr><td>PDO Drivers</td><td>" . implode(', ', PDO::getAvailableDrivers()) . "</td></tr>";
echo "<tr><td>MySQL Default Socket (php.ini)</td><td>" . ini_get('pdo_mysql.default_socket') . "</td></tr>";
echo "<tr><td>MySQL Default Socket (mysqli)</td><td>" . ini_get('mysqli.default_socket') . "</td></tr>";
echo "</table>";

echo "<hr>";
echo "<p style='text-align: center; color: #666;'>Script executado em: " . date('Y-m-d H:i:s') . "</p>";
?>
