<?php
session_start();
require_once __DIR__ . '/auth/AcessoCidadaoAuth.php';

$returnUrl = $_GET['returnUrl'] ?? '/index.php';
$expired = $_GET['expired'] ?? false;

if ($expired) {
    echo "<p style='color: red;'>Sua sessão expirou. Faça login novamente.</p>";
}

try {
    $auth = new AcessoCidadaoAuth();
    $user = $auth->login($returnUrl);
    
    // Se chegou aqui, o login foi bem-sucedido
    // O redirect acontece dentro do método login()
    
} catch (Exception $e) {
    echo "<h1>Erro no Login</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<a href='/'>Voltar</a>";
}