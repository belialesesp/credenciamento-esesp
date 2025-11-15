<?php
return [
    // Configurações do Acesso Cidadão
    'authority' => 'https://acessocidadao.es.gov.br/is/',
    'client_id' => getenv('AC_CLIENT_ID'), // Definir no .env
    'client_secret' => getenv('AC_CLIENT_SECRET'), // Definir no .env
    
    // URLs da sua aplicação
    'redirect_uri' => getenv('APP_URL') . '/auth/callback',
    'post_logout_redirect_uri' => getenv('APP_URL'),
    
    // Scopes solicitados
    'scopes' => [
        'openid',
        'profile',
        'email',
        'permissoes',
        'agentepublico'
    ],
    
    // Response type (Hybrid Flow)
    'response_type' => ['code', 'id_token'],
    'response_mode' => 'form_post',
    
    // Configurações de sessão
    'session' => [
        'cookie_name' => 'CredenciamentoCookie',
        'expire_minutes' => 60,
        'save_tokens' => true
    ],
    
    // Endpoints
    'endpoints' => [
        'authorize' => 'https://acessocidadao.es.gov.br/is/connect/authorize',
        'token' => 'https://acessocidadao.es.gov.br/is/connect/token',
        'userinfo' => 'https://acessocidadao.es.gov.br/is/connect/userinfo',
        'logout' => 'https://acessocidadao.es.gov.br/is/connect/endsession',
        'well_known' => 'https://acessocidadao.es.gov.br/is/.well-known/openid-configuration'
    ]
];