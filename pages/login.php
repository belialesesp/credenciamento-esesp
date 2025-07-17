<?php
// pages/login.php - CPF Only Version

require_once('../init.php');

if (isset($_SESSION['user_id'])) {
  header('Location: home.php');
  exit();
}

$error_message = $_SESSION['login_error'] ?? '';
$success_message = $_SESSION['login_message'] ?? '';
unset($_SESSION['login_error']);
unset($_SESSION['login_message']);

require_once '../components/header.php';
?>
<style>
  .did-floating-label-content {
    position: relative;
  }

  .password-toggle {
    position: absolute;
    right: 10px;
    top: 12px;
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
    z-index: 10;
    padding: 5px;
  }

  .password-toggle:hover {
    color: #333;
  }

  .password-toggle i {
    font-size: 1.1em;
  }

  /* Add to existing styles */
  .forgot-password-link {
    text-align: center;
    margin-top: 15px;
  }

  .forgot-password-link a {
    color: #007bff;
    text-decoration: none;
    font-size: 14px;
    transition: color 0.3s;
  }

  .forgot-password-link a:hover {
    text-decoration: underline;
    color: #0056b3;
  }

  /* Optional: Add a divider */
  .login-divider {
    text-align: center;
    margin: 20px 0;
    position: relative;
  }

  .login-divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #dee2e6;
  }

  .login-divider span {
    background: white;
    padding: 0 15px;
    position: relative;
    color: #6c757d;
    font-size: 14px;
  }
</style>
<div class="container register-container">

  <h1 class="main-title">Entrar</h1>

  <?php if ($success_message): ?>
    <p class="success-message"><?= htmlspecialchars($success_message) ?></p>
  <?php endif; ?>

  <?php if ($error_message): ?>
    <p class="error-message"><?= htmlspecialchars($error_message) ?></p>
  <?php endif; ?>

  <form id="loginForm" method="post" action="../auth/process_login.php" class="needs-validation" enctype="multipart/form-data" novalidate>

    <div class="did-floating-label-content col-12">
      <input name="cpf" id="cpf" class="did-floating-input form-control" type="text"
        placeholder=" " value="<?= htmlspecialchars($_POST['cpf'] ?? '') ?>"
        maxlength="20" required />
      <label for="cpf" class="did-floating-label">CPF*</label>
      <div class="invalid-feedback">Informe seu CPF</div>
    </div>

    <div class="did-floating-label-content col-12">
      <input name="password" id="password" class="did-floating-input form-control" type="password" placeholder=" " required />
      <button type="button" class="password-toggle" onclick="togglePassword('password')" tabindex="-1">
        <i class="fas fa-eye" id="password_icon"></i>
      </button>
      <label for="password" class="did-floating-label">Senha*</label>
      <div class="invalid-feedback">Informe uma senha</div>
    </div>

    <input type="submit" class="btnF form-btn" name="send-form" value="Entrar"></input>
    <!-- Add the forgot password link -->
    <div class="forgot-password-link">
      <a href="forgot-password.php">Esqueceu sua senha?</a>
    </div>

  </form>

  <!-- Also update the form action to use the new processor -->
  <form id="loginForm" method="post" action="../auth/process_login_unified.php" class="needs-validation" enctype="multipart/form-data" novalidate>


    <div class="notes-container">
      <p class="obs">Primeira vez acessando?</p>
      <ul>
        <li>Use seu CPF (apenas números) como usuário</li>
        <li>Sua senha inicial é igual ao seu CPF</li>
        <li>Você será solicitado a criar uma nova senha no primeiro acesso</li>
      </ul>
    </div>

</div>

<?php require_once '../components/footer.php'; ?>

<script>
  // CPF Mask with special case for admin
  document.getElementById('cpf').addEventListener('input', function(e) {
    let value = e.target.value;

    // Allow 'credenciamento' for admin login
    if (value.toLowerCase().startsWith('credenciamento')) {
      return;
    }

    // Regular CPF formatting
    value = value.replace(/\D/g, '');
    if (value.length <= 11) {
      value = value.replace(/(\d{3})(\d)/, '$1.$2');
      value = value.replace(/(\d{3})(\d)/, '$1.$2');
      value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }
    e.target.value = value;
  });

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
</script>