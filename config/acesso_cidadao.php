<?php
// Carregar variáveis de ambiente
require_once __DIR__ . '/load_env.php';

return [
    // Configurações do Acesso Cidadão
    'authority' => 'https://acessocidadao.es.gov.br/is/',
    'client_id' => $_ENV['AC_CLIENT_ID'], // 12ee5e77-81b3-4b70-872f-bd32f5f8a9cc
    'client_secret' => $_ENV['AC_CLIENT_SECRET'], // Obter do painel
    
    // URLs da sua aplicação (baseado no APP_URL do .env)
    'redirect_uri' => $_ENV['APP_URL'] . '/callback.php',
    'post_logout_redirect_uri' => $_ENV['APP_URL'],
    
    // Scopes solicitados
    'scopes' => [
        'openid',
        'profile',
        'email',
        'permissoes',
        'agentepublico'
    ],
    
    // Response type (Hybrid Flow) - conforme a imagem mostra
    'response_type' => ['code', 'id_token'],
    'response_mode' => 'form_post',
    
    // Configurações de sessão
    'session' => [
        'cookie_name' => 'CredenciamentoESESPCookie',
        'expire_minutes' => 60,
        'save_tokens' => true
    ],
    
    // Endpoints do Acesso Cidadão ES
    'endpoints' => [
        'authorize' => 'https://acessocidadao.es.gov.br/is/connect/authorize',
        'token' => 'https://acessocidadao.es.gov.br/is/connect/token',
        'userinfo' => 'https://acessocidadao.es.gov.br/is/connect/userinfo',
        'logout' => 'https://acessocidadao.es.gov.br/is/connect/endsession',
        'well_known' => 'https://acessocidadao.es.gov.br/is/.well-known/openid-configuration'
    ]
];