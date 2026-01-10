<?php
/**
 * find_mysql_socket.php - Encontrar Socket MySQL Real
 */

echo "<h1>🔍 Descobrindo Configuração Real do MySQL</h1>";
echo "<hr>";

// 1. Verificar configuração PHP
echo "<h2>1️⃣ Configuração PHP (php.ini)</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Configuração</th><th>Valor</th></tr>";

$php_configs = [
    'pdo_mysql.default_socket' => ini_get('pdo_mysql.default_socket'),
    'mysqli.default_socket' => ini_get('mysqli.default_socket'),
    'mysql.default_socket' => ini_get('mysql.default_socket'),
];

foreach ($php_configs as $key => $value) {
    $display = $value ? $value : '<em style="color: #999;">(não definido)</em>';
    echo "<tr><td><strong>$key</strong></td><td>$display</td></tr>";
}
echo "</table>";

// 2. Procurar arquivos socket comuns
echo "<h2>2️⃣ Procurar Arquivos Socket</h2>";
$possible_sockets = [
    '/var/run/mysqld/mysqld.sock',
    '/tmp/mysql.sock',
    '/var/lib/mysql/mysql.sock',
    '/var/mysql/mysql.sock',
    '/opt/lampp/var/mysql/mysql.sock', // XAMPP
    '/Applications/MAMP/tmp/mysql/mysql.sock', // MAMP
];

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Caminho</th><th>Existe?</th><th>Permissões</th></tr>";

$found_socket = null;
foreach ($possible_sockets as $socket) {
    $exists = file_exists($socket);
    $perms = $exists ? substr(sprintf('%o', fileperms($socket)), -4) : 'N/A';
    $status = $exists ? '✅ SIM' : '❌ Não';
    
    echo "<tr>";
    echo "<td><code>$socket</code></td>";
    echo "<td>$status</td>";
    echo "<td>$perms</td>";
    echo "</tr>";
    
    if ($exists && !$found_socket) {
        $found_socket = $socket;
    }
}
echo "</table>";

