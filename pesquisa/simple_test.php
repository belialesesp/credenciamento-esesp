<?php
/**
 * simple_test.php - Teste Simples de Conexão
 * Upload para: /var/www/html/pesquisa/
 */

// Mostrar todos os erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Teste Simples</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".box{background:white;padding:20px;margin:10px 0;border-radius:8px;border-left:5px solid #667eea;}";
echo ".success{border-left-color:#28a745;background:#d4edda;}";
echo ".error{border-left-color:#dc3545;background:#f8d7da;}";
echo "pre{background:#2d2d2d;color:#f8f8f2;padding:15px;border-radius:5px;overflow-x:auto;}";
echo "</style></head><body>";

echo "<h1>🔍 Teste Simples de Conexão MySQL</h1>";

// Teste 1: Verificar se PDO está disponível
echo "<div class='box'>";
echo "<h2>1️⃣ Verificando PDO MySQL</h2>";
if (extension_loaded('pdo_mysql')) {
    echo "<p style='color:green;'>✅ PDO MySQL está instalado</p>";
    echo "<p>Drivers disponíveis: " . implode(', ', PDO::getAvailableDrivers()) . "</p>";
} else {
    echo "<p style='color:red;'>❌ PDO MySQL NÃO está instalado</p>";
    echo "<p>Você precisa instalar: <code>sudo apt-get install php-mysql</code></p>";
}
echo "</div>";

// Teste 2: Informações do PHP
echo "<div class='box'>";
echo "<h2>2️⃣ Informações do PHP</h2>";
echo "<p><strong>Versão PHP:</strong> " . phpversion() . "</p>";
echo "<p><strong>Socket MySQL (PDO):</strong> " . (ini_get('pdo_mysql.default_socket') ?: '(padrão)') . "</p>";
echo "<p><strong>Socket MySQL (MySQLi):</strong> " . (ini_get('mysqli.default_socket') ?: '(padrão)') . "</p>";
echo "</div>";

// Teste 3: Testar conexões
$configs = [
    ['host' => '127.0.0.1', 'pass' => '', 'label' => 'IP com senha vazia'],
    ['host' => '127.0.0.1', 'pass' => 'ead@GOV2015', 'label' => 'IP com senha ead@GOV2015'],
    ['host' => 'localhost', 'pass' => '', 'label' => 'localhost com senha vazia'],
    ['host' => 'localhost', 'pass' => 'ead@GOV2015', 'label' => 'localhost com senha ead@GOV2015'],
];

$found_solution = false;

foreach ($configs as $config) {
    echo "<div class='box'>";
    echo "<h2>🔌 Testando: {$config['label']}</h2>";
    echo "<p>Host: <code>{$config['host']}</code></p>";
    
    try {
        $dsn = "mysql:host={$config['host']};dbname=esesp_pesquisas;charset=utf8mb4";
        
        $pdo = new PDO($dsn, 'root', $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3
        ]);
        
        echo "<div class='success'>";
        echo "<h3>✅ CONEXÃO FUNCIONOU!</h3>";
        echo "<p><strong>Esta é a configuração correta!</strong></p>";
        
        // Contar tabelas
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Tabelas no banco: <strong>" . count($tables) . "</strong></p>";
        
        if (count($tables) > 0) {
            echo "<p>Exemplos: " . implode(', ', array_slice($tables, 0, 5)) . "</p>";
        }
        
        echo "<h3>📝 Use esta configuração:</h3>";
        echo "<pre>";
        $pass_display = empty($config['pass']) ? '' : $config['pass'];
        echo htmlspecialchars("<?php\n");
        echo "define('DB_HOST', '{$config['host']}');\n";
        echo "define('DB_NAME', 'esesp_pesquisas');\n";
        echo "define('DB_USER', 'root');\n";
        echo "define('DB_PASS', '$pass_display');\n";
        echo "define('DB_CHARSET', 'utf8mb4');\n";
        echo "?>";
        echo "</pre>";
        echo "</div>";
        
        $found_solution = true;
        
    } catch (PDOException $e) {
        echo "<div class='error'>";
        echo "<h3>❌ Falhou</h3>";
        echo "<p><strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>Código:</strong> " . $e->getCode() . "</p>";
        echo "</div>";
    }
    
    echo "</div>";
}

// Resumo final
if ($found_solution) {
    echo "<div class='box success'>";
    echo "<h2>🎉 Solução Encontrada!</h2>";
    echo "<p>Copie o código acima e cole em: <code>/var/www/html/pesquisa/php_system/includes/config.php</code></p>";
    echo "</div>";
} else {
    echo "<div class='box error'>";
    echo "<h2>⚠️ Nenhuma Configuração Funcionou</h2>";
    echo "<p><strong>Possíveis causas:</strong></p>";
    echo "<ul>";
    echo "<li>Banco 'esesp_pesquisas' não existe</li>";
    echo "<li>Senha do MySQL está incorreta</li>";
    echo "<li>MySQL não está rodando</li>";
    echo "<li>Permissões incorretas</li>";
    echo "</ul>";
    
    echo "<p><strong>Verifique a senha do credenciamento:</strong></p>";
    echo "<pre>cat /var/www/html/credenciamento/config/database.php</pre>";
    
    echo "<p><strong>Ou teste manualmente:</strong></p>";
    echo "<pre>mysql -u root -p\n# Digite a senha quando solicitado</pre>";
    echo "</div>";
}

// Footer
echo "<div style='text-align:center;margin-top:30px;padding:20px;border-top:2px solid #eee;'>";
echo "<p style='color:#666;'>Executado em: " . date('d/m/Y H:i:s') . "</p>";
echo "<a href='?' style='display:inline-block;padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;'>🔄 Recarregar</a>";
echo "</div>";

echo "</body></html>";
?>
