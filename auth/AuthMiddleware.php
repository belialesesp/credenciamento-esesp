<?php
require_once __DIR__ . '/AcessoCidadaoAuth.php';

class AuthMiddleware
{
    public static function requireAuth()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar se está autenticado
        if (!AcessoCidadaoAuth::isAuthenticated()) {
            $returnUrl = urlencode($_SERVER['REQUEST_URI']);
            header("Location: /login.php?returnUrl=" . $returnUrl);
            exit;
        }
        
        // Verificar expiração da sessão
        if (!AcessoCidadaoAuth::checkSessionExpiry()) {
            header("Location: /login.php?expired=1");
            exit;
        }
    }
    
    public static function requireRole($role)
    {
        self::requireAuth();
        
        $user = AcessoCidadaoAuth::getUser();
        
        if (!isset($user['role']) || $user['role'] !== $role) {
            http_response_code(403);
            die("Acesso negado. Role necessária: {$role}");
        }
    }
}