// 3. Testar com socket encontrado
if ($found_socket) {
    echo "<div style='background: #d4edda; padding: 15px; margin: 20px 0; border-left: 5px solid #28a745;'>";
    echo "<h3>✅ Socket Encontrado!</h3>";
    echo "<p><strong>Caminho:</strong> <code>$found_socket</code></p>";
    echo "<p><strong>Testando conexão com este socket...</strong></p>";
    
    $databases = ['credenciamento_esesp', 'esesp_pesquisas'];
    
    foreach ($databases as $db) {
        echo "<h4>Database: $db</h4>";
        try {
            $dsn = "mysql:unix_socket=$found_socket;dbname=$db;charset=utf8mb4";
            $pdo = new PDO($dsn, 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            
            echo "<div style='background: #d1f2eb; padding: 10px; margin: 10px 0;'>";
            echo "✅ <strong>CONECTOU com sucesso!</strong><br>";
            
            // Testar query
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "Total de tabelas: " . count($tables) . "<br>";
            
            echo "<br><strong>📝 USE ESTA CONFIGURAÇÃO:</strong><br>";
            echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #ddd;'>";
            echo "// config.php\n";
            echo "define('DB_SOCKET', '$found_socket');\n";
            echo "define('DB_NAME', '$db');\n";
            echo "define('DB_USER', 'root');\n";
            echo "define('DB_PASS', '');\n";
            echo "define('DB_CHARSET', 'utf8mb4');\n\n";
            echo "// db.php - linha ~20, mude o DSN para:\n";
            echo "\$dsn = \"mysql:unix_socket=\" . DB_SOCKET . \";dbname=\" . DB_NAME . \";charset=\" . DB_CHARSET;\n";
            echo "</pre>";
            echo "</div>";
            
        } catch (PDOException $e) {
            echo "<div style='background: #f8d7da; padding: 10px; margin: 10px 0;'>";
            echo "❌ Falhou: " . $e->getMessage();
            echo "</div>";
        }
    }
    
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; padding: 15px; margin: 20px 0; border-left: 5px solid #ffc107;'>";
    echo "<h3>⚠️ Nenhum socket encontrado</h3>";
    echo "<p>Isso significa que:</p>";
    echo "<ol>";
    echo "<li>MySQL pode estar configurado para aceitar apenas conexões TCP</li>";
    echo "<li>Socket está em localização não padrão</li>";
    echo "<li>MySQL não está rodando</li>";
    echo "</ol>";
    echo "</div>";
}

// 4. Verificar se MySQL está rodando
echo "<h2>3️⃣ Verificar Processos MySQL</h2>";
echo "<p>Tentando detectar processos MySQL rodando...</p>";

$commands = [
    'ps aux | grep mysql',
    'netstat -tlnp | grep mysql',
    'ss -tlnp | grep mysql',
];

echo "<div style='background: #e7f3ff; padding: 15px; margin: 10px 0;'>";
echo "<p><strong>⚠️ Estes comandos precisam ser executados via SSH:</strong></p>";
echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #ddd;'>";
foreach ($commands as $cmd) {
    echo "$cmd\n";
}
echo "</pre>";
echo "<p>Execute-os e me envie o resultado!</p>";
echo "</div>";

// 5. Encontrar php.ini
echo "<h2>4️⃣ Localização do php.ini</h2>";
$php_ini = php_ini_loaded_file();
echo "<p><strong>Arquivo:</strong> <code>$php_ini</code></p>";

if ($php_ini) {
    echo "<div style='background: #e7f3ff; padding: 15px;'>";
    echo "<p><strong>Para configurar o socket correto:</strong></p>";
    echo "<ol>";
    echo "<li>Edite o arquivo: <code>$php_ini</code></li>";
    echo "<li>Encontre e modifique:</li>";
    echo "</ol>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #ddd;'>";
    if ($found_socket) {
        echo "pdo_mysql.default_socket = $found_socket\n";
        echo "mysqli.default_socket = $found_socket\n";
    } else {
        echo "pdo_mysql.default_socket = /caminho/para/mysql.sock\n";
        echo "mysqli.default_socket = /caminho/para/mysql.sock\n";
    }
    echo "</pre>";
    echo "<li>Reinicie o servidor web: <code>service apache2 restart</code></li>";
    echo "</div>";
}

// 6. Testar seu outro app
echo "<h2>5️⃣ Mistério: Como Seu Outro App Funciona?</h2>";
echo "<div style='background: #fff3cd; padding: 15px;'>";
echo "<p><strong>Hipóteses:</strong></p>";
echo "<ol>";
echo "<li><strong>Usa conexão mysqli (não PDO):</strong> mysqli pode ter configuração diferente</li>";
echo "<li><strong>Socket configurado no php.ini:</strong> Mas só funciona para alguns bancos?</li>";
echo "<li><strong>Arquivo .htaccess:</strong> Define variáveis de ambiente</li>";
echo "<li><strong>Usa outro método:</strong> Pode ter wrapper ou proxy</li>";
echo "</ol>";

echo "<p><strong>🔍 Para descobrir, me diga:</strong></p>";
echo "<ul>";
echo "<li>Qual o nome do arquivo principal do seu outro app? (ex: index.php)</li>";
echo "<li>Ele usa PDO ou mysqli?</li>";
echo "<li>Posso ver o código completo da conexão?</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<h2>🎯 Resumo e Próximos Passos</h2>";

if ($found_socket) {
    echo "<div style='background: #d4edda; padding: 15px;'>";
    echo "<h3>✅ Solução Encontrada!</h3>";
    echo "<p>Use o socket: <code>$found_socket</code></p>";
    echo "<p><strong>Passos:</strong></p>";
    echo "<ol>";
    echo "<li>Adicione no config.php: <code>define('DB_SOCKET', '$found_socket');</code></li>";
    echo "<li>Modifique db.php para usar socket em vez de host</li>";
    echo "<li>Ou configure php.ini com este socket e reinicie Apache</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px;'>";
    echo "<h3>❌ Socket Não Encontrado</h3>";
    echo "<p><strong>Ações necessárias:</strong></p>";
    echo "<ol>";
    echo "<li>Execute via SSH: <code>mysql_config --socket</code></li>";
    echo "<li>Ou: <code>mysqladmin variables | grep socket</code></li>";
    echo "<li>Verifique se MySQL está rodando: <code>service mysql status</code></li>";
    echo "</ol>";
    echo "</div>";
}
?>
