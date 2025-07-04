<?php
// pages/login_docente_enhanced.php
// Enhanced version with optional password field

require_once('../init.php');

// Check if already logged in as docente
if(isset($_SESSION['docente_id']) && isset($_SESSION['docente_type'])) {
    if($_SESSION['docente_type'] == 'regular') {
        header('Location: docente_profile.php');
    } else {
        header('Location: docente_pos_profile.php');
    }
    exit();
}

$error_message = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

// Check if password protection is enabled (you can set this in a config file)
$passwordProtectionEnabled = false; // Set to true to enable password field

require_once '../components/header.php';
?>

<div class="container register-container">
    <h1 class="main-title">Portal do Docente</h1>
    <p class="text-center mb-4">Acesse seu perfil e documentos</p>

    <?php if($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error_message) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <form id="docenteLoginForm" method="post" action="../auth/process_docente_login_enhanced.php" class="needs-validation" novalidate>
        <div class="did-floating-label-content col-12 mb-3">
            <input name="cpf" id="cpf" class="did-floating-input form-control" type="text" 
                   placeholder=" " value="<?= htmlspecialchars($_POST['cpf'] ?? '') ?>" 
                   pattern="\d{3}\.\d{3}\.\d{3}-\d{2}" 
                   maxlength="14" required/>
            <label for="cpf" class="did-floating-label">CPF*</label>
            <div class="invalid-feedback">Informe um CPF válido (xxx.xxx.xxx-xx)</div>
        </div>

        <?php if($passwordProtectionEnabled): ?>
        <div class="did-floating-label-content col-12 mb-3">
            <input name="password" id="password" class="did-floating-input form-control" type="password" 
                   placeholder=" "/>
            <label for="password" class="did-floating-label">Senha (opcional)</label>
            <small class="form-text text-muted">
                Deixe em branco se ainda não definiu uma senha
            </small>
        </div>
        <?php endif; ?>

        <div class="row mt-4">
            <div class="col-12">
                <h5>Selecione o tipo de docente:</h5>
            </div>
            <div class="col-md-6 mb-3">
                <div class="custom-control custom-radio">
                    <input type="radio" id="docente_regular" name="docente_type" value="regular" 
                           class="custom-control-input" checked>
                    <label class="custom-control-label" for="docente_regular">
                        <i class="fas fa-chalkboard-teacher"></i> Docente Regular
                    </label>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="custom-control custom-radio">
                    <input type="radio" id="docente_pos" name="docente_type" value="postgraduate" 
                           class="custom-control-input">
                    <label class="custom-control-label" for="docente_pos">
                        <i class="fas fa-graduation-cap"></i> Docente Pós-Graduação
                    </label>
                </div>
            </div>
        </div>

        <div class="text-center">
            <button type="submit" class="btnF form-btn" name="send-form">
                <i class="fas fa-sign-in-alt"></i> Acessar
            </button>
        </div>
    </form>

    <div class="notes-container mt-4">
        <div class="row">
            <div class="col-md-6 text-center">
                <p class="obs">
                    <i class="fas fa-user-tie"></i> 
                    É administrador? <a href="./login.php">Login administrativo</a>
                </p>
            </div>
            <div class="col-md-6 text-center">
                <p class="obs">
                    <i class="fas fa-user-plus"></i> 
                    Ainda não cadastrado? <a href="./register.php">Cadastre-se aqui</a>
                </p>
            </div>
        </div>
        
        <?php if($passwordProtectionEnabled): ?>
        <div class="text-center mt-3">
            <p class="obs">
                <i class="fas fa-key"></i> 
                Esqueceu sua senha? <a href="./reset_password_docente.php">Recuperar senha</a>
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../components/footer.php'; ?>

<style>
.custom-control-label {
    font-weight: 500;
    cursor: pointer;
    padding: 10px;
    border-radius: 5px;
    transition: background-color 0.3s;
}

.custom-control-input:checked ~ .custom-control-label {
    background-color: #f0f0f0;
}

.alert {
    margin-bottom: 20px;
}

.form-btn {
    margin-top: 20px;
    padding: 12px 40px;
    font-size: 16px;
}

.notes-container {
    border-top: 1px solid #dee2e6;
    padding-top: 20px;
}
</style>

<script>
// CPF Mask
document.getElementById('cpf').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 11) {
        value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    }
    e.target.value = value;
});

// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Show loading spinner on form submit
document.getElementById('docenteLoginForm').addEventListener('submit', function(e) {
    if (this.checkValidity()) {
        const button = this.querySelector('button[type="submit"]');
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
        button.disabled = true;
    }
});
</script>