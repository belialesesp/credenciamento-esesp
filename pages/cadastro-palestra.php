
<?php 
  include_once('../components/header.php');

?>

<style>
  .btn-small {
    font-size: 12px;
    background-color: #fca934;
    color: #fff;
    width: 6rem;
    padding: 5px;
  }

  .btn-small:hover {
    background-color: #fca934;
    opacity: 0.7;
  }

  .span-container {
    margin-top: 1rem;
    text-align: center;

    & span {
      font-size: 12px;
    }

  }

  .btn-container {
    display: flex;
    justify-content: space-evenly;
  }

  .user-info {
    color: #1e4c82;
  }



</style>


<main>
  <div class="container">
    <h1 class="main-title">Cadastro de Palestras</h1>

    <section class="form-section form">
      <h5 class="form-subtitle">Docente</h5>
      <p>Insira abaixo o cpf credenciado e clique na categoria de credenciamento</p>
      <div class="row" style="align-items: baseline;">
        <div class="did-floating-label-content col-8">
          <input name="cpf" id="cpf" class="did-floating-input form-control" type="text" placeholder=" " />
          <label for="cpf" class="did-floating-label">Cpf</label>
        </div>
        <div class="col-4 btn-container">
          <button id="btnDocente" class="btn btn-small">Docente</button>
          <button id="btnDocentePos" class="btn btn-small">Docente pós graduação</button>
        </div>
      </div>
      <div class="span-container">
        <span>Ainda não está credenciado? Clique <a href="../index.html">aqui</a> para fazer seu cadastro </span>
      </div>
    </section>
    
    <!-- Mensagem de boas-vindas ao docente -->
    <section id="welcomeSection" class="form user-info" style="display: none;">
      <h4 class="">Bem-vindo(a), <span id="teacherName"></span>!</h4>
      <p>Complete o formulário abaixo para cadastrar sua palestra.</p>
    </section>
    
    <!-- Seção para docentes não cadastrados -->
    <section id="notRegisteredSection" class="form user-info" style="display: none;">
      <h2 class="">Docente não encontrado!</h2>
      <p>Não encontramos seu CPF em nossa base de dado! Por favor, realize o credenciamento antes de cadastrar sua palestra.</p>
      <p><a href="cadastro-docente.php">Credenciamento Docente</a></p>
      <p><a href="cadastro-docente-pos.php">Credenciamento Docente Pós Graduação</a></p>
      <div class="span-container">
        <span>Precisa de ajuda? Entre em contato conosco através do nosso email: credenciamento@esesp.es.gov.br</span>
      </div>
    </section>

    <form id="palestraForm" class="needs-validation" enctype="multipart/form-data" novalidate style="display: none;">
      <input type="hidden" id="teacher_id" name="teacher_id" value="">
      <input type="hidden" id="teacher_type" name="teacher_type" value="">
      
      <section class="form-section">
        <h5 class="form-subtitle">Informações Gerais</h5>
        <div class="row">
          <div class="did-floating-label-content col-12">
            <input name="theme" id="theme" class="did-floating-input form-control" type="text" placeholder=" " required/>
            <label for="theme" class="did-floating-label">Tema*</label>
            <div class="invalid-feedback">Informe o tema da palestra</div>
          </div>
        </div>
        <div class="row">
          <div class="did-floating-label-content col-12">
            <input name="goal" id="goal" class="did-floating-input form-control" type="text" placeholder=" " required />
            <label for="goal" class="did-floating-label">Objetivo*</label>
            <div class="invalid-feedback">Informe o objetivo</div>
          </div>
        </div>
        <div class="row">
          <div class="did-floating-label-content col-12 ">
            <input name="target" id="target" class="did-floating-input form-control" type="text" placeholder=" " required />
            <label for="target" class="did-floating-label">Público Alvo*</label>
            <div class="invalid-feedback">Informe o público alvo</div>
          </div>
        </div>

        <div class="row">
          <div class="did-floating-label-content col-12">   
            <textarea
              class="did-floating-input"
              name="content"
              id="content"
              style="height: 200px; padding-top: 10px;"
              required
              placeholder=" "
            ></textarea>
            <label class="did-floating-label">Conteúdo*</label>
            <div class="invalid-feedback">Informe o conteúdo</div>
          </div>
        </div>
      </section>     

      <section class="form-section">
        <h5 class="form-subtitle">Formatação</h5>
        <div class="row">
          <div class="did-floating-label-content col-12 col-md-6">
            <input name="duration" id="duration" class="did-floating-input form-control" type="text" placeholder=" " required/>
            <label for="duration" class="did-floating-label">Tempo estimado*</label>
            <div class="invalid-feedback">Informe o tempo estimado aproximada da palestra</div>
          </div>
          <div class="col-12 col-md-6">
            <div class="did-floating-label-content">
                <select
                  name="format"
                  class="did-floating-select form-select"
                  required
                >
                  <option value=""></option>
                  <option value="Presencial">Presencial</option>
                  <option value="Online">Online</option>
                </select>
                <label class="did-floating-label"
                  >Modelo*</label
                >
                <div class="invalid-feedback">Informe o modelo de apresentação</div>
              </div>
          </div>
        </div>
        
        <div class="row">
          <div class="did-floating-label-content col-12">   
            <textarea
              class="did-floating-input"
              name="infos"
              id="infos"
              style="height: 200px; padding-top: 10px;"              
              placeholder=" "
            ></textarea>
            <label class="did-floating-label">Informações Adicionais</label>
          </div>
        </div>
      </section>     

      <input type="submit" class="btnF form-btn" name="send-form" value="Enviar"></input>
    </form>
    
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
      <div class="loading-content">
        <div class="spinner"></div>
        <p>Enviando formulário...</p>
        <p class="upload-progress">0%</p>
      </div>
    </div>
  </div>

</main>

  
<?php 
include_once('../components/footer.php');
?>



<script src="../scripts/formScript.js"></script>
<script type="module">
  import {searchCpf} from '../scripts/palestraScript.js';
  document.addEventListener('DOMContentLoaded', function() {
    searchCpf();
    
  });

</script>











