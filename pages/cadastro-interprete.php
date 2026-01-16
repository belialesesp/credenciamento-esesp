<?php
  require_once("../components/header.php");
?>

<main>
  <div class="container">
    <h1 class="main-title">Credenciamento Intérprete de Libras</h1>

    <form class="needs-validation" enctype="multipart/form-data" id="interpreterForm" novalidate>
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
            <label for="rg" class="did-floating-label">Documento de identidade*</label>
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
        <div class="row">
          <div class="did-floating-label-content">
            <select
              name="scholarship"
              class="did-floating-select form-select"
              required
            >
              <option value=""></option>
              <option value="Médio">Ensino médio completo</option>
              <option value="Superior incompleto">Ensino superior incompleto</option>
              <option value="Superior completo">Ensino superior completo</option>
            </select>
            <label class="did-floating-label"
              >Escolaridade*</label
            >
            <div class="invalid-feedback">Informe seu grau de escolaridade</div>
          </div>
        </div>
      </section>

      <!-- Comprovação de Qualificação Técnica -->
      <div class="document-subsection">
        <h6 class="subsection-title">
          <i class="fas fa-graduation-cap me-2"></i>
          Comprovação de Qualificação Técnica
        </h6>

        <div class="file-upload-group">
          <label for="formacao_escolar" class="file-label">
            Formação Escolar <span class="required-indicator">*</span>
          </label>
          <input class="form-control" type="file" id="formacao_escolar"
            name="formacao_escolar" accept="application/pdf" required>
          <div class="invalid-feedback">Comprovação de formação escolar é obrigatória</div>
          <div class="form-text">Diploma, certificado ou declaração de conclusão</div>
        </div>

        <div class="file-upload-group">
          <label for="experiencia_profissional" class="file-label">
            Comprovante de experiência profissional na área de atuação <span class="required-indicator">*</span>
          </label>
          <input class="form-control" type="file" id="experiencia_profissional"
            name="experiencia_profissional" accept="application/pdf" required>
          <div class="invalid-feedback">Comprovante de experiência profissional é obrigatório</div>
          <div class="form-text">Carteira de trabalho, declaração ou contrato</div>
        </div>
      </div>
      <section class="form-section">
        <h5 class="form-subtitle">Informações Adicionais</h5>        
        <div >
          <h6 >É portador de necessidades especiais?</h6>
          <div class="form-check form-check-inline radio-div">
            <input class="form-check-input" type="radio" name="specialNeeds" id="specialNeedsYes" value="yes" onclick="showSpecialNeeds(true)" required>
            <span class="check"></span>
            <label class="form-check-label" for="specialNeedsYes">Sim</label>
          </div>
          <div class="form-check form-check-inline radio-div">
            <input class="form-check-input" type="radio" name="specialNeeds" id="specialNeedsNo" value="no" onclick="showSpecialNeeds(false)" required>
            <span class="check"></span>
            <label class="form-check-label" for="specialNeedsNo">Não</label>
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
require_once("../components/footer.php");
?>

<script src="../scripts/formScript.js"></script>
<script type="module">
  import { handleInterpreterSubmission } from '../scripts/main.js';
  document.addEventListener("DOMContentLoaded", () => {
    handleInterpreterSubmission();
  });
 
</script>