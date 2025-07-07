<?php
// pages/login.php - MINIMAL CHANGES TO YOUR EXISTING FILE

require_once('../init.php');

if(isset($_SESSION['user_id'])) {
  header('Location: home.php');
  exit();
}

$error_message = $_SESSION['login_error'] ?? '';
$success_message = $_SESSION['login_message'] ?? '';
unset($_SESSION['login_error']);
unset($_SESSION['login_message']);

require_once '../components/header.php';
?>

<div class="container register-container">

  <h1 class="main-title">Entrar</h1>

  <?php if($success_message): ?>
    <p class="success-message"><?= htmlspecialchars($success_message) ?></p>
  <?php endif; ?>

  <?php if($error_message): ?>
    <p class="error-message"><?= htmlspecialchars($error_message) ?></p>
  <?php endif; ?>

  <form id="loginForm" method="post" action="../auth/process_login.php" class="needs-validation" enctype="multipart/form-data" novalidate>
  
    <!-- CHANGED: type from "email" to "text", name from "email" to "username" -->
    <div class="did-floating-label-content col-12">
      <input name="username" id="username" class="did-floating-input form-control" type="text" placeholder=" " value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required/>
      <label for="username" class="did-floating-label">Email ou CPF*</label>
      <div class="invalid-feedback">Informe seu email ou CPF</div>
    </div>
    
    <div class="did-floating-label-content col-12">
      <input name="password" id="password" class="did-floating-input form-control" type="password" placeholder=" " required/>
      <label for="password" class="did-floating-label">Senha*</label>
      <div class="invalid-feedback">Informe uma senha</div>
    </div>
  
    <input type="submit" class="btnF form-btn" name="send-form" value="Enviar"></input>
      
  </form>
  
  <div class="notes-container">
    <!-- CHANGED: Updated text and link -->
    <p class="obs">Primeira vez acessando? Use seu CPF como usuário e senha.</p>
    <p class="obs"><a href="./forgot_password.php">Esqueci minha senha</a></p>
  </div>

</div>

<?php
require_once '../components/footer.php';
?>

<script>
  // ADDED: CPF formatting
  document.getElementById('username').addEventListener('input', function(e) {
    let value = e.target.value;
    // Only apply mask if all characters are numbers
    if (/^\d+$/.test(value.replace(/[\.\-]/g, ''))) {
      value = value.replace(/\D/g, '');
      if (value.length <= 11) {
        value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
      }
      e.target.value = value;
    }
  });

  // EXISTING: Your validation code
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
</script>