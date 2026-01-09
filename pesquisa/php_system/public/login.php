<?php
/**
 * public/login.php - Página de Login
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Se já está logado, redirecionar para dashboard
if (isAuthenticated()) {
    header('Location: index.php');
    exit;
}

$error = null;

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        $result = login($username, $password);
        
        if ($result['success']) {
            // Redirecionar para página solicitada ou dashboard
            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Verificar se veio de timeout
$timeout = isset($_GET['timeout']) && $_GET['timeout'] == '1';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1e3a5f;
            --secondary: #2c5f8d;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            margin: 0;
            opacity: 0.9;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e5e7eb;
            padding: 0.75rem 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(30, 58, 95, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .input-group-text {
            border-radius: 10px 0 0 10px;
            background: #f3f4f6;
            border: 2px solid #e5e7eb;
            border-right: none;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }
        
        .form-group {
            position: relative;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>🎓 ESESP</h1>
                <p>Sistema de Pesquisas de Satisfação</p>
            </div>
            
            <div class="login-body">
                <?php if ($timeout): ?>
                <div class="alert alert-warning">
                    Sua sessão expirou. Por favor, faça login novamente.
                </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= e($error) ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Usuário</label>
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username" 
                               placeholder="Digite seu usuário"
                               value="<?= e($_POST['username'] ?? '') ?>"
                               required 
                               autofocus>
                    </div>
                    
                    <div class="mb-4 form-group">
                        <label for="password" class="form-label">Senha</label>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               placeholder="Digite sua senha"
                               required>
                        <span class="password-toggle" onclick="togglePassword()">
                            👁️
                        </span>
                    </div>
                    
                    <button type="submit" class="btn btn-login">
                        Entrar
                    </button>
                </form>
                
                <div class="mt-4 text-center">
                    <small class="text-muted">
                        Sistema protegido por autenticação
                    </small>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3" style="color: white;">
            <small>
                © <?= date('Y') ?> ESESP - Escola de Serviço Público do Espírito Santo
            </small>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const toggle = document.querySelector('.password-toggle');
            
            if (input.type === 'password') {
                input.type = 'text';
                toggle.textContent = '🙈';
            } else {
                input.type = 'password';
                toggle.textContent = '👁️';
            }
        }
        
        // Focus no primeiro campo
        document.getElementById('username').focus();
    </script>
</body>
</html>
