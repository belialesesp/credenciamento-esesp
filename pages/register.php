<?php

require_once '../components/header.php';

?>

<style>
  .btn-view {
    position: absolute;
    right: 0;
    top: 6px;
    border: none;
  }
  .btn-view > i {
    color: #1e4c82;
  }

  ul {
    list-style: none;
    padding: 0.5rem 1rem;
  }

  li {
    font-size: 14px;
    color: #fff;
  }

  i {
    margin-right: 5px;
  }

  .password-container {
    position: relative;
  }

  .speech-bubble {
	position: relative;
	background: #7dcfe7;
	border-radius: .4em;
  }

  .speech-bubble:after {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    width: 0;
    height: 0;
    border: 20px solid transparent;
    border-right-color: #7dcfe7;
    border-left: 0;
    margin-top: -20px;
    margin-left: -20px;
  }

  .password-rules {
    position: absolute;
    right: -330px;
    top: -35px;
  }

</style>

<div class="container register-container">

  <h1 class="main-title">Página de Cadastro</h1>

  <form id="registrationForm" method="post" class="needs-validation" enctype="multipart/form-data" novalidate>
  
    <div class="did-floating-label-content col-12">
      <input name="name" id="name" class="did-floating-input form-control" type="text" placeholder=" " required/>
      <label for="name" class="did-floating-label">Nome*</label>
      <div class="invalid-feedback">Informe seu nome</div>
    </div>
    <div class="did-floating-label-content col-12">
      <input name="email" id="email" class="did-floating-input form-control" type="email" placeholder=" " required/>
      <label for="email" class="did-floating-label">Email*</label>
      <div class="invalid-feedback">Informe seu email institucional</div>
    </div>

    <div class="password-container">
      <div class="did-floating-label-content col-12">
        <input name="password" id="password" class="did-floating-input form-control" type="password" placeholder=" " oninput="checkPassword(this.value)"  required/>
        <label for="password" class="did-floating-label">Senha*</label>
        <button class="btn btn-outline-secondary btn-view" type="button" id="togglePassword">
            <i class="fas fa-eye"></i>
          </button>
      </div>
      <div class="password-rules">
        <ul class="speech-bubble">
            <li id="minLength">
              <i class="fas fa-times text-danger"></i> Mínimo de 8 caracteres
            </li>
            <li id="uppercase">
              <i class="fas fa-times text-danger"></i> Pelo menos uma letra maiúscula
            </li>
            <li id="lowercase">
              <i class="fas fa-times text-danger"></i> Pelo menos uma letra minúscula
            </li>
            <li id="number">
              <i class="fas fa-times text-danger"></i> Pelo menos um número
            </li>
            <li id="symbol">
              <i class="fas fa-times text-danger"></i> Pelo menos um símbolo (@$!%*?&+)
            </li>
        </ul>
      </div>
    </div>

    <div class="form-group">
      <div class="did-floating-label-content col-12">
        <input name="password_confirmation" id="password_confirmation" class="did-floating-input form-control" type="password" placeholder=" " required/>
        <label for="password_confirmation" class="did-floating-label">Confirme a senha*</label>
        <div class="invalid-feedback">Confirme a senha</div>
        <button class="btn btn-outline-secondary btn-view" type="button" id="toggleConfirmPassword">
            <i class="fas fa-eye"></i>
          </button>
      </div>
    </div>
     
  
    <input type="submit" class="btnF form-btn" name="send-form" value="Enviar"></input>    

  </form>
  
  <div class="notes-container">    
    <p class="obs">Já possui cadastro? Clique <a href="./login.php">aqui</a> para entrar</p>
  </div>


</div>

<?php

require_once '../components/footer.php';

?>

<script type="module">
  import { handleRegisterSubmission } from '../scripts/main.js';
  
  document.addEventListener('DOMContentLoaded', () => {
    handleRegisterSubmission();
  });
</script>
<script>

  document.getElementById('togglePassword').addEventListener('click',
    function () {
      const passwordInput = document.getElementById('password');
      const icon = this.querySelector('i');

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
  });

  document.getElementById('toggleConfirmPassword').addEventListener('click',
    function () {
      const passwordInput = document.getElementById('password_confirmation');
      const icon = this.querySelector('i');

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
  });

  function checkPassword(password) {
    const errorMessage = document.getElementById('errorMessage');

    // Check each condition and update the corresponding label
    document.getElementById('minLength').innerHTML = 
          password.length >= 8 ?
        '<i class="fas fa-check text-success"></i> Mínimo de 8 caracteres' :
        '<i class="fas fa-times text-danger"></i> Mínimo de 8 caracteres';
    document.getElementById('uppercase').innerHTML = 
          /[A-Z]/.test(password) ?
        '<i class="fas fa-check text-success"></i> Pelo menos uma letra maiúscula' :
        '<i class="fas fa-times text-danger"></i> Pelo menos uma letra maiúscula';
    document.getElementById('lowercase').innerHTML = 
          /[a-z]/.test(password) ?
        '<i class="fas fa-check text-success"></i> Pelo menos uma letra minúscula' :
        '<i class="fas fa-times text-danger"></i> Pelo menos uma letra minúscula';
    document.getElementById('number').innerHTML = 
          /[0-9]/.test(password) ?
        '<i class="fas fa-check text-success"></i> Pelo menos um número' :
        '<i class="fas fa-times text-danger"></i> Pelo menos um número';
    document.getElementById('symbol').innerHTML = 
          /[@$!%*?&+]/.test(password) ?
        '<i class="fas fa-check text-success"></i> Pelo menos um símbolo (@$!%*?&+)' :
        '<i class="fas fa-times text-danger"></i> Pelo menos um símbolo (@$!%*?&+)';

    
  }

</script>

