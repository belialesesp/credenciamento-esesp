<?php
/**
 * includes/auth.php - Sistema de Autenticação
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Verificar se usuário está autenticado
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Obter usuário logado
 */
function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    $db = Database::getInstance();
    return $db->fetchOne(
        "SELECT id, username, full_name, email FROM admin_users WHERE id = ?",
        [$_SESSION['user_id']]
    );
}

/**
 * Requerer autenticação (redireciona se não autenticado)
 */
function requireAuth() {
    if (!isAuthenticated()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit;
    }
}

/**
 * Fazer login
 */
function login($username, $password) {
    $db = Database::getInstance();
    
    // Verificar tentativas de login
    if (isLoginLocked($username)) {
        return [
            'success' => false,
            'message' => 'Muitas tentativas de login. Tente novamente em 15 minutos.'
        ];
    }
    
    // Buscar usuário
    $user = $db->fetchOne(
        "SELECT * FROM admin_users WHERE username = ? AND is_active = 1",
        [$username]
    );
    
    if (!$user) {
        recordLoginAttempt($username, false);
        return [
            'success' => false,
            'message' => 'Usuário ou senha inválidos.'
        ];
    }
    
    // Verificar senha
    if (!password_verify($password, $user['password_hash'])) {
        recordLoginAttempt($username, false);
        return [
            'success' => false,
            'message' => 'Usuário ou senha inválidos.'
        ];
    }
    
    // Login bem-sucedido
    recordLoginAttempt($username, true);
    
    // Criar sessão
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['last_activity'] = time();
    
    // Atualizar último login
    $db->update(
        'admin_users',
        ['last_login' => date('Y-m-d H:i:s')],
        'id = ?',
        [$user['id']]
    );
    
    // Regenerar session ID (segurança)
    session_regenerate_id(true);
    
    return [
        'success' => true,
        'message' => 'Login realizado com sucesso!',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name']
        ]
    ];
}

/**
 * Fazer logout
 */
function logout() {
    $_SESSION = [];
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

/**
 * Registrar tentativa de login
 */
function recordLoginAttempt($username, $success) {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }
    
    if (!isset($_SESSION['login_attempts'][$username])) {
        $_SESSION['login_attempts'][$username] = [
            'count' => 0,
            'last_attempt' => time()
        ];
    }
    
    if ($success) {
        // Limpar tentativas em caso de sucesso
        unset($_SESSION['login_attempts'][$username]);
    } else {
        // Incrementar contador
        $_SESSION['login_attempts'][$username]['count']++;
        $_SESSION['login_attempts'][$username]['last_attempt'] = time();
    }
}

/**
 * Verificar se login está bloqueado
 */
function isLoginLocked($username) {
    if (!isset($_SESSION['login_attempts'][$username])) {
        return false;
    }
    
    $attempts = $_SESSION['login_attempts'][$username];
    
    // Verificar se passou o tempo de bloqueio
    if (time() - $attempts['last_attempt'] > LOGIN_LOCKOUT_TIME) {
        unset($_SESSION['login_attempts'][$username]);
        return false;
    }
    
    // Verificar número de tentativas
    return $attempts['count'] >= MAX_LOGIN_ATTEMPTS;
}

/**
 * Criar hash de senha
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verificar força da senha
 */
function isStrongPassword($password) {
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        return false;
    }
    
    // Verificar se tem pelo menos uma letra e um número
    $hasLetter = preg_match('/[a-zA-Z]/', $password);
    $hasNumber = preg_match('/[0-9]/', $password);
    
    return $hasLetter && $hasNumber;
}

/**
 * Gerar token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Verificar timeout de sessão
 */
function checkSessionTimeout() {
    if (!isAuthenticated()) {
        return;
    }
    
    $lastActivity = $_SESSION['last_activity'] ?? 0;
    $currentTime = time();
    
    if ($currentTime - $lastActivity > SESSION_LIFETIME) {
        logout();
        header('Location: login.php?timeout=1');
        exit;
    }
    
    $_SESSION['last_activity'] = $currentTime;
}

/**
 * Sanitizar entrada do usuário
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

/**
 * Validar email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Verificar timeout em cada request (se autenticado)
checkSessionTimeout();
