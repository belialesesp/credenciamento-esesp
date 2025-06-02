<?php

require_once('../init.php');

if(isset( $_SESSION['user_id'])) {
  header('Location: home.php');
  exit();
}

$error_message = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

require_once '../components/header.php';
?>

<div class="container register-container">

  <h1 class="main-title">Entrar</h1>

  <?php if($error_message): ?>
    <p class="error-message"><?= htmlspecialchars($error_message) ?></p>
  <?php endif; ?>

  <form id="loginForm" method="post" action="../auth/process_login.php" class="needs-validation" enctype="multipart/form-data" novalidate>
  
    <div class="did-floating-label-content col-12">
      <input name="email" id="email" class="did-floating-input form-control" type="email" placeholder=" " value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required/>
      <label for="email" class="did-floating-label">Email*</label>
      <div class="invalid-feedback">Informe um email</div>
    </div>
    <div class="did-floating-label-content col-12">
      <input name="password" id="password" class="did-floating-input form-control" type="password" placeholder=" " required/>
      <label for="password" class="did-floating-label">Senha*</label>
      <div class="invalid-feedback">Informe uma senha</div>
    </div>
  
    <input type="submit" class="btnF form-btn" name="send-form" value="Enviar"></input>
      
  </form>
  
  <div class="notes-container">
    
    <p class="obs">Ainda não possui cadastro? Clique <a href="./register.php">aqui</a> para se cadastrar</p>
  </div>


</div>

<?php

require_once '../components/footer.php';

?>

<script>
  (function() {
  'use strict';
  window.addEventListener('load', function() {
    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var forms = document.getElementsByClassName('needs-validation');
    // Loop over them and prevent submission
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
