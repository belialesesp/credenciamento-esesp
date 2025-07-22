<?php
// pages/reset-password.php
session_start();
require_once '../backend/classes/database.class.php';

// Get token and type from URL
$token = $_GET['token'] ?? '';
$userType = $_GET['type'] ?? '';

// Validate token format
if (empty($token) || !ctype_xdigit($token) || strlen($token) !== 64) {
    $_SESSION['reset_error'] = 'Link de redefinição inválido.';
    header('Location: forgot-password.php');
    exit;
}

// Check if token is valid and not expired
$connection = new Database();
$conn = $connection->connect();

$user = null;
$tableName = '';

if (empty($userType)) {
    // Check main user table
    $stmt = $conn->prepare("
        SELECT id, name, email 
        FROM user 
        WHERE password_reset_token = :token 
        AND password_reset_expires > NOW()
        LIMIT 1
    ");
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $tableName = 'user';
} else {
    // Check specific user type table
    $tableMap = [
        'teacher' => 'teacher',
        'postg_teacher' => 'postg_teacher',
        'interpreter' => 'interpreter',
        'technician' => 'technician'
    ];
    
    if (isset($tableMap[$userType])) {
        $tableName = $tableMap[$userType];
        $stmt = $conn->prepare("
            SELECT id, name, email 
            FROM $tableName 
            WHERE password_reset_token = :token 
            AND password_reset_expires > NOW()
            LIMIT 1
        ");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!$user) {
    $_SESSION['reset_error'] = 'Link de redefinição inválido ou expirado. Por favor, solicite um novo link.';
    header('Location: forgot-password.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Sistema de Credenciamento</title>
    <link rel="stylesheet" href="../includes/style.css">
    <style>
        body {
            background-color: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
        }
        
        .reset-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            margin: 1rem;
        }
        
        .reset-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .reset-header h1 {
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .user-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .user-info strong {
            display: block;
            color: #495057;
            margin-bottom: 0.3rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #495057;
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.15s ease-in-out;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .password-requirements {
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .password-requirements ul {
            margin: 0.5rem 0;
            padding-left: 1.5rem;
        }
        
        .password-requirements li {
            margin-bottom: 0.3rem;
        }
        
        .password-strength {
            margin-top: 0.5rem;
            height: 5px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            transition: width 0.3s ease, background-color 0.3s ease;
            width: 0;
        }
        
        .strength-weak { background-color: #dc3545; }
        .strength-medium { background-color: #ffc107; }
        .strength-strong { background-color: #28a745; }
        
        .btn {
            width: 100%;
            padding: 0.75rem;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.25rem;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .back-to-login {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-to-login a {
            color: #007bff;
            text-decoration: none;
        }
        
        .back-to-login a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h1>Redefinir Senha</h1>
            <p>Crie uma nova senha para sua conta</p>
        </div>
        
        <div class="user-info">
            <strong>Usuário:</strong> <?php echo htmlspecialchars($user['name']); ?>
            <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?>
        </div>
        
        <?php if (isset($_SESSION['reset_error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo htmlspecialchars($_SESSION['reset_error']); 
                unset($_SESSION['reset_error']);
                ?>
            </div>
        <?php endif; ?>
        
        <form action="../auth/process_reset_password.php" method="POST" id="resetForm">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($userType); ?>">
            
            <div class="form-group">
                <label for="password">Nova Senha *</label>
                <div class="password-toggle">
                    <input type="password" 
                           class="form-control" 
                           id="password" 
                           name="password" 
                           required>
                    <span class="toggle-password" onclick="togglePassword('password')">👁️</span>
                </div>
                <div class="password-strength">
                    <div class="password-strength-bar" id="strengthBar"></div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmar Nova Senha *</label>
                <div class="password-toggle">
                    <input type="password" 
                           class="form-control" 
                           id="confirm_password" 
                           name="confirm_password" 
                           required>
                    <span class="toggle-password" onclick="togglePassword('confirm_password')">👁️</span>
                </div>
            </div>
            
            <div class="password-requirements">
                <p>A senha deve conter:</p>
                <ul>
                    <li id="length-check">Mínimo de 8 caracteres</li>
                    <li id="uppercase-check">Pelo menos uma letra maiúscula</li>
                    <li id="lowercase-check">Pelo menos uma letra minúscula</li>
                    <li id="number-check">Pelo menos um número</li>
                    <li id="special-check">Pelo menos um caractere especial (@$!%*?&)</li>
                </ul>
            </div>
            
            <button type="submit" class="btn" id="submitBtn" disabled>
                Redefinir Senha
            </button>
        </form>
        
        <div class="back-to-login">
            <a href="login.php">← Voltar para o login</a>
        </div>
    </div>
    
    <script>
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
        field.setAttribute('type', type);
    }
    
    // Password strength checker
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const strengthBar = document.getElementById('strengthBar');
    const submitBtn = document.getElementById('submitBtn');
    
    function checkPasswordStrength(password) {
        let strength = 0;
        const checks = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[@$!%*?&]/.test(password)
        };
        
        // Update requirement indicators
        document.getElementById('length-check').style.color = checks.length ? '#28a745' : '#6c757d';
        document.getElementById('uppercase-check').style.color = checks.uppercase ? '#28a745' : '#6c757d';
        document.getElementById('lowercase-check').style.color = checks.lowercase ? '#28a745' : '#6c757d';
        document.getElementById('number-check').style.color = checks.number ? '#28a745' : '#6c757d';
        document.getElementById('special-check').style.color = checks.special ? '#28a745' : '#6c757d';
        
        // Calculate strength
        strength = Object.values(checks).filter(Boolean).length;
        
        // Update strength bar
        if (strength <= 2) {
            strengthBar.className = 'password-strength-bar strength-weak';
            strengthBar.style.width = '33%';
        } else if (strength <= 4) {
            strengthBar.className = 'password-strength-bar strength-medium';
            strengthBar.style.width = '66%';
        } else {
            strengthBar.className = 'password-strength-bar strength-strong';
            strengthBar.style.width = '100%';
        }
        
        return strength === 5;
    }
    
    function validateForm() {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        const isPasswordValid = checkPasswordStrength(password);
        const passwordsMatch = password === confirmPassword && password.length > 0;
        
        submitBtn.disabled = !(isPasswordValid && passwordsMatch);
        
        if (confirmPassword.length > 0 && !passwordsMatch) {
            confirmPasswordInput.style.borderColor = '#dc3545';
        } else if (confirmPassword.length > 0 && passwordsMatch) {
            confirmPasswordInput.style.borderColor = '#28a745';
        } else {
            confirmPasswordInput.style.borderColor = '#ced4da';
        }
    }
    
    passwordInput.addEventListener('input', validateForm);
    confirmPasswordInput.addEventListener('input', validateForm);
    
    // Prevent form submission if validation fails
    document.getElementById('resetForm').addEventListener('submit', function(e) {
        if (submitBtn.disabled) {
            e.preventDefault();
        }
    });
    </script>
</body>
</html>