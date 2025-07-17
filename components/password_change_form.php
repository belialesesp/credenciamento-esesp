<?php
// components/password_change_form.php
// Include this component in profile pages (teacher.php, etc.)

// Check if user should be prompted to change password
$showPasswordForm = false;
$isFirstLogin = $_SESSION['first_login'] ?? false;
$showPasswordSection = isset($_GET['action']) && $_GET['action'] === 'change-password';

if ($isFirstLogin || $showPasswordSection) {
    $showPasswordForm = true;
}

// Handle password change messages
$passwordError = $_SESSION['password_error'] ?? '';
$passwordSuccess = $_SESSION['password_success'] ?? '';
unset($_SESSION['password_error']);
unset($_SESSION['password_success']);
?>

<?php if ($showPasswordForm): ?>
<div class="password-change-section mt-4">
    <div class="card">
        <div class="card-header">
            <h3>
                <?php if ($isFirstLogin): ?>
                    <i class="fas fa-exclamation-triangle text-warning"></i> Primeiro Acesso - Alterar Senha
                <?php else: ?>
                    <i class="fas fa-key"></i> Alterar Senha
                <?php endif; ?>
            </h3>
        </div>
        <div class="card-body">
            <?php if ($isFirstLogin): ?>
                <div class="alert alert-warning">
                    <strong>Atenção!</strong> Este é seu primeiro acesso ao sistema. Por segurança, você deve criar uma nova senha.
                    <br>Sua senha atual é seu CPF (apenas números).
                </div>
            <?php endif; ?>

            <?php if ($passwordError): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($passwordError) ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($passwordSuccess): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($passwordSuccess) ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <form method="post" action="../auth/process_password_change.php" id="passwordChangeForm">
                <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                
                <div class="form-group password-input-group">
                    <label for="current_password">Senha Atual</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="current_password" 
                               name="current_password" required>
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary" 
                                    onclick="togglePasswordVisibility('current_password')">
                                <i class="fas fa-eye" id="current_password_icon"></i>
                            </button>
                        </div>
                    </div>
                    <?php if ($isFirstLogin): ?>
                        <small class="form-text text-muted">
                            Use seu CPF (apenas números) como senha atual
                        </small>
                    <?php endif; ?>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group password-input-group">
                            <label for="new_password">Nova Senha</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" 
                                       name="new_password" required minlength="8">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" 
                                            onclick="togglePasswordVisibility('new_password')">
                                        <i class="fas fa-eye" id="new_password_icon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group password-input-group">
                            <label for="confirm_password">Confirmar Nova Senha</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" required minlength="8">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" 
                                            onclick="togglePasswordVisibility('confirm_password')">
                                        <i class="fas fa-eye" id="confirm_password_icon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="password-requirements">
                    <h5>Requisitos da senha:</h5>
                    <ul class="list-unstyled" id="password-requirements">
                        <li id="req-length">
                            <i class="fas fa-circle text-muted"></i> Mínimo de 8 caracteres
                        </li>
                        <li id="req-uppercase">
                            <i class="fas fa-circle text-muted"></i> Pelo menos uma letra maiúscula
                        </li>
                        <li id="req-lowercase">
                            <i class="fas fa-circle text-muted"></i> Pelo menos uma letra minúscula
                        </li>
                        <li id="req-number">
                            <i class="fas fa-circle text-muted"></i> Pelo menos um número
                        </li>
                        <li id="req-special">
                            <i class="fas fa-circle text-muted"></i> Pelo menos um caractere especial (@$!%*?&)
                        </li>
                    </ul>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Alterar Senha
                    </button>
                    <?php if (!$isFirstLogin): ?>
                        <a href="<?= htmlspecialchars(strtok($_SERVER['REQUEST_URI'], '?')) ?>" 
                           class="btn btn-secondary">
                            Cancelar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
function togglePasswordVisibility(fieldId) {
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

// Real-time password validation
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    
    // Check requirements
    updateRequirement('req-length', password.length >= 8);
    updateRequirement('req-uppercase', /[A-Z]/.test(password));
    updateRequirement('req-lowercase', /[a-z]/.test(password));
    updateRequirement('req-number', /[0-9]/.test(password));
    updateRequirement('req-special', /[@$!%*?&]/.test(password));
});

function updateRequirement(id, isMet) {
    const element = document.getElementById(id);
    const icon = element.querySelector('i');
    
    if (isMet) {
        icon.classList.remove('fa-circle', 'text-muted');
        icon.classList.add('fa-check-circle', 'text-success');
    } else {
        icon.classList.remove('fa-check-circle', 'text-success');
        icon.classList.add('fa-circle', 'text-muted');
    }
}

// Form validation
document.getElementById('passwordChangeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        alert('As senhas não coincidem!');
        return;
    }
    
    // Check all requirements
    if (!isPasswordValid(newPassword)) {
        alert('A senha não atende aos requisitos mínimos!');
        return;
    }
    
    this.submit();
});

function isPasswordValid(password) {
    return password.length >= 8 &&
           /[A-Z]/.test(password) &&
           /[a-z]/.test(password) &&
           /[0-9]/.test(password) &&
           /[@$!%*?&]/.test(password);
}
</script>

<style>
.password-change-section {
    margin-bottom: 30px;
}

.password-requirements {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.password-requirements h5 {
    font-size: 16px;
    margin-bottom: 10px;
}

.password-requirements li {
    margin-bottom: 5px;
    font-size: 14px;
}

.password-requirements i {
    margin-right: 8px;
    font-size: 12px;
}
</style>

<?php endif; ?>

<?php if (!$showPasswordForm && !$isFirstLogin): ?>
<!-- Show change password button in profile -->
<div class="mt-3">
    <a href="?id=<?= htmlspecialchars($_GET['id'] ?? '') ?>&action=change-password" 
       class="btn btn-outline-primary">
        <i class="fas fa-key"></i> Alterar Senha
    </a>
</div>
<?php endif; ?>