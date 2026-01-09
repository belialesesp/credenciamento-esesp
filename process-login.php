<?php
/**
 * Processa o código de autenticação e obtém os tokens
 */


session_start();

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
    // Configuração (usando valores fixos por segurança)
    $config = [
        'client_id' => '12ee5e77-81b3-4b70-872f-bd32f5f8a9cc',
        'client_secret' => 'ZF3*Ort@p$MDONxabFjTB8Cn1Owg2X', // ← Coloque o CLIENT_SECRET aqui!
        'redirect_uri' => 'https://credenciamento.esesp.es.gov.br/callback.php',
        'token_endpoint' => 'https://acessocidadao.es.gov.br/is/connect/token',
        'userinfo_endpoint' => 'https://acessocidadao.es.gov.br/is/connect/userinfo'
    ];
    
   
    
    // Trocar o code por tokens
    $tokenData = exchangeCodeForTokens($code, $config);
    
    
    // Obter informações do usuário
    $userInfo = getUserInfo($tokenData['access_token'], $config);
    
    
    // Salvar na sessão
    $user = [
        'id' => $userInfo['subNovo'] ?? $userInfo['sub'],
        'cpf' => $userInfo['sub'] ?? $userInfo['cpf'] ?? null,
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
    
    
    // Redirecionar após 3 segundos
    header("Refresh: 3; url=" . $returnUrl);
    
} catch (Exception $e) {
    echo "<h1>❌ Erro ao Processar Login</h1>";
    echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='/login.php'>Tentar novamente</a></p>";
    
    // Log do erro
    error_log("Erro no process-login: " . $e->getMessage());
}

/**
 * Troca o código de autorização por tokens
 */
function exchangeCodeForTokens($code, $config) {
    // Preparar requisição
    $tokenUrl = $config['token_endpoint'];
    
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
        throw new Exception("Erro ao obter token: " . $response);
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
    $userinfoUrl = $config['userinfo_endpoint'];
    
    $ch = curl_init($userinfoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("Erro ao obter informações do usuário: " . $response);
    }
    
    return json_decode($response, true);
}