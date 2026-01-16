<?php
/**
 * Arquivo de inicialização do sistema
 * Agora usa Acesso Cidadão para autenticação
 */

// Iniciar sessão se ainda não iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir autoloader do Composer (se usar)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Incluir configuração
require_once __DIR__ . '/config/database.php';

// Incluir classes de autenticação nova
require_once __DIR__ . '/auth/AcessoCidadaoAuth.php';
require_once __DIR__ . '/auth/AuthMiddleware.php';

// Definir constantes úteis
define('BASE_URL', 'https://credenciamento.esesp.es.gov.br');
define('SITE_NAME', 'Sistema de Credenciamento - ESESP');

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Função helper para verificar se está logado
function isLoggedIn() {
    return AcessoCidadaoAuth::isAuthenticated();
}

// Função helper para obter usuário atual
function getCurrentUser() {
    return AcessoCidadaoAuth::getUser();
}