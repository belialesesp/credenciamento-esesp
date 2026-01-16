<?php


// Database configuration dev
$host = 'localhost';
$username = 'root';
$password = '';

// Determine which database to use based on the current module
function getDatabaseConnection($dbname) {
    global $host, $username, $password;
    
    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch(PDOException $e) {
        // More detailed error message
        die("Connection failed to database '$dbname': " . $e->getMessage() . 
            "<br><br>Host: $host<br>Username: $username<br>Database: $dbname");
    }
}

// Detect which module we're in
$uri = $_SERVER['REQUEST_URI'];

if (strpos($uri, '/pesquisa/') !== false) {
    $pdo = getDatabaseConnection('esesp_pesquisas');
} elseif (strpos($uri, '/credenciamento/') !== false) {
    $pdo = getDatabaseConnection('credenciamento_esesp');
} else {
    // Default database (root level)
    $pdo = getDatabaseConnection('credenciamento_esesp');
}
?>