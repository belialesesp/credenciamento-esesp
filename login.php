<?php
session_start();

$returnUrl = $_GET['returnUrl'] ?? '/';
$_SESSION['return_url'] = $returnUrl;

// Configuração
$config = [
    'authority' => 'https://acessocidadao.es.gov.br/is/',
    'client_id' => '12ee5e77-81b3-4b70-872f-bd32f5f8a9cc',
    'client_secret' => 'SEU_CLIENT_SECRET_DO_ENV', // Do .env
    'redirect_uri' => 'https://credenciamento.esesp.es.gov.br/callback.php',
    
    // ✅ SCOPES CORRETOS (sem "permissoes")
    'scopes' => ['openid', 'profile', 'email', 'agentepublico'],
    'authorize_endpoint' => 'https://acessocidadao.es.gov.br/is/connect/authorize'
];

$nonce = bin2hex(random_bytes(16));
$state = bin2hex(random_bytes(16));

$_SESSION['oidc_nonce'] = $nonce;
$_SESSION['oidc_state'] = $state;
$_SESSION['ac_client_secret'] = $config['client_secret'];

$params = [
    'response_type' => 'code id_token',
    'client_id' => $config['client_id'],
    'scope' => implode(' ', $config['scopes']), // Agora sem "permissoes"
    'redirect_uri' => $config['redirect_uri'],
    'nonce' => $nonce,
    'state' => $state,
    'response_mode' => 'form_post'
];

$authorizeUrl = $config['authorize_endpoint'] . '?' . http_build_query($params);

// Redirecionar
header("Location: " . $authorizeUrl);
exit;