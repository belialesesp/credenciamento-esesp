<?php

include_once('../components/header.php');

?>

<style>
  :root {
    --primary-color: #1e4c82;
    --secondary-color: #fca934;
    --success-color: #28a745;
    --danger-color: #dc3545;
    --light-bg: #f8f9fa;
    --border-color: #dee2e6;
  }

  body {
    font-family: 'Poppins', sans-serif;
    background-color: var(--light-bg);
    color: #333;
  }

  .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
  }

  .main-title {
    color: var(--primary-color);
    font-size: 2.5rem;
    font-weight: 700;
    text-align: center;
    margin-bottom: 2rem;
    text-transform: uppercase;
  }

  /* Tabs Styling */
  .nav-tabs {
    border-bottom: 3px solid var(--primary-color);
    margin-bottom: 2rem;
    flex-wrap: wrap;
  }

  .nav-tabs .nav-link {
    color: var(--primary-color);
    background-color: #fff;
    border: 2px solid transparent;
    border-bottom: none;
    font-weight: 500;
    padding: 0.75rem 1.5rem;
    margin-right: 0.5rem;
    margin-bottom: -3px;
    transition: all 0.3s ease;
  }

  .nav-tabs .nav-link:hover {
    border-color: var(--border-color);
    background-color: var(--light-bg);
  }

  .nav-tabs .nav-link.active {
    color: #fff;
    background-color: var(--primary-color);
    border-color: var(--primary-color);
  }

  /* Form Sections */
  .form-section {
    background: #fff;
    border-radius: 8px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  .form-subtitle {
    color: var(--primary-color);
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--secondary-color);
  }

  /* Floating Labels */
  .did-floating-label-content {
    position: relative;
    margin-bottom: 1.5rem;
  }

  .did-floating-input,
  .did-floating-select {
    font-size: 1rem;
    display: block;
    width: 100%;
    height: calc(2.5rem + 2px);
    padding: 0.375rem 0.75rem;
    background-color: #fff;
    border: 1px solid var(--border-color);
    border-radius: 0.25rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
  }

  .did-floating-input:focus,
  .did-floating-select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(30, 76, 130, 0.25);
  }

  .did-floating-label {
    color: #6c757d;
    font-size: 0.875rem;
    font-weight: normal;
    position: absolute;
    pointer-events: none;
    left: 0.75rem;
    top: 0.5rem;
    transition: all 0.2s ease;
    background-color: #fff;
    padding: 0 0.25rem;
  }

  .did-floating-input:focus~.did-floating-label,
  .did-floating-input:not(:placeholder-shown)~.did-floating-label,
  .did-floating-select:focus~.did-floating-label,
  .did-floating-select:valid~.did-floating-label {
    top: -0.5rem;
    font-size: 0.75rem;
    color: var(--primary-color);
  }

  /* Checkboxes and Radios */
  .checkbox-group,
  .radio-group {
    padding: 1rem;
    background-color: var(--light-bg);
    border-radius: 0.25rem;
  }

  .form-check {
    margin-bottom: 0.75rem;
  }

  .form-check-input {
    margin-top: 0.25rem;
  }

  .form-check-label {
    margin-left: 0.5rem;
    cursor: pointer;
  }

  /* Document Upload Section */
  .document-subsection {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background-color: var(--light-bg);
    border-radius: 0.5rem;
  }

  .subsection-title {
    color: var(--primary-color);
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
  }

  .file-upload-group {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background-color: #fff;
    border: 1px solid var(--border-color);
    border-radius: 0.25rem;
  }

  .file-label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: var(--primary-color);
  }

  .required-indicator {
    color: var(--danger-color);
  }

  .optional-label {
    color: #6c757d;
    font-size: 0.875rem;
    font-weight: normal;
  }

  /* Buttons */
  .btnF {
    background-color: var(--secondary-color);
    color: #fff;
    border: none;
    padding: 0.75rem 2rem;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .btnF:hover {
    background-color: #e89820;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  }

  .add-section-btn {
    background-color: var(--primary-color);
    margin-top: 1rem;
  }

  .add-section-btn:hover {
    background-color: #16365a;
  }

  .form-btn {
    display: block;
    width: 100%;
    max-width: 300px;
    margin: 2rem auto 0;
    font-size: 1.25rem;
    padding: 1rem;
  }

  /* Terms Checkbox */
  .terms-section {
    background-color: #fff;
    padding: 2rem;
    border-radius: 0.5rem;
    margin-bottom: 2rem;
  }

  .box {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1rem;
  }

  .box input[type="checkbox"] {
    margin-right: 1rem;
    margin-top: 0.25rem;
  }

  .terms-label {
    flex: 1;
    line-height: 1.6;
  }

  /* Clone Sections */
  .clone-section {
    border: 2px dashed var(--border-color);
    padding: 1.5rem;
    margin-bottom: 1rem;
    border-radius: 0.5rem;
    position: relative;
  }

  .remove-section {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background-color: var(--danger-color);
    color: #fff;
    border: none;
    padding: 0.25rem 0.75rem;
    border-radius: 0.25rem;
    cursor: pointer;
  }

  /* Validation Feedback */
  .invalid-feedback {
    display: none;
    color: var(--danger-color);
    font-size: 0.875rem;
    margin-top: 0.25rem;
  }

  .was-validated .form-control:invalid~.invalid-feedback,
  .was-validated .form-select:invalid~.invalid-feedback {
    display: block;
  }

  .was-validated .form-control:invalid,
  .was-validated .form-select:invalid {
    border-color: var(--danger-color);
  }

  /* Loading Overlay */
  .loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: center;
  }

  .loading-content {
    background-color: #fff;
    padding: 2rem;
    border-radius: 0.5rem;
    text-align: center;
  }

  .spinner {
    border: 4px solid var(--border-color);
    border-top: 4px solid var(--primary-color);
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
  }

  @keyframes spin {
    0% {
      transform: rotate(0deg);
    }

    100% {
      transform: rotate(360deg);
    }
  }

  /* Responsive Design */
  @media (max-width: 768px) {
    .nav-tabs .nav-link {
      padding: 0.5rem 1rem;
      font-size: 0.875rem;
    }

    .main-title {
      font-size: 1.75rem;
    }

    .form-section {
      padding: 1rem;
    }
  }
</style>
<div class="container">
  <h1 class="main-title">Credenciamento ESESP</h1>

  <!-- Navigation Tabs -->
  <ul class="nav nav-tabs" id="cadastroTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="cadastro-tab" data-bs-toggle="tab" data-bs-target="#cadastro" type="button" role="tab">
        <i class="fas fa-chalkboard-teacher"></i> Cadastro
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="docente-tab" data-bs-toggle="tab" data-bs-target="#docente" type="button" role="tab">
        <i class="fas fa-chalkboard-teacher"></i> Docente
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="docente-pos-tab" data-bs-toggle="tab" data-bs-target="#docente-pos" type="button" role="tab">
        <i class="fas fa-graduation-cap"></i> Docente Pós-Graduação
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="interprete-tab" data-bs-toggle="tab" data-bs-target="#interprete" type="button" role="tab">
        <i class="fas fa-hands"></i> Intérprete de Libras
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tecnico-tab" data-bs-toggle="tab" data-bs-target="#tecnico" type="button" role="tab">
        <i class="fas fa-tools"></i> Apoio Técnico
      </button>
    </li>
  </ul>

  <!-- Tab Content -->
  <div class="tab-content" id="cadastroTabContent">
    <!-- Common Tab -->
    <div class="tab-pane fade show active" id="cadastro" role="tabpanel">
      <form id="docenteForm" class="needs-validation" enctype="multipart/form-data" novalidate>
        <!-- Personal Data Section (Common) -->
        <section class="form-section">
          <h5 class="form-subtitle">Dados Pessoais</h5>
          <div class="row">
            <div class="did-floating-label-content col-12">
              <input name="name" class="did-floating-input form-control" type="text" placeholder=" " required />
              <label class="did-floating-label">Nome*</label>
              <div class="invalid-feedback">Informe seu nome</div>
            </div>
          </div>
          <div class="row">
            <div class="did-floating-label-content col-12 col-md-6">
              <input name="rg" class="did-floating-input form-control" type="text" placeholder=" " maxlength="12" required />
              <label class="did-floating-label">Documento de Identidade*</label>
              <div class="invalid-feedback">Informe um número de documento de identidade</div>
            </div>
            <div class="did-floating-label-content col-6 col-md-4">
              <input name="rgEmissor" class="did-floating-input form-control" type="text" placeholder=" " required />
              <label class="did-floating-label">Órgão emissor*</label>
              <div class="invalid-feedback">Informe o órgão emissor</div>
            </div>
            <div class="did-floating-label-content col-6 col-md-2">
              <input name="rgUf" class="did-floating-input form-control" type="text" placeholder=" " maxlength="2" required />
              <label class="did-floating-label">UF*</label>
              <div class="invalid-feedback">Informe a UF</div>
            </div>
          </div>
          <div class="row">
            <div class="did-floating-label-content col-6">
              <input name="cpf" class="did-floating-input form-control" type="text" placeholder=" " required />
              <label class="did-floating-label">CPF*</label>
              <div class="invalid-feedback">Informe um CPF válido</div>
            </div>
            <div class="did-floating-label-content col-6">
              <input name="email" class="did-floating-input form-control" type="email" placeholder=" " required />
              <label class="did-floating-label">Email*</label>
              <div class="invalid-feedback">Informe um email válido</div>
            </div>
          </div>
          <div class="row">
            <div class="did-floating-label-content col-12 col-md-6">
              <input name="address" class="did-floating-input form-control" type="text" placeholder=" " required />
              <label class="did-floating-label">Endereço*</label>
              <div class="invalid-feedback">Informe seu endereço</div>
            </div>
            <div class="did-floating-label-content col-6 col-md-2">
              <input name="addNumber" class="did-floating-input form-control" type="text" placeholder=" " required />
              <label class="did-floating-label">Nº*</label>
              <div class="invalid-feedback">Informe o número</div>
            </div>
            <div class="did-floating-label-content col-6 col-md-4">
              <input name="addComplement" class="did-floating-input form-control" type="text" placeholder=" " />
              <label class="did-floating-label">Complemento</label>
            </div>
          </div>
          <div class="row">
            <div class="did-floating-label-content col-12 col-md-5">
              <input name="neighborhood" class="did-floating-input form-control" type="text" placeholder=" " required />
              <label class="did-floating-label">Bairro*</label>
              <div class="invalid-feedback">Informe seu bairro</div>
            </div>
            <div class="did-floating-label-content col-6 col-md-5">
              <input name="city" class="did-floating-input form-control" type="text" placeholder=" " required />
              <label class="did-floating-label">Cidade*</label>
              <div class="invalid-feedback">Informe sua cidade</div>
            </div>
            <div class="did-floating-label-content col-6 col-md-2">
              <input name="state" class="did-floating-input form-control" type="text" placeholder=" " maxlength="2" required />
              <label class="did-floating-label">Estado*</label>
              <div class="invalid-feedback">Informe seu estado</div>
            </div>
          </div>
          <div class="row">
            <div class="did-floating-label-content col-6">
              <input name="zipCode" class="did-floating-input form-control" type="text" placeholder=" " required />
              <label class="did-floating-label">CEP*</label>
              <div class="invalid-feedback">Informe seu CEP</div>
            </div>
            <div class="did-floating-label-content col-6">
              <input name="phone" class="did-floating-input form-control" type="text" placeholder=" " required />
              <label class="did-floating-label">Telefone*</label>
              <div class="invalid-feedback">Informe um telefone</div>
            </div>
          </div>
        </section>

        <!-- Professional Data Section -->
        <section class="form-section">
          <h5 class="form-subtitle">Dados Profissionais</h5>
          <div class="row">
            <div class="did-floating-label-content col-12">
              <select name="scholarship" class="did-floating-select form-select" required>
                <option value=""></option>
                <option value="Superior completo">Superior completo</option>
                <option value="Pós-graduação">Pós-graduação</option>
                <option value="Mestrado">Mestrado</option>
                <option value="Doutorado">Doutorado</option>
              </select>
              <label class="did-floating-label">Escolaridade*</label>
              <div class="invalid-feedback">Informe seu grau de escolaridade</div>
            </div>
          </div>
          <div id="education-sections">
            <div class="clone-section">
              <div class="row">
                <div class="did-floating-label-content col-12 col-md-6">
                  <input name="course[]" class="did-floating-input form-control" type="text" placeholder=" " required />
                  <label class="did-floating-label">Curso de Formação*</label>
                  <div class="invalid-feedback">Informe o curso</div>
                </div>
                <div class="did-floating-label-content col-12 col-md-6">
                  <input name="institution[]" class="did-floating-input form-control" type="text" placeholder=" " required />
                  <label class="did-floating-label">Instituição*</label>
                  <div class="invalid-feedback">Informe a instituição</div>
                </div>
              </div>
            </div>
          </div>
          <button class="btnF add-section-btn" type="button" onclick="addEducationSection()">
            <i class="fas fa-plus"></i> Adicionar mais
          </button>
        </section>

        <!-- Categories Section -->
        <section class="form-section">
          <h5 class="form-subtitle">Categoria, Atividades e Serviços</h5>
          <div class="checkbox-group">
            <p style="font-weight:500">Selecione as categorias nas quais você deseja se credenciar:</p>
            <div class="form-check">
              <input type="checkbox" class="form-check-input" name="position[]" value="1" id="docente-pos1">
              <label class="form-check-label" for="docente-pos1">Docente</label>
            </div>
            <div class="form-check">
              <input type="checkbox" class="form-check-input" name="position[]" value="2" id="docente-pos2">
              <label class="form-check-label" for="docente-pos2">Docente Conteudista</label>
            </div>
            <div class="form-check">
              <input type="checkbox" class="form-check-input" name="position[]" value="3" id="docente-pos3">
              <label class="form-check-label" for="docente-pos3">Docente Assistente</label>
            </div>
            <div class="form-check">
              <input type="checkbox" class="form-check-input" name="position[]" value="4" id="docente-pos4">
              <label class="form-check-label" for="docente-pos4">Coordenador Técnico</label>
            </div>
            <div class="form-check">
              <input type="checkbox" class="form-check-input" name="position[]" value="5" id="docente-pos5">
              <label class="form-check-label" for="docente-pos5">Conferencista/Palestrante</label>
            </div>
            <div class="form-check">
              <input type="checkbox" class="form-check-input" name="position[]" value="6" id="docente-pos6">
              <label class="form-check-label" for="docente-pos6">Painelista/Debatedor</label>
            </div>
            <div class="form-check">
              <input type="checkbox" class="form-check-input" name="position[]" value="7" id="docente-pos7">
              <label class="form-check-label" for="docente-pos7">Moderador</label>
            </div>
            <div class="form-check">
              <input type="checkbox" class="form-check-input" name="position[]" value="8" id="docente-pos8">
              <label class="form-check-label" for="docente-pos8">Reunião Técnica</label>
            </div>
            <div class="form-check">
              <input type="checkbox" class="form-check-input" name="position[]" value="9" id="docente-pos9">
              <label class="form-check-label" for="docente-pos9">Assessoramento Técnico</label>
            </div>
            <div class="form-check">
              <input type="checkbox" class="form-check-input" name="position[]" value="10" id="docente-pos10">
              <label class="form-check-label" for="docente-pos10">Revisão de Texto</label>
            </div>
            <div class="form-check">
              <input type="checkbox" class="form-check-input" name="position[]" value="11" id="docente-pos11">
              <label class="form-check-label" for="docente-pos11">Entrevista</label>
            </div>
          </div>
        </section>

        <!-- Documents Section with Separate Fields -->
        <section class="form-section">
          <h5 class="form-subtitle">
            <i class="fas fa-file-upload me-2"></i>
            Documentos
          </h5>
          <p class="text-muted mb-4">
            Envie as documentações pessoais e habilitações requeridas no Edital.
            Cada documento deve ser enviado separadamente em formato PDF.
          </p>

          <!-- Personal Documents -->
          <div class="document-subsection">
            <h6 class="subsection-title">
              <i class="fas fa-user me-2"></i>
              Documentos Pessoais
            </h6>

            <div class="file-upload-group">
              <label for="comprovante_residencia" class="file-label">
                Comprovante de Residência <span class="required-indicator">*</span>
              </label>
              <input class="form-control" type="file" id="comprovante_residencia"
                name="comprovante_residencia" accept="application/pdf" required>
              <div class="invalid-feedback">Comprovante de residência é obrigatório</div>
              <div class="form-text">Conta de luz, água, telefone ou similar dos últimos 3 meses</div>
            </div>

            <div class="file-upload-group">
              <label for="documento_identificacao" class="file-label">
                Documento de Identificação oficial com foto e CPF <span class="required-indicator">*</span>
              </label>
              <input class="form-control" type="file" id="documento_identificacao"
                name="documento_identificacao" accept="application/pdf" required>
              <div class="invalid-feedback">Documento de identificação é obrigatório</div>
              <div class="form-text">RG, CNH ou outro documento oficial com foto</div>
            </div>

            <div class="file-upload-group">
              <label for="titulo_eleitor" class="file-label">
                Título de Eleitor <span class="required-indicator">*</span>
              </label>
              <input class="form-control" type="file" id="titulo_eleitor"
                name="titulo_eleitor" accept="application/pdf" required>
              <div class="invalid-feedback">Título de eleitor é obrigatório</div>
            </div>

            <div class="file-upload-group">
              <label for="certificado_reservista" class="file-label">
                Certificado de Reservista <span class="optional-label">(se aplicável)</span>
              </label>
              <input class="form-control" type="file" id="certificado_reservista"
                name="certificado_reservista" accept="application/pdf">
              <div class="form-text">Obrigatório apenas para candidatos do sexo masculino</div>
            </div>
          </div>

          <!-- Technical Qualification Documents -->
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
                Comprovante de experiência profissional <span class="required-indicator">*</span>
              </label>
              <input class="form-control" type="file" id="experiencia_profissional"
                name="experiencia_profissional" accept="application/pdf" required>
              <div class="invalid-feedback">Comprovante de experiência é obrigatório</div>
              <div class="form-text">Carteira de trabalho, declaração ou contrato</div>
            </div>

            <div class="file-upload-group">
              <label for="publicacoes" class="file-label">
                Publicações <span class="optional-label">(se houver)</span>
              </label>
              <input class="form-control" type="file" id="publicacoes"
                name="publicacoes" accept="application/pdf">
              <div class="form-text">Artigos, livros ou outras publicações relevantes</div>
            </div>

            <div class="file-upload-group">
              <label for="certificados_cursos" class="file-label">
                Certificados de cursos <span class="optional-label">(se houver)</span>
              </label>
              <input class="form-control" type="file" id="certificados_cursos"
                name="certificados_cursos" accept="application/pdf">
              <div class="form-text">Cursos complementares, especializações ou capacitações</div>
            </div>
          </div>

          <!-- Economic-Financial Qualification -->
          <div class="document-subsection">
            <h6 class="subsection-title">
              <i class="fas fa-file-invoice-dollar me-2"></i>
              Qualificação Econômico-Financeira
            </h6>

            <div class="file-upload-group">
              <label for="pis_pasep" class="file-label">
                PIS/PASEP <span class="required-indicator">*</span>
              </label>
              <input class="form-control" type="file" id="pis_pasep"
                name="pis_pasep" accept="application/pdf" required>
              <div class="invalid-feedback">PIS/PASEP é obrigatório</div>
            </div>

            <div class="file-upload-group">
              <label for="protocolo_siades" class="file-label">
                Protocolo SIADES <span class="required-indicator">*</span>
              </label>
              <input class="form-control" type="file" id="protocolo_siades"
                name="protocolo_siades" accept="application/pdf" required>
              <div class="invalid-feedback">Protocolo SIADES é obrigatório</div>
            </div>
          </div>
        </section>

        <!-- Additional Information -->
        <section class="form-section">
          <h5 class="form-subtitle">Informações Adicionais</h5>
          <div class="radio-group">
            <p style="font-weight:500">É portador de necessidades especiais?</p>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="specialNeeds" id="specialNeedsYes1" value="yes" required>
              <label class="form-check-label" for="specialNeedsYes1">Sim</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="specialNeeds" id="specialNeedsNo1" value="no" required>
              <label class="form-check-label" for="specialNeedsNo1">Não</label>
            </div>
            <div class="did-floating-label-content" style="display:none; margin-top: 1rem" id="specialNeedsDetails1">
              <input name="specialNeedsDetails" class="did-floating-input form-control" type="text" placeholder=" " />
              <label class="did-floating-label">Especifique*</label>
              <div class="invalid-feedback">Você deve especificar</div>
            </div>
          </div>
        </section>

        <!-- Terms and Conditions -->
        <section class="terms-section">
          <div class="box">
            <input id="terms1" name="terms" type="checkbox" required>
            <label for="terms1" class="terms-label">Autorizo o tratamento dos meus dados pessoais exclusivamente para os fins do presente edital.</label>
            <div class="invalid-feedback">O candidato deve autorizar o envio de dados</div>
          </div>
          <div class="box">
            <input id="terms2" name="terms2" type="checkbox" required>
            <label for="terms2" class="terms-label">Declaro que não possuo vínculo de natureza técnica, comercial, econômica, financeira, trabalhista ou civil com dirigente do órgão ou da entidade credenciante ou com agente público que desempenhe função no processo de contratação ou atue na fiscalização ou na gestão do contrato, ou que deles seja cônjuge, companheiro ou parente em linha reta, colateral ou por afinidade, até o terceiro grau.</label>
            <div class="invalid-feedback">Campo obrigatório</div>
          </div>
        </section>

        <button type="submit" class="btnF form-btn">
          <i class="fas fa-paper-plane"></i> Enviar Formulário
        </button>
      </form>
    </div>

    <!-- Docente Pós-Graduação Tab -->
    <div class="tab-pane fade" id="docente-pos" role="tabpanel">
      <form id="docentePosForm" class="needs-validation" enctype="multipart/form-data" novalidate>
        <!-- Personal Data Section (same structure as above) -->
        <section class="form-section">
          <h5 class="form-subtitle">Dados Pessoais</h5>
          <!-- Copy same personal data fields from docente tab -->
          <div class="row">
            <div class="did-floating-label-content col-12">
              <input name="name" class="did-floating-input form-control" type="text" placeholder=" " required />
              <label class="did-floating-label">Nome*</label>
              <div class="invalid-feedback">Informe seu nome</div>
            </div>
          </div>
          <!-- Include all other personal data fields... -->
        </section>

        <!-- Professional Data with Disciplines -->
        <section class="form-section">
          <h5 class="form-subtitle">Dados Profissionais</h5>
          <div class="row">
            <div class="did-floating-label-content col-12">
              <select name="scholarship" class="did-floating-select form-select" required>
                <option value=""></option>
                <option value="Pós-graduação">Pós-graduação</option>
                <option value="Mestrado">Mestrado</option>
                <option value="Doutorado">Doutorado</option>
                <option value="Pós-doutorado">Pós-doutorado</option>
              </select>
              <label class="did-floating-label">Escolaridade*</label>
              <div class="invalid-feedback">Informe seu grau de escolaridade</div>
            </div>
          </div>

          <h6 class="subsection-title mt-4">Disciplinas</h6>
          <div id="disciplines-sections">
            <div class="clone-section">
              <div class="row">
                <div class="did-floating-label-content col-12">
                  <input name="discipline[]" class="did-floating-input form-control" type="text" placeholder=" " required />
                  <label class="did-floating-label">Nome da Disciplina*</label>
                  <div class="invalid-feedback">Informe a disciplina</div>
                </div>
              </div>
            </div>
          </div>
          <button class="btnF add-section-btn" type="button" onclick="addDisciplineSection()">
            <i class="fas fa-plus"></i> Adicionar mais disciplinas
          </button>
        </section>

        <!-- Categories for Pós-Graduação -->
        <section class="form-section">
          <h5 class="form-subtitle">Categoria, Atividades e Serviços</h5>
          <div class="checkbox-group">
            <p style="font-weight:500">Selecione as categorias nas quais você deseja se credenciar:</p>
            <div class="form-check">
              <input type="checkbox" class="form-check-input" name="position[]" value="1" id="pos-doc1">
              <label class="form-check-label" for="pos-doc1">Docente</label>
            </div>
            <div class="form-check">
              <input type="checkbox" class="form-check-input" name="position[]" value="2" id="pos-doc2">
              <label class="form-check-label" for="pos-doc2">Docente Conteudista</label>
            </div>
            <div class="form-check">
              <input type="checkbox" class="form-check-input" name="position[]" value="9" id="pos-doc3">
              <label class="form-check-label" for="pos-doc3">Assessoramento Técnico</label>
            </div>
          </div>
        </section>

        <!-- Documents Section (unified) -->
        <section class="form-section">
          <h5 class="form-subtitle">Documentos</h5>
          <p>Envie as documentações pessoais e habilitações requeridas no Edital</p>
          <div class="file-upload-group">
            <input class="form-control" type="file" id="documents_pos" name="documents" accept="application/pdf" required>
            <div class="invalid-feedback">Documentos de comprovação obrigatório</div>
            <div class="form-text">*Apenas documentos unificados em formato de pdf serão aceitos</div>
          </div>
        </section>

        <!-- Additional Information and Terms -->
        <!-- Copy from docente tab with unique IDs -->

        <button type="submit" class="btnF form-btn">
          <i class="fas fa-paper-plane"></i> Enviar Formulário
        </button>
      </form>
    </div>

    <!-- Intérprete de Libras Tab -->
    <div class="tab-pane fade" id="interprete" role="tabpanel">
      <form id="interpreteForm" class="needs-validation" enctype="multipart/form-data" novalidate>
        <!-- Personal Data Section -->
        <section class="form-section">
          <h5 class="form-subtitle">Dados Pessoais</h5>
          <!-- Include all personal data fields -->
        </section>

        <!-- Professional Data for Intérprete -->
        <section class="form-section">
          <h5 class="form-subtitle">Dados Profissionais</h5>
          <div class="row">
            <div class="did-floating-label-content col-12">
              <select name="scholarship" class="did-floating-select form-select" required>
                <option value=""></option>
                <option value="Médio">Ensino médio completo</option>
                <option value="Superior incompleto">Ensino superior incompleto</option>
                <option value="Superior completo">Ensino superior completo</option>
              </select>
              <label class="did-floating-label">Escolaridade*</label>
              <div class="invalid-feedback">Informe seu grau de escolaridade</div>
            </div>
          </div>
        </section>

        <!-- Documents Section -->
        <section class="form-section">
          <h5 class="form-subtitle">Documentos</h5>
          <p>Envie as documentações pessoais e habilitações requeridas no Edital</p>
          <div class="file-upload-group">
            <label for="documents_interprete" class="file-label">Documento de comprovação*</label>
            <input class="form-control" type="file" id="documents_interprete" name="documents" accept="application/pdf" required>
            <div class="invalid-feedback">Documentos de comprovação obrigatório</div>
            <div class="form-text">*Apenas documentos unificados em formato de pdf serão aceitos</div>
          </div>
        </section>

        <!-- Additional Information and Terms -->
        <!-- Copy structure with unique IDs -->

        <button type="submit" class="btnF form-btn">
          <i class="fas fa-paper-plane"></i> Enviar Formulário
        </button>
      </form>
    </div>

    <!-- Apoio Técnico Tab -->
    <div class="tab-pane fade" id="tecnico" role="tabpanel">
      <form id="tecnicoForm" class="needs-validation" enctype="multipart/form-data" novalidate>
        <!-- Personal Data Section -->
        <section class="form-section">
          <h5 class="form-subtitle">Dados Pessoais</h5>
          <!-- Include all personal data fields -->
        </section>

        <!-- Professional Data for Técnico -->
        <section class="form-section">
          <h5 class="form-subtitle">Dados Profissionais</h5>
          <div class="row">
            <div class="did-floating-label-content col-12">
              <select name="scholarship" class="did-floating-select form-select" required>
                <option value=""></option>
                <option value="Médio">Ensino médio completo</option>
                <option value="Superior incompleto">Ensino superior incompleto</option>
                <option value="Superior completo">Ensino superior completo</option>
              </select>
              <label class="did-floating-label">Escolaridade*</label>
              <div class="invalid-feedback">Informe seu grau de escolaridade</div>
            </div>
          </div>
        </section>

        <!-- Documents Section -->
        <section class="form-section">
          <h5 class="form-subtitle">Documentos</h5>
          <p>Envie as documentações pessoais e habilitações requeridas no Edital</p>
          <div class="file-upload-group">
            <label for="documents_tecnico" class="file-label">Documento de comprovação*</label>
            <input class="form-control" type="file" id="documents_tecnico" name="documents" accept="application/pdf" required>
            <div class="invalid-feedback">Documentos de comprovação obrigatório</div>
            <div class="form-text">*Apenas documentos unificados em formato de pdf serão aceitos</div>
          </div>
        </section>

        <!-- Additional Information and Terms -->
        <!-- Copy structure with unique IDs -->

        <button type="submit" class="btnF form-btn">
          <i class="fas fa-paper-plane"></i> Enviar Formulário
        </button>
      </form>
    </div>
  </div>

  <!-- Loading Overlay -->
  <div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="loading-content">
      <div class="spinner"></div>
      <p>Enviando formulário...</p>
      <p class="upload-progress">0%</p>
    </div>
  </div>
</div>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // Handle special needs radio buttons
  document.querySelectorAll('input[name="specialNeeds"]').forEach(radio => {
    radio.addEventListener('change', function() {
      const detailsContainer = this.closest('.radio-group').querySelector('[id^="specialNeedsDetails"]');
      if (detailsContainer) {
        detailsContainer.style.display = this.value === 'yes' ? 'block' : 'none';
        const input = detailsContainer.querySelector('input');
        if (this.value === 'yes') {
          input.setAttribute('required', '');
        } else {
          input.removeAttribute('required');
          input.value = '';
        }
      }
    });
  });

  // Add Education Section
  function addEducationSection() {
    const container = document.getElementById('education-sections');
    const sections = container.querySelectorAll('.clone-section');
    const newSection = sections[0].cloneNode(true);

    // Clear input values
    newSection.querySelectorAll('input').forEach(input => {
      input.value = '';
    });

    // Add remove button if it's not the first section
    if (sections.length > 0) {
      const removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.className = 'remove-section';
      removeBtn.innerHTML = '<i class="fas fa-times"></i> Remover';
      removeBtn.onclick = function() {
        newSection.remove();
      };
      newSection.appendChild(removeBtn);
    }

    container.appendChild(newSection);
  }

  // Add Discipline Section
  function addDisciplineSection() {
    const container = document.getElementById('disciplines-sections');
    const sections = container.querySelectorAll('.clone-section');
    const newSection = sections[0].cloneNode(true);

    // Clear input values
    newSection.querySelectorAll('input').forEach(input => {
      input.value = '';
    });

    // Add remove button
    if (sections.length > 0) {
      const removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.className = 'remove-section';
      removeBtn.innerHTML = '<i class="fas fa-times"></i> Remover';
      removeBtn.onclick = function() {
        newSection.remove();
      };
      newSection.appendChild(removeBtn);
    }

    container.appendChild(newSection);
  }

  // Form Validation
  document.querySelectorAll('.needs-validation').forEach(form => {
    form.addEventListener('submit', function(event) {
      event.preventDefault();
      event.stopPropagation();

      if (form.checkValidity()) {
        // Show loading overlay
        document.getElementById('loadingOverlay').style.display = 'flex';

        // Here you would normally submit the form
        // For demo purposes, we'll just hide the overlay after 2 seconds
        setTimeout(() => {
          document.getElementById('loadingOverlay').style.display = 'none';
          alert('Formulário enviado com sucesso!');
          form.reset();
          form.classList.remove('was-validated');
        }, 2000);
      }

      form.classList.add('was-validated');
    }, false);
  });

  // CPF Mask
  function applyCPFMask(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length > 11) value = value.slice(0, 11);

    if (value.length > 9) {
      value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    } else if (value.length > 6) {
      value = value.replace(/(\d{3})(\d{3})(\d{3})/, '$1.$2.$3');
    } else if (value.length > 3) {
      value = value.replace(/(\d{3})(\d{3})/, '$1.$2');
    }

    input.value = value;
  }

  // Phone Mask
  function applyPhoneMask(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length > 11) value = value.slice(0, 11);

    if (value.length > 6) {
      if (value.length === 11) {
        value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
      } else {
        value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
      }
    } else if (value.length > 2) {
      value = value.replace(/(\d{2})(\d+)/, '($1) $2');
    }

    input.value = value;
  }

  // CEP Mask
  function applyCEPMask(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length > 8) value = value.slice(0, 8);

    if (value.length > 5) {
      value = value.replace(/(\d{5})(\d{3})/, '$1-$2');
    }

    input.value = value;
  }

  // Apply masks to inputs
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[name="cpf"]').forEach(input => {
      input.addEventListener('input', () => applyCPFMask(input));
    });

    document.querySelectorAll('input[name="phone"]').forEach(input => {
      input.addEventListener('input', () => applyPhoneMask(input));
    });

    document.querySelectorAll('input[name="zipCode"]').forEach(input => {
      input.addEventListener('input', () => applyCEPMask(input));
    });
  });
</script>


<?php

include_once('../components/footer.php');

?>