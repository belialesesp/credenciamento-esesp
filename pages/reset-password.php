<?php
// pages/reset-password.php
// Updated to work with unified user table
session_start();
require_once '../backend/classes/database.class.php';

// Get token from URL
$token = $_GET['token'] ?? '';

// Validate token format
if (empty($token) || !ctype_xdigit($token) || strlen($token) !== 64) {
    $_SESSION['reset_error'] = 'Link de redefinição inválido.';
    header('Location: forgot-password.php');
    exit;
}

// Check if token is valid and not expired
$connection = new Database();
$conn = $connection->connect();

// Check token in unified user table
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

if (!$user) {
    $_SESSION['reset_error'] = 'Link de redefinição inválido ou expirado. Por favor, solicite um novo link.';
    header('Location: forgot-password.php');
    exit;
}

$pageTitle = 'Redefinir Senha';
require_once '../components/header.php';
?>

<style>
    .forgot-password-container {
        max-width: 400px;
        margin: 50px auto;
        padding: 30px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .password-requirements {
        font-size: 0.85em;
        color: #666;
        margin-top: 10px;
        padding: 10px;
        background: #f5f5f5;
        border-radius: 5px;
    }
    
    .password-requirements ul {
        margin: 5px 0;
        padding-left: 20px;
    }
    
    .password-toggle {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        color: #666;
    }
    
    .password-field-wrapper {
        position: relative;
    }
    
    .strength-indicator {
        height: 5px;
        margin-top: 5px;
        border-radius: 3px;
        transition: all 0.3s ease;
    }
    
    .strength-weak { background-color: #ff4444; }
    .strength-fair { background-color: #ffaa00; }
    .strength-good { background-color: #00aa00; }
    .strength-strong { background-color: #008800; }
</style>

<div class="container">
    <div class="forgot-password-container">
        <h2 class="text-center mb-4">Redefinir Senha</h2>
        
        <p class="text-center mb-4">Olá, <?= htmlspecialchars($user['name']) ?>!</p>
        <p class="text-center mb-4">Digite sua nova senha abaixo:</p>
        
        <?php if (isset($_SESSION['reset_error'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($_SESSION['reset_error']) ?>
            </div>
            <?php unset($_SESSION['reset_error']); ?>
        <?php endif; ?>
        
        <form method="POST" action="../auth/process_reset_password.php" class="needs-validation" novalidate>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            
            <div class="mb-3 password-field-wrapper">
                <label for="password" class="form-label">Nova Senha *</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                    <i class="fas fa-eye" id="password_icon"></i>
                </button>
                <div class="strength-indicator" id="strength-indicator"></div>
                <div class="invalid-feedback">
                    Por favor, digite uma senha.
                </div>
            </div>
            
            <div class="mb-3 password-field-wrapper">
                <label for="confirm_password" class="form-label">Confirmar Nova Senha *</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                    <i class="fas fa-eye" id="confirm_password_icon"></i>
                </button>
                <div class="invalid-feedback" id="confirm-feedback">
                    Por favor, confirme sua senha.
                </div>
            </div>
            
            <div class="password-requirements">
                <strong>Requisitos da senha:</strong>
                <ul>
                    <li id="req-length">Mínimo de 8 caracteres</li>
                    <li id="req-uppercase">Pelo menos 1 letra maiúscula</li>
                    <li id="req-lowercase">Pelo menos 1 letra minúscula</li>
                    <li id="req-number">Pelo menos 1 número</li>
                    <li id="req-special">Pelo menos 1 caractere especial (@$!%*?&#)</li>
                </ul>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 mt-4">Redefinir Senha</button>
            
            <div class="text-center mt-3">
                <a href="login.php">Voltar ao login</a>
            </div>
        </form>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password strength checker
document.getElementById('password').addEventListener('input', function(e) {
    const password = e.target.value;
    const indicator = document.getElementById('strength-indicator');
    const requirements = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[@$!%*?&#]/.test(password)
    };
    
    // Update requirement indicators
    for (const [req, met] of Object.entries(requirements)) {
        const element = document.getElementById(`req-${req}`);
        if (met) {
            element.style.color = '#00aa00';
            element.innerHTML = element.innerHTML.replace('❌', '').replace('✓', '') + ' ✓';
        } else {
            element.style.color = '#666';
            element.innerHTML = element.innerHTML.replace('❌', '').replace('✓', '');
        }
    }
    
    // Calculate strength
    const strength = Object.values(requirements).filter(Boolean).length;
    
    // Update strength indicator
    indicator.className = 'strength-indicator';
    if (password.length > 0) {
        if (strength <= 2) indicator.classList.add('strength-weak');
        else if (strength === 3) indicator.classList.add('strength-fair');
        else if (strength === 4) indicator.classList.add('strength-good');
        else indicator.classList.add('strength-strong');
    }
    
    // Check password match
    checkPasswordMatch();
});

// Check if passwords match
document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);

function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const feedback = document.getElementById('confirm-feedback');
    
    if (confirmPassword.length > 0) {
        if (password === confirmPassword) {
            feedback.textContent = 'As senhas coincidem!';
            feedback.style.color = '#00aa00';
        } else {
            feedback.textContent = 'As senhas não coincidem.';
            feedback.style.color = '#dc3545';
        }
    }
}

// Form validation
(function() {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                event.preventDefault();
                event.stopPropagation();
                alert('As senhas não coincidem!');
                return;
            }
            
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php require_once '../components/footer.php'; ?>