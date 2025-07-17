<?php
// pages/forgot-password.php - Multi-table version
session_start();

// Redirect if already logged in
if(isset($_SESSION['user_id']) || isset($_SESSION['docente_id'])) {
    header('Location: home.php');
    exit();
}

$error_message = $_SESSION['forgot_error'] ?? '';
$success_message = $_SESSION['forgot_success'] ?? '';
unset($_SESSION['forgot_error']);
unset($_SESSION['forgot_success']);

require_once '../components/header.php';
?>

<style>
.forgot-container {
    max-width: 500px;
    margin: 50px auto;
    padding: 30px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}

.forgot-header {
    text-align: center;
    margin-bottom: 30px;
}

.forgot-header h2 {
    color: #333;
    margin-bottom: 10px;
}

.forgot-header p {
    color: #666;
    font-size: 14px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #555;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
    transition: border-color 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #007bff;
}

.form-select {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
    background-color: white;
    cursor: pointer;
}

.btn-primary {
    width: 100%;
    padding: 12px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-primary:disabled {
    background: #6c757d;
    cursor: not-allowed;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.back-to-login {
    text-align: center;
    margin-top: 20px;
}

.back-to-login a {
    color: #007bff;
    text-decoration: none;
}

.back-to-login a:hover {
    text-decoration: underline;
}

.user-type-hint {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    font-size: 14px;
}

.user-type-hint h4 {
    margin-top: 0;
    font-size: 16px;
    color: #333;
}

.user-type-hint ul {
    margin: 10px 0 0 0;
    padding-left: 20px;
}

.user-type-hint li {
    color: #666;
    margin-bottom: 5px;
}
</style>

<div class="forgot-container">
    <div class="forgot-header">
        <h2>Esqueceu sua senha?</h2>
        <p>Digite suas informações para receber instruções de redefinição de senha.</p>
    </div>

    <?php if($success_message): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <?php if($error_message): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <div class="user-type-hint">
        <h4>Selecione seu tipo de usuário:</h4>
        <ul>
            <li><strong>Docente:</strong> Professor regular</li>
            <li><strong>Docente Pós:</strong> Professor de pós-graduação</li>
            <li><strong>Intérprete:</strong> Intérprete de Libras</li>
            <li><strong>Técnico:</strong> Técnico administrativo</li>
        </ul>
    </div>

    <form id="forgotForm" method="post" action="../auth/process_forgot_password_multi.php">
        <div class="form-group">
            <label for="user_type">Tipo de Usuário *</label>
            <select class="form-select" id="user_type" name="user_type" required>
                <option value="">Selecione...</option>
                <option value="teacher">Docente</option>
                <option value="postg_teacher">Docente Pós-Graduação</option>
                <option value="interpreter">Intérprete</option>
                <option value="technician">Técnico</option>
            </select>
        </div>

        <div class="form-group">
            <label for="cpf">CPF *</label>
            <input type="text" 
                   class="form-control" 
                   id="cpf" 
                   name="cpf" 
                   placeholder="Digite seu CPF" 
                   maxlength="14"
                   required>
            <small class="form-text text-muted">Digite apenas números ou use o formato: 000.000.000-00</small>
        </div>

        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" 
                   class="form-control" 
                   id="email" 
                   name="email" 
                   placeholder="Digite seu email cadastrado" 
                   required>
        </div>

        <button type="submit" class="btn btn-primary" id="submitBtn">
            Enviar instruções de redefinição
        </button>
    </form>

    <div class="back-to-login">
        <a href="login.php">← Voltar para o login</a>
    </div>
</div>

<script>
// CPF formatting
document.getElementById('cpf').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 11) value = value.slice(0, 11);
    
    if (value.length > 9) {
        value = value.slice(0, 3) + '.' + value.slice(3, 6) + '.' + value.slice(6, 9) + '-' + value.slice(9);
    } else if (value.length > 6) {
        value = value.slice(0, 3) + '.' + value.slice(3, 6) + '.' + value.slice(6);
    } else if (value.length > 3) {
        value = value.slice(0, 3) + '.' + value.slice(3);
    }
    
    e.target.value = value;
});

// Form submission handling
document.getElementById('forgotForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Processando...';
});

// Show additional hint based on user type selection
document.getElementById('user_type').addEventListener('change', function(e) {
    const hints = {
        'teacher': 'Para docentes regulares do ensino técnico',
        'postg_teacher': 'Para docentes de pós-graduação',
        'interpreter': 'Para intérpretes de Libras',
        'technician': 'Para técnicos administrativos'
    };
    
    const selectedType = e.target.value;
    const helpText = e.target.parentElement.querySelector('.type-help');
    
    if (helpText) {
        helpText.remove();
    }
    
    if (selectedType && hints[selectedType]) {
        const help = document.createElement('small');
        help.className = 'form-text text-muted type-help';
        help.textContent = hints[selectedType];
        e.target.parentElement.appendChild(help);
    }
});
</script>

<?php require_once '../components/footer.php'; ?>