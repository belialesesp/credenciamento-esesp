<?php
// pages/forgot-password.php
// Updated to work with unified user table (no user type selection needed)
session_start();
$pageTitle = 'Esqueci Minha Senha';
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
    
    .info-box {
        background: #f0f8ff;
        border: 1px solid #b0d4ff;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 20px;
        font-size: 0.9em;
    }
    
    .info-box i {
        color: #0066cc;
        margin-right: 8px;
    }
</style>

<div class="container">
    <div class="forgot-password-container">
        <h2 class="text-center mb-4">Esqueceu sua senha?</h2>
        
        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            Digite seu CPF e email cadastrados para receber as instruções de redefinição de senha.
        </div>
        
        <?php if (isset($_SESSION['forgot_error'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($_SESSION['forgot_error']) ?>
            </div>
            <?php unset($_SESSION['forgot_error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['forgot_success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['forgot_success']) ?>
            </div>
            <?php unset($_SESSION['forgot_success']); ?>
        <?php endif; ?>
        
        <form method="POST" action="../auth/process_forgot_password.php" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="cpf" class="form-label">CPF *</label>
                <input type="text" class="form-control" id="cpf" name="cpf" 
                       placeholder="000.000.000-00" required 
                       maxlength="14" pattern="\d{3}\.\d{3}\.\d{3}-\d{2}">
                <div class="invalid-feedback">
                    Por favor, digite um CPF válido.
                </div>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email *</label>
                <input type="email" class="form-control" id="email" name="email" 
                       placeholder="seu@email.com" required>
                <div class="invalid-feedback">
                    Por favor, digite um email válido.
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">Enviar Instruções</button>
            
            <div class="text-center mt-3">
                <a href="login.php">Voltar ao login</a>
            </div>
        </form>
    </div>
</div>

<script>
// CPF Mask
document.getElementById('cpf').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    
    if (value.length <= 11) {
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }
    
    e.target.value = value;
});

// Form validation
(function() {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
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