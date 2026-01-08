<?php
/**
 * Processa o código de autenticação e obtém os tokens
 */

session_start();

require_once __DIR__ . '/auth/AcessoCidadaoAuth.php';

// Verificar se tem o código de autenticação
if (!isset($_SESSION['auth_code'])) {
    die("Erro: Código de autenticação não encontrado. <a href='/login.php'>Fazer login</a>");
}

$code = $_SESSION['auth_code'];
$state = $_SESSION['auth_state'] ?? null;

// Limpar da sessão
unset($_SESSION['auth_code']);
unset($_SESSION['auth_state']);

try {
    // Carregar configuração
    $config = require __DIR__ . '/config/acesso_cidadao.php';
    
    // Trocar o code por tokens
    $tokenData = exchangeCodeForTokens($code, $config);
    
    // Obter informações do usuário
    $userInfo = getUserInfo($tokenData['access_token'], $config);
    
    // Salvar na sessão
    $user = [
        'id' => $userInfo['subNovo'] ?? $userInfo['sub'],
        'cpf' => $userInfo['sub'] ?? null,
        'nome' => $userInfo['nome'] ?? $userInfo['name'] ?? 'Usuário',
        'apelido' => $userInfo['apelido'] ?? $userInfo['nome'] ?? $userInfo['name'],
        'email' => $userInfo['email'] ?? null,
        'verificada' => $userInfo['verificada'] ?? false,
        'role' => $userInfo['role'] ?? null,
        'authenticated_at' => time(),
    ];
    
    $_SESSION['user'] = $user;
    $_SESSION['id_token'] = $tokenData['id_token'];
    $_SESSION['access_token'] = $tokenData['access_token'];
    $_SESSION['last_activity'] = time();
    
    // Redirecionar para onde o usuário queria ir
    $returnUrl = $_SESSION['return_url'] ?? '/index.php';
    unset($_SESSION['return_url']);
    
    header("Location: " . $returnUrl);
    exit;
    
} catch (Exception $e) {
    echo "<h1>Erro ao Processar Login</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<a href='/login.php'>Tentar novamente</a>";
    
    // Log do erro
    error_log("Erro no process-login: " . $e->getMessage());
}

/**
 * Troca o código de autorização por tokens
 */
function exchangeCodeForTokens($code, $config) {
    // Preparar requisição
    $tokenUrl = $config['endpoints']['token'];
    
    // Criar Basic Auth (CLIENT_ID:CLIENT_SECRET em base64)
    $auth = base64_encode($config['client_id'] . ':' . $config['client_secret']);
    
    // Dados do POST
    $postData = http_build_query([
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $config['redirect_uri']
    ]);
    
    // Fazer requisição
    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $auth,
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $error = json_decode($response, true);
        throw new Exception("Erro ao obter token: " . ($error['error_description'] ?? $response));
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['access_token'])) {
        throw new Exception("Token de acesso não recebido");
    }
    
    return $data;
}

/**
 * Obtém informações do usuário usando o access_token
 */
function getUserInfo($accessToken, $config) {
    $userinfoUrl = $config['endpoints']['userinfo'];
    
    $ch = curl_init($userinfoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("Erro ao obter informações do usuário");
    }
    
    return json_decode($response, true);
}