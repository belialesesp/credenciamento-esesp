<?php
/**
 * Callback do Acesso Cidadão
 * Este arquivo recebe o retorno após a autenticação
 */

// Iniciar sessão
session_start();

// Verificar se recebeu os parâmetros esperados
if (!isset($_POST['code']) && !isset($_GET['code'])) {
    die("Erro: Nenhum código de autenticação recebido.");
}

// O código pode vir via POST (form_post) ou GET
$code = $_POST['code'] ?? $_GET['code'] ?? null;
$state = $_POST['state'] ?? $_GET['state'] ?? null;

// Verificar se recebeu erro
if (isset($_GET['error']) || isset($_POST['error'])) {
    $error = $_GET['error'] ?? $_POST['error'];
    $errorDescription = $_GET['error_description'] ?? $_POST['error_description'] ?? 'Erro desconhecido';
    
    echo "<h1>Erro na Autenticação</h1>";
    echo "<p><strong>Erro:</strong> " . htmlspecialchars($error) . "</p>";
    echo "<p><strong>Descrição:</strong> " . htmlspecialchars($errorDescription) . "</p>";
    echo "<a href='/login.php'>Tentar novamente</a>";
    exit;
}

// Salvar o code na sessão temporariamente
$_SESSION['auth_code'] = $code;
$_SESSION['auth_state'] = $state;

// Redirecionar para o processador de login que finaliza a autenticação
header("Location: /process-login.php");
exit;