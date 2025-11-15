<?php
// Remover ou ajustar o namespace se não estiver usando
// namespace App\Auth;

require_once __DIR__ . '/../vendor/autoload.php';

use Jumbojett\OpenIDConnectClient;
use Exception;

class AcessoCidadaoAuth
{
    private $oidc;
    private $config;
    
    public function __construct()
    {
        // Ajustar o caminho do config conforme sua estrutura
        $this->config = require __DIR__ . '/../config/acesso_cidadao.php';
        
        $this->oidc = new OpenIDConnectClient(
            $this->config['authority'],
            $this->config['client_id'],
            $this->config['client_secret']
        );
        
        $this->configure();
    }
    
    private function configure()
    {
        // Configurar redirect URI
        $this->oidc->setRedirectURL($this->config['redirect_uri']);
        
        // Adicionar scopes
        foreach ($this->config['scopes'] as $scope) {
            $this->oidc->addScope($scope);
        }
        
        // Configurar response types (Hybrid Flow)
        $this->oidc->setResponseTypes($this->config['response_type']);
        
        // Segurança
        $this->oidc->setVerifyHost(true);
        $this->oidc->setVerifyPeer(true);
    }
    
    /**
     * Inicia o processo de login
     */
    public function login($returnUrl = '/')
    {
        try {
            // Salvar URL de retorno na sessão
            $_SESSION['return_url'] = $returnUrl;
            
            // Gerar nonce para segurança
            $nonce = bin2hex(random_bytes(16));
            $_SESSION['oidc_nonce'] = $nonce;
            $this->oidc->setNonce($nonce);
            
            // Redirecionar para Acesso Cidadão
            $this->oidc->authenticate();
            
            // Processar callback (executado após o redirect)
            return $this->processCallback();
            
        } catch (Exception $e) {
            error_log("Erro no login Acesso Cidadão: " . $e->getMessage());
            throw new Exception("Falha na autenticação: " . $e->getMessage());
        }
    }
    
    /**
     * Processa o callback após autenticação
     */
    private function processCallback()
    {
        try {
            // Obter informações do usuário
            $userInfo = $this->oidc->requestUserInfo();
            
            // Extrair claims importantes (similar ao código C#)
            $user = [
                // ID único do usuário (preferir subNovo)
                'id' => $userInfo->subNovo ?? $userInfo->sub,
                
                // Informações básicas
                'cpf' => $userInfo->sub ?? null,
                'nome' => $userInfo->nome ?? $userInfo->name,
                'apelido' => $userInfo->apelido ?? $userInfo->name,
                'email' => $userInfo->email ?? null,
                
                // Status da conta
                'verificada' => $userInfo->verificada ?? false,
                
                // Role/Papel
                'role' => $userInfo->role ?? null,
                
                // Timestamps
                'authenticated_at' => time(),
            ];
            
            // Salvar na sessão
            $_SESSION['user'] = $user;
            $_SESSION['id_token'] = $this->oidc->getIdToken();
            $_SESSION['access_token'] = $this->oidc->getAccessToken();
            $_SESSION['last_activity'] = time();
            
            // Limpar cache do usuário (similar ao C# ClearUserCache)
            $this->clearUserCache($user['id']);
            
            return $user;
            
        } catch (Exception $e) {
            error_log("Erro ao processar callback: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Fazer logout
     */
    public function logout()
    {
        $idToken = $_SESSION['id_token'] ?? null;
        
        // Limpar sessão local
        session_destroy();
        
        // Redirecionar para logout do Acesso Cidadão
        if ($idToken) {
            $logoutUrl = $this->config['endpoints']['logout'];
            $params = http_build_query([
                'id_token_hint' => $idToken,
                'post_logout_redirect_uri' => $this->config['post_logout_redirect_uri']
            ]);
            
            header("Location: " . $logoutUrl . '?' . $params);
            exit;
        } else {
            header("Location: " . $this->config['post_logout_redirect_uri']);
            exit;
        }
    }
    
    /**
     * Obter informações adicionais do usuário via API
     */
    public function getUserInfo()
    {
        if (!isset($_SESSION['access_token'])) {
            throw new Exception("Usuário não autenticado");
        }
        
        $accessToken = $_SESSION['access_token'];
        
        $ch = curl_init($this->config['endpoints']['userinfo']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Erro ao obter informações do usuário");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Limpar cache do usuário (implementar conforme sua necessidade)
     */
    private function clearUserCache($userId)
    {
        // Implementar limpeza de cache conforme seu sistema
        // Exemplo: limpar cache Redis, Memcached, etc.
    }
    
    /**
     * Verificar se usuário está autenticado
     */
    public static function isAuthenticated()
    {
        return isset($_SESSION['user']) && isset($_SESSION['access_token']);
    }
    
    /**
     * Obter usuário da sessão
     */
    public static function getUser()
    {
        return $_SESSION['user'] ?? null;
    }
    
    /**
     * Verificar se sessão expirou
     */
    public static function checkSessionExpiry()
    {
        $config = require __DIR__ . '/../config/acesso_cidadao.php';
        $expireSeconds = $config['session']['expire_minutes'] * 60;
        
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > $expireSeconds) {
                session_destroy();
                return false;
            }
            $_SESSION['last_activity'] = time();
        }
        
        return true;
    }
}