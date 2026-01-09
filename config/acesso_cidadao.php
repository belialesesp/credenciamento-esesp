<?php
return [
    'authority' => 'https://acessocidadao.es.gov.br/is/',
    'client_id' => '12ee5e77-81b3-4b70-872f-bd32f5f8a9cc',
    'client_secret' => $_ENV['AC_CLIENT_SECRET'] ?? 'seu_client_secret',
    
    'redirect_uri' => 'https://credenciamento.esesp.es.gov.br/callback.php',
    'post_logout_redirect_uri' => 'https://credenciamento.esesp.es.gov.br',
    
    // ✅ SCOPES CORRETOS (removido "permissoes")
    'scopes' => [
        'openid',
        'profile',
        'email',
        'agentepublico'
    ],
    
    'response_type' => ['code', 'id_token'],
    'response_mode' => 'form_post',
    
    'session' => [
        'cookie_name' => 'CredenciamentoESESPCookie',
        'expire_minutes' => 60,
        'save_tokens' => true
    ],
    
    'endpoints' => [
        'authorize' => 'https://acessocidadao.es.gov.br/is/connect/authorize',
        'token' => 'https://acessocidadao.es.gov.br/is/connect/token',
        'userinfo' => 'https://acessocidadao.es.gov.br/is/connect/userinfo',
        'logout' => 'https://acessocidadao.es.gov.br/is/connect/endsession',
        'well_known' => 'https://acessocidadao.es.gov.br/is/.well-known/openid-configuration'
    ]
];