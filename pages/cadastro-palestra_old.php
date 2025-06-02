
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
          <button class="btn btn-small">Docente</button>
          <button class="btn btn-small">Docente pós graduação</button>
        </div>
      </div>
      <div class="span-container">
        <span>Ainda não está credenciado? Clique <a href="">aqui</a> para fazer seu cadastro </span>
      </div>
    </section>     

    <form id="docenteForm" class="needs-validation" enctype="multipart/form-data" novalidate>
      <section class="form-section">
        <h5 class="form-subtitle">Informações Gerais</h5>
        <div class="row">
          <div class="did-floating-label-content col-12">
            <input name="name" id="name" class="did-floating-input form-control" type="text" placeholder=" " required/>
            <label for="name" class="did-floating-label">Tema*</label>
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
            <div class="form-check form-check-inline radio-div">
              <input class="form-check-input" type="radio" name="specialNeeds" id="specialNeedsYes" value="inPerson" required>
              <span class="check"></span>
              <label class="form-check-label terms-label" for="specialNeedsYes" >Presencial</label>
            </div>
            <div class="form-check form-check-inline radio-div">
              <input class="form-check-input" type="radio" name="specialNeeds" id="specialNeedsNo" value="online"  required>
              <span class="check"></span>
              <label class="form-check-label terms-label" for="specialNeedsNo">Online</label>
            </div>
          </div>
        </div>
        
        <div class="row">
          <div class="did-floating-label-content col-12">   
            <textarea
              class="did-floating-input"
              name="infos"
              style="height: 200px; padding-top: 10px;"              
              placeholder=" "
            ></textarea>
            <label class="did-floating-label">Informações Adicionais</label>
          </div>
        </div>


      </section>     


      <div>
        <div class="box box2">
          <input id="terms2" name="terms2" type="checkbox" required>
          <span class="check"></span>
          <label for="terms2" class="terms-label">Declaro que não possuo vínculo de natureza técnica, comercial, econômica, financeira, trabalhista ou civil com dirigente do órgão ou da entidade credenciante ou com agente público que desempenhe função no processo de contratação ou atue na fiscalização ou na gestão do contrato, ou que deles seja cônjuge, companheiro ou parente em linha reta, colateral ou por afinidade, até o terceiro grau.</label>
        </div>
        <div class="invalid-feedback">Campo obrigatório</div>
      </div>

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
<script>
  


</script>







