
<?php
  include_once('../components/header.php');
?>
<main>
  <div class="container">
    <h1 class="main-title">Credenciamento para Demandas Específicas</h1>

    <form id="spcDemandForm" class="needs-validation" enctype="multipart/form-data" novalidate>
      <section class="form-section">
        <h5 class="form-subtitle">Dados Pessoais</h5>
        <div class="row">
          <div class="did-floating-label-content col-12">
            <input name="name" id="name" class="did-floating-input form-control" type="text" placeholder=" " required/>
            <label for="name" class="did-floating-label">Nome*</label>
            <div class="invalid-feedback">Informe seu nome</div>
          </div>
        </div>
        <div class="row">
          <div class="did-floating-label-content col-12 col-md-6">
            <input name="rg" id="rg" class="did-floating-input form-control" type="text" placeholder=" " maxlength="12" required />
            <label for="rg" class="did-floating-label">Documento de Identidade*</label>
            <div class="invalid-feedback">Informe um número de documento de identidade</div>
          </div>
          <div class="did-floating-label-content col-6 col-md-4">
            <input name="rgEmissor" id="rgEmissor" class="did-floating-input form-control" type="text" placeholder=" "  required />
            <label for="rgEmissor" class="did-floating-label">Órgão emissor*</label>
            <div class="invalid-feedback">Informe o órgão emissor</div>
          </div>
          <div class="did-floating-label-content col-6 col-md-2">
            <input name="rgUf" id="rgUf" class="did-floating-input form-control" type="text" placeholder=" " maxlength="2" required />
            <label for="rgUf" class="did-floating-label">UF*</label>
            <div class="invalid-feedback">Informe a UF</div>
          </div>
        </div>
        <div class="row">
          <div class="did-floating-label-content col-6">
            <input name="cpf" id="cpf" class="did-floating-input form-control" type="text" placeholder=" " required />
            <label for="cpf" class="did-floating-label">CPF*</label>
            <div class="invalid-feedback">Informe um cpf válido</div>
          </div>
          <div class="did-floating-label-content col-6">
            <input name="email" id="email" class="did-floating-input form-control" type="email" placeholder=" " required />
            <label for="email" class="did-floating-label">Email*</label>
            <div class="invalid-feedback">Informe um email válido</div>
          </div>
        </div>
        <div class="row">
          <div class="did-floating-label-content col-12 col-md-6">
            <input name="address" id="address" class="did-floating-input form-control" type="text" placeholder=" " required />
            <label for="address" class="did-floating-label">Endereço*</label>
            <div class="invalid-feedback">Informe seu endereço</div>
          </div>
          <div class="did-floating-label-content col-6 col-md-2">
            <input name="addNumber" id="addNumber" class="did-floating-input form-control" type="text" placeholder=" " required />
            <label for="addNumber" class="did-floating-label">Nº*</label>
            <div class="invalid-feedback">Informe o número</div>
          </div>
          <div class="did-floating-label-content col-6 col-md-4">
            <input name="addComplement" id="addComplement" class="did-floating-input form-control" type="text" placeholder=" " />
            <label for="addComplement" class="did-floating-label">Complemento</label>
          </div>
        </div>
        <div class="row">
          <div class="did-floating-label-content col-12 col-md-5">
            <input name="neighborhood" id="neighborhood" class="did-floating-input form-control" type="text" placeholder=" " required />
            <label for="neighborhood" class="did-floating-label">Bairro*</label>
            <div class="invalid-feedback">Informe seu bairro</div>
          </div>
          <div class="did-floating-label-content col-6 col-md-5">
            <input name="city" id="city" class="did-floating-input form-control" type="text" placeholder=" " required />
            <label for="city" class="did-floating-label">Cidade*</label>
            <div class="invalid-feedback">Informe sua cidade</div>
          </div>
          <div class="did-floating-label-content col-6 col-md-2">
            <input name="state" id="state" class="did-floating-input form-control" type="text" placeholder=" " maxlength="2" required />
            <label for="state" class="did-floating-label">Estado*</label>
            <div class="invalid-feedback">Informe seu estado</div>
          </div>
        </div>
        <div class="row">
          <div class="did-floating-label-content col-6">
            <input name="zipCode" id="zipCode" class="did-floating-input form-control" type="text" placeholder=" " required />
            <label for="zipCode" class="did-floating-label">CEP*</label>
            <div class="invalid-feedback">Informe seu CEP</div>
          </div>
          <div class="did-floating-label-content col-6">
            <input name="phone" id="phone" class="did-floating-input form-control" type="text" placeholder=" " required />
            <label for="phone" class="did-floating-label">Telefone*</label>
            <div class="invalid-feedback">Informe um telefone</div>
          </div>
        </div>
      </section>

      <section id="scholarship-section" class="form-section">
        <h5 class="form-subtitle">Habilitação</h5>
        <div class="scholarship-container" id="scholarshipContainer">
          <div class="scholarship-content" id="scholarshipContent">
            <div class="row">
              <div class="did-floating-label-content">
                <select
                  name="degree_0"
                  class="did-floating-select form-select"
                  required
                >
                  <option value=""></option>
                  <option value="Graduação">Graduação</option>
                  <option value="Especialidade">Especialização</option>
                  <option value="Mestrado">Mestrado</option>
                  <option value="Doutorado">Doutorado</option>
                </select>
                <label class="did-floating-label"
                  >Escolaridade*</label
                >
                <div class="invalid-feedback">Informe seu grau de escolaridade</div>
              </div>
            </div>
            <div class="scholarship-details">
              <div class="row">
                <div class="did-floating-label-content col-12">
                  <input
                    class="did-floating-input"
                    type="text"
                    name="courseName_0"
                    required
                    placeholder=" "
                  />
                  <label class="did-floating-label">Nome do curso*</label>
                  <div class="invalid-feedback">Informe o nome do curso</div>
                </div>
              </div>
              <div class="row">
                <div class="did-floating-label-content col-12">
                  <input
                    class="did-floating-input"
                    type="text"
                    name="institution_0"
                    required
                    placeholder=" "
                  />
                  <label class="did-floating-label">Instituição*</label>
                  <div class="invalid-feedback">Informe a instituição</div>
                </div>
              </div>
            </div>
          </div>
          <button
            class="btnF add-section-btn"
            type="button"
            onclick="cloneSection('scholarship')"
            id="scholarshipBtn"
          >
            Adicionar mais
          </button>
        </div>
      </section>

      <section class="form-section">
        <h5 class="form-subtitle">Cursos</h5>
        <div class="disciplines-container" id="disciplinesContainer">
          <div class="disciplines-content" id="disciplinesContent">

            <div class="row">
              <div class="did-floating-label-content">
                <select
                  name="institution_0"
                  id="institution_0"
                  class="did-floating-select form-select institution-select"
                  onchange="loadSpcCourses(this)"
                  required
                >
                </select>
                <option value=""></option>
                <label for="eixo_0" class="did-floating-label"
                  >Órgão*</label
                >
              </div>
            </div>

            <div class="row">
              <div class="did-floating-label-content">
                <select
                  name="course_0"
                  id="course_0"
                  class="did-floating-select form-select"
                  required
                >
                </select>
                <option value=""></option>
                <label for="course_0" class="did-floating-label"
                  >Curso*</label
                >
              </div>
            </div>


          </div>
          <button
            class="btnF add-section-btn"
            onclick="cloneSection('disciplines')"
            type="button"
            id="disciplinesBtn"
            >
            Adicionar mais
          </button>
        </div>
      </section>

      <section class="form-section">
        <h5 class="form-subtitle">Categoria, Atividades e Serviços</h5>
        <div class="checkbox-group">
          <p class="control-label col-md-12" for="position" style="font-weight:500">Selecione as categorias nas quais você deseja se credenciar</p>
          <div class="col-md-6">
            <input type="checkbox" name="position[]" value="1" id="position1"/> <label for="position1">Docente</label><br>
            <input type="checkbox" name="position[]" value="2" id="position2"/> <label for="position2"> Docente Conteudista</label><br>
            <input type="checkbox" name="position[]" value="3" id="position3"/> <label for="position3">Docente Assistente</label> <br>
            <input type="checkbox" name="position[]" value="4" id="position4"/> <label for="position4">Coordenador Técnico</label> <br>
            <input type="checkbox" name="position[]" value="5" id="position5"/> <label for="position5">Conferencista/Palestrante</label> <br>
            <input type="checkbox" name="position[]" value="6" id="position6"/> <label for="position6">Painelista/Debatedor</label> <br>
            <input type="checkbox" name="position[]" value="7" id="position7"/> <label for="position7">Moderador</label> <br>
            <input type="checkbox" name="position[]" value="8" id="position8"/> <label for="position8">Reunião Técnica</label> <br>
            <input type="checkbox" name="position[]" value="9" id="position9"/> <label for="position9">Assessoramento Técnico</label> <br>
            <input type="checkbox" name="position[]" value="10" id="position10"/> <label for="position10">Revisão de Texto</label> <br>
            <input type="checkbox" name="position[]" value="11" id="position11"/> <label for="position11">Entrevista</label>
          </div>
        </div>
      </section>

      <section class="form-section">
        <h5 class="form-subtitle">Documentos</h5>
        <p>Envie as documentações pessoais e habilitações requeridas no Edital</p>
        <div class="mb-4" id="fileContainer">
          <input class="form-control" type="file" id="documents" name="documents" accept="application/pdf" required>
          <div class="invalid-feedback">Documentos de comprovação obrigatório</div>
          <div class="form-text">*Apenas documentos unificados em formato de pdf serão aceitos</div>
        </div>
      </section>

      <section class="form-section">
        <h5 class="form-subtitle">Informações Adicionais</h5>
        <div >
          <p >É portador de necessidades especiais?</p>
          <div class="form-check form-check-inline radio-div">
            <input class="form-check-input" type="radio" name="specialNeeds" id="specialNeedsYes" value="yes" onclick="showSpecialNeeds(true)" required>
            <span class="check"></span>
            <label class="form-check-label terms-label" for="specialNeedsYes" >Sim</label>
          </div>
          <div class="form-check form-check-inline radio-div">
            <input class="form-check-input" type="radio" name="specialNeeds" id="specialNeedsNo" value="no" onclick="showSpecialNeeds(false)" required>
            <span class="check"></span>
            <label class="form-check-label terms-label" for="specialNeedsNo">Não</label>
          </div>
          <div class="did-floating-label-content" style="display:none; margin-top: 10px" id="specialNeedsDetailsContainer">
            <input name="specialNeedsDetails" id="specialNeedsDetails" class="did-floating-input form-control" type="text" placeholder=" " required/>
            <label for="specialNeedsDetails" class="did-floating-label">Especifique*</label>
            <div class="invalid-feedback">Você deve especificar</div>
          </div>
        </div>

      </section>

      <div>
        <div class="box">
          <input id="terms" name="terms" type="checkbox" required>
          <span class="check"></span>
          <label for="terms" class="terms-label">Autorizo o tratamento dos meus dados pessoais exclusivamente para os fins do presente edital.</label>
          <div class="invalid-feedback">O candidato deve autorizar o envio de dados</div>
        </div>
      </div>
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
<script type="module">
  import {handleSpcDemandSubmission} from '../scripts/main.js'
  document.addEventListener("DOMContentLoaded", () => {
  loadSpcInstitutions();
  handleSpcDemandSubmission();

});


</script>







