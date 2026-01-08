<?php
session_start();

$returnUrl = $_GET['returnUrl'] ?? '/';
$_SESSION['return_url'] = $returnUrl;

// Configuração manual (sem depender do .env)
$config = [
    'authority' => 'https://acessocidadao.es.gov.br/is/',
    'client_id' => '12ee5e77-81b3-4b70-872f-bd32f5f8a9cc',
    
    // ✅ URLs fixas e corretas
    'redirect_uri' => 'https://credenciamento.esesp.es.gov.br/callback.php',
    
    'scopes' => ['openid', 'profile', 'email', 'permissoes', 'agentepublico'],
    'authorize_endpoint' => 'https://acessocidadao.es.gov.br/is/connect/authorize'
];

$nonce = bin2hex(random_bytes(16));
$state = bin2hex(random_bytes(16));

$_SESSION['oidc_nonce'] = $nonce;
$_SESSION['oidc_state'] = $state;

$params = [
    'response_type' => 'code id_token',
    'client_id' => $config['client_id'],
    'scope' => implode(' ', $config['scopes']),
    'redirect_uri' => $config['redirect_uri'], // ← URL fixa
    'nonce' => $nonce,
    'state' => $state,
    'response_mode' => 'form_post'
];

$authorizeUrl = $config['authorize_endpoint'] . '?' . http_build_query($params);

// Debug - mostrar a URL que será usada
echo "<h2>Debug Info</h2>";
echo "<p><strong>Redirect URI:</strong> " . htmlspecialchars($config['redirect_uri']) . "</p>";
echo "<p><strong>URL completa:</strong></p>";
echo "<pre>" . htmlspecialchars($authorizeUrl) . "</pre>";
echo "<hr>";
echo "<p>Redirecionando em 3 segundos...</p>";
echo "<a href='" . htmlspecialchars($authorizeUrl) . "'>Ou clique aqui</a>";

// Redirecionar
header("Refresh: 3; url=" . $authorizeUrl);