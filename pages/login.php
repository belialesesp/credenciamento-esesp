<?php
// pages/login.php - CPF Only Version

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
  
    <div class="did-floating-label-content col-12">
      <input name="cpf" id="cpf" class="did-floating-input form-control" type="text" 
             placeholder=" " value="<?= htmlspecialchars($_POST['cpf'] ?? '') ?>" 
             maxlength="20" required/>
      <label for="cpf" class="did-floating-label">CPF*</label>
      <div class="invalid-feedback">Informe seu CPF</div>
    </div>
    
    <div class="did-floating-label-content col-12">
      <input name="password" id="password" class="did-floating-input form-control" type="password" placeholder=" " required/>
      <label for="password" class="did-floating-label">Senha*</label>
      <div class="invalid-feedback">Informe uma senha</div>
    </div>
  
    <input type="submit" class="btnF form-btn" name="send-form" value="Entrar"></input>
      
  </form>
  
  <div class="notes-container">
    <p class="obs">Primeira vez acessando? Use seu CPF como senha inicial.</p>
    <p class="obs"><small>Admin: use 'credenciamento' como CPF</small></p>
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
</script>