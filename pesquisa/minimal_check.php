<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico Mínimo</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f0f0; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; }
        .ok { border-left: 5px solid #28a745; }
        .erro { border-left: 5px solid #dc3545; }
        pre { background: #333; color: #0f0; padding: 10px; }
    </style>
</head>
<body>
    <h1>Diagnóstico MySQL Mínimo</h1>
    
    <?php
    echo "<div class='box'>";
    echo "<h2>1. PDO MySQL disponível?</h2>";
    if (extension_loaded('pdo_mysql')) {
        echo "<p style='color:green'>✅ SIM</p>";
    } else {
        echo "<p style='color:red'>❌ NÃO - Instale: apt install php-mysql</p>";
        die();
    }
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h2>2. Listar TODOS os bancos disponíveis</h2>";
    try {
        $pdo = new PDO("mysql:host=127.0.0.1", "root", "");
        $stmt = $pdo->query("SHOW DATABASES");
        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p style='color:green'>✅ Conectado ao MySQL</p>";
        echo "<p><strong>Bancos encontrados:</strong></p><ul>";
        foreach ($databases as $db) {
            $highlight = ($db === 'esesp_pesquisas') ? ' style="color:blue;font-weight:bold"' : '';
            echo "<li$highlight>$db</li>";
        }
        echo "</ul>";
        
        if (!in_array('esesp_pesquisas', $databases)) {
            echo "<div class='box erro'>";
            echo "<h3>❌ PROBLEMA ENCONTRADO!</h3>";
            echo "<p>O banco <strong>esesp_pesquisas</strong> NÃO EXISTE no MySQL!</p>";
            echo "<h4>Solução:</h4>";
            echo "<p>1. Acesse o phpMyAdmin</p>";
            echo "<p>2. Clique em 'Novo' para criar um banco</p>";
            echo "<p>3. Nome: <code>esesp_pesquisas</code></p>";
            echo "<p>4. Collation: <code>utf8mb4_unicode_ci</code></p>";
            echo "<p>5. Importe o schema SQL se tiver</p>";
            echo "</div>";
        } else {
            echo "<div class='box ok'>";
            echo "<h3>✅ Banco esesp_pesquisas EXISTE!</h3>";
            echo "</div>";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ ERRO: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h2>3. Tentar conectar ao esesp_pesquisas</h2>";
    
    $configs = [
        ['host' => '127.0.0.1', 'pass' => ''],
        ['host' => 'localhost', 'pass' => ''],
    ];
    
    foreach ($configs as $cfg) {
        echo "<h3>Testando: {$cfg['host']} com senha vazia</h3>";
        try {
            $pdo = new PDO(
                "mysql:host={$cfg['host']};dbname=esesp_pesquisas",
                "root",
                $cfg['pass']
            );
            echo "<p style='color:green'>✅ FUNCIONOU!</p>";
            
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "<p>Tabelas: " . count($tables) . "</p>";
            
            echo "<div class='box ok'>";
            echo "<h3>USE ESTA CONFIGURAÇÃO:</h3>";
            echo "<pre>";
            echo "define('DB_HOST', '{$cfg['host']}');\n";
            echo "define('DB_NAME', 'esesp_pesquisas');\n";
            echo "define('DB_USER', 'root');\n";
            echo "define('DB_PASS', '');\n";
            echo "</pre>";
            echo "</div>";
            break;
            
        } catch (PDOException $e) {
            echo "<p style='color:red'>❌ " . $e->getMessage() . "</p>";
        }
    }
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h2>4. Info do Sistema</h2>";
    echo "<p>PHP: " . phpversion() . "</p>";
    echo "<p>Socket: " . ini_get('pdo_mysql.default_socket') . "</p>";
    echo "</div>";
    ?>
    
    <div style="text-align:center;margin-top:20px;">
        <a href="?" style="padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;">🔄 Recarregar</a>
    </div>
</body>
</html>
