<?php
/**
 * includes/config.php - Configurações do Sistema
 */

// Configurações do Banco de Dados MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'esesp_pesquisas');
define('DB_USER', 'root');  // MUDAR para seu usuário MySQL
define('DB_PASS', 'ead@GOV2015');      // MUDAR para sua senha MySQL
define('DB_CHARSET', 'utf8mb4');

// Configurações do Site
define('SITE_NAME', 'Sistema de Pesquisas ESESP');
define('SITE_URL', 'https://credenciamento.esesp.es.gov.br/pesquisa');  // MUDAR para seu domínio
define('ADMIN_EMAIL', 'admin@esesp.es.gov.br');

// Configurações de Sessão
define('SESSION_LIFETIME', 7200); // 2 horas
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

// Configurações de Upload e Output
define('UPLOAD_DIR', __DIR__ . '/../output/diagrams/');
define('UPLOAD_URL', SITE_URL . '/output/diagrams/');

// Configuração Python (para geração de diagramas)
define('PYTHON_PATH', '/usr/bin/python3');  // ou 'python3' se estiver no PATH
define('PYTHON_SCRIPTS', __DIR__ . '/../python/');

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de Erro (PRODUÇÃO: mudar para false)
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php-errors.log');
}

// Configurações de Segurança
define('PASSWORD_MIN_LENGTH', 6);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutos

// Iniciar sessão se ainda não iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurar fuso horário da sessão
if (!isset($_SESSION['timezone'])) {
    $_SESSION['timezone'] = 'America/Sao_Paulo';
}
