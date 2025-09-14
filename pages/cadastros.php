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

  /* Role Selection */
  .role-selector {
    background: var(--light-bg);
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 1rem;
  }

  .role-checkbox {
    display: flex;
    align-items: center;
    padding: 1rem;
    margin-bottom: 0.5rem;
    background: #fff;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .role-checkbox:hover {
    border-color: var(--primary-color);
    box-shadow: 0 2px 8px rgba(30, 76, 130, 0.1);
  }

  .role-checkbox.active {
    border-color: var(--primary-color);
    background: linear-gradient(135deg, rgba(30, 76, 130, 0.05) 0%, rgba(252, 169, 52, 0.05) 100%);
  }

  .role-checkbox input[type="checkbox"] {
    width: 20px;
    height: 20px;
    margin-right: 1rem;
    cursor: pointer;
  }

  .role-info {
    flex: 1;
  }

  .role-title {
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 0.25rem;
  }

  .role-description {
    font-size: 0.875rem;
    color: #6c757d;
  }

  /* Conditional Sections */
  .conditional-section {
    display: none;
    opacity: 0;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    margin-top: 1.5rem;
    padding: 1.5rem;
    background: #f0f7ff;
    border-left: 4px solid var(--primary-color);
    border-radius: 0 8px 8px 0;
  }

  .conditional-section.show {
    display: block;
    opacity: 1;
    transform: translateY(0);
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

  /* Categories */
  .category-group {
    padding: 1rem;
    background: #fff;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    margin-bottom: 1rem;
  }

  .category-title {
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 1rem;
    font-size: 1.1rem;
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

  /* Alert Box */
  .alert-info {
    background-color: #e7f3ff;
    border: 1px solid #b3d9ff;
    color: #004085;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
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

  /* Validation */
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

  .radio-div {
    position: relative;
    margin-right: 1rem;
  }

  .radio-div .form-check-input {
    cursor: pointer;
  }

  .radio-div .check {
    position: absolute;
    top: 50%;
    left: 0;
    transform: translateY(-50%);
    height: 20px;
    width: 20px;
    background-color: #fff;
    border: 2px solid var(--border-color);
    border-radius: 50%;
    pointer-events: none;
  }

  .radio-div .form-check-input:checked~.check {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
  }

  .radio-div .form-check-input:checked~.check:after {
    content: "";
    position: absolute;
    display: block;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: white;
  }

  .radio-div .terms-label {
    margin-left: 0.5rem;
    cursor: pointer;
  }

  #specialNeedsDetailsContainer {
    transition: opacity 0.3s ease;
  }
.docente-only-fields {
  transition: all 0.3s ease;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px dashed var(--border-color);
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
    .main-title {
      font-size: 1.75rem;
    }

    .form-section {
      padding: 1rem;
    }

    .role-checkbox {
      padding: 0.75rem;
    }
  }
</style>

<div class="container">
  <h1 class="main-title">Credenciamento ESESP</h1>

  <!-- Change the form tag to point to the process script -->
  <form id="cadastroForm" class="needs-validation" enctype="multipart/form-data" novalidate action="../process/process_registration.php" method="POST">
    <!-- Role Selection Section -->
    <section class="form-section">
      <h5 class="form-subtitle">Selecione as Funções Desejadas</h5>
      <p class="text-muted mb-3">Você pode selecionar múltiplas funções. Campos específicos aparecerão conforme sua seleção.</p>

      <div class="role-selector">
        <div class="role-checkbox" data-role="docente">
          <input type="checkbox" id="role-docente" name="roles[]" value="docente">
          <div class="role-info">
            <div class="role-title">Docente</div>
            <div class="role-description">Instrutor para cursos e capacitações</div>
          </div>
        </div>

        <div class="role-checkbox" data-role="docente-pos">
          <input type="checkbox" id="role-docente-pos" name="roles[]" value="docente-pos">
          <div class="role-info">
            <div class="role-title">Docente Pós-Graduação</div>
            <div class="role-description">Instrutor para cursos de pós-graduação</div>
          </div>
        </div>

        <div class="role-checkbox" data-role="interprete">
          <input type="checkbox" id="role-interprete" name="roles[]" value="interprete">
          <div class="role-info">
            <div class="role-title">Intérprete de Libras</div>
            <div class="role-description">Intérprete de língua brasileira de sinais</div>
          </div>
        </div>

        <div class="role-checkbox" data-role="tecnico">
          <input type="checkbox" id="role-tecnico" name="roles[]" value="tecnico">
          <div class="role-info">
            <div class="role-title">Apoio Técnico</div>
            <div class="role-description">Suporte técnico para eventos e capacitações</div>
          </div>
        </div>
      </div>
    </section>

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
          <input name="document_number" class="did-floating-input form-control" type="text" placeholder=" " maxlength="12" required />
          <label class="did-floating-label">Documento de Identidade*</label>
          <div class="invalid-feedback">Informe um número de documento de identidade</div>
        </div>
        <div class="did-floating-label-content col-6 col-md-4">
          <input name="document_emissor" class="did-floating-input form-control" type="text" placeholder=" " required /> <label class="did-floating-label">Órgão emissor*</label>
          <div class="invalid-feedback">Informe o órgão emissor</div>
        </div>
        <div class="did-floating-label-content col-6 col-md-2">
          <input name="document_uf" class="did-floating-input form-control" type="text" placeholder=" " maxlength="2" required /> <label class="did-floating-label">UF*</label>
          <div class="invalid-feedback">Informe a UF</div>
        </div>
      </div>
      <div class="row">
        <div class="did-floating-label-content col-6">
          <input name="cpf" class="did-floating-input form-control" type="text" placeholder=" " required />
          <label class="did-floating-label">CPF*</label>
          <div class="invalid-feedback">Informe um CPF válido</div>
        </div>
        <!-- Add this in the Personal Data section after the CPF field -->
        <div class="did-floating-label-content col-6" style="display: none;">
          <input name="password" class="did-floating-input form-control" type="password" value="<?php echo isset($_POST['cpf']) ? $_POST['cpf'] : ''; ?>" />
          <label class="did-floating-label">Senha (inicialmente o CPF)</label>
        </div>
        <div class="did-floating-label-content col-6">
          <input name="email" class="did-floating-input form-control" type="email" placeholder=" " required />
          <label class="did-floating-label">Email*</label>
          <div class="invalid-feedback">Informe um email válido</div>
        </div>

        <div class="did-floating-label-content col-6">
          <input name="birth_date" class="did-floating-input form-control" type="date" placeholder=" " required />
          <label class="did-floating-label">Data de Nascimento*</label>
          <div class="invalid-feedback">Informe sua data de nascimento</div>
        </div>
      </div>
      <div class="row">
        <div class="did-floating-label-content col-12 col-md-6">
          <input name="street" class="did-floating-input form-control" type="text" placeholder=" " required /> <label class="did-floating-label">Endereço*</label>
          <div class="invalid-feedback">Informe seu endereço</div>
        </div>
        <div class="did-floating-label-content col-6 col-md-2">
          <input name="number" class="did-floating-input form-control" type="text" placeholder=" " required /> <label class="did-floating-label">Nº*</label>
          <div class="invalid-feedback">Informe o número</div>
        </div>
        <div class="did-floating-label-content col-6 col-md-4">
          <input name="complement" class="did-floating-input form-control" type="text" placeholder=" " /> <label class="did-floating-label">Complemento</label>
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
          <input name="zip_code" class="did-floating-input form-control" type="text" placeholder=" " required /> <label class="did-floating-label">CEP*</label>
          <div class="invalid-feedback">Informe seu CEP</div>
        </div>
        <div class="did-floating-label-content col-6">
          <input name="phone" class="did-floating-input form-control" type="text" placeholder=" " required />
          <label class="did-floating-label">Telefone*</label>
          <div class="invalid-feedback">Informe um telefone</div>
        </div>
      </div>
    </section>
    <!-- Conta Bancária -->
    <section class="form-section">
      <h5 class="form-subtitle">Conta Bancária (para recebimento via TED)</h5>
      <div class="row mb-3">
        <div class="col-md-3">
          <label for="codigo_banco" class="form-label">Código do Banco</label>
          <input type="text" class="form-control" id="codigo_banco" name="codigo_banco">
        </div>
        <div class="col-md-3">
          <label for="nome_banco" class="form-label">Nome do Banco</label>
          <input type="text" class="form-control" id="nome_banco" name="nome_banco">
        </div>
        <div class="col-md-3">
          <label for="agencia" class="form-label">Agência</label>
          <input type="text" class="form-control" id="agencia" name="agencia">
        </div>
        <div class="col-md-3">
          <label for="conta" class="form-label">Conta</label>
          <input type="text" class="form-control" id="conta" name="conta">
        </div>
      </div>
    </section>
    <!-- Professional Data Section -->
    <section class="form-section">
      <h5 class="form-subtitle">Dados Profissionais</h5>
      <div class="row">
        <div class="did-floating-label-content col-12">
          <select name="degree[]" class="did-floating-select form-select" required>
            <option value=""></option>
            <option value="Médio">Ensino médio completo</option>
            <option value="Superior incompleto">Superior incompleto</option>
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
              <input name="course_name[]" class="did-floating-input form-control" type="text" placeholder=" " required /> <label class="did-floating-label">Curso de Formação*</label>
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
        <i class="fas fa-plus"></i> Adicionar mais formação
      </button>

      <!-- Intérprete Requirements (shown when Intérprete is selected) -->
      <div id="interprete-requirements" class="conditional-section">
        <div class="alert-info">
          <h6><i class="fas fa-info-circle"></i> Requisitos para Intérprete de Libras</h6>
          <p>Para atuar como Intérprete de Libras, você precisará fornecer:</p>
          <ul>
            <li>Certificação específica em Língua Brasileira de Sinais (Libras)</li>
            <li>Comprovação de experiência profissional em interpretação de Libras</li>
            <li>Certificado de proficiência em Libras (Prolibras ou similar)</li>
          </ul>
        </div>
      </div>
    </section>

    <!-- Documents Section -->
    <section class="form-section">
      <h5 class="form-subtitle">
        <i class="fas fa-file-upload me-2"></i>
        Documentos
      </h5>
      <p class="text-muted mb-4">
        Envie as documentações pessoais e habilitações requeridas no Edital.
        Cada documento deve ser enviado separadamente em formato PDF.
      </p>

      <!-- Personal Documents (Common) -->
      <div class="document-subsection">
        <h6 class="subsection-title">
          <i class="fas fa-user me-2"></i>
          Documentos Pessoais (Obrigatórios para todos)
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
        </div>

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

      <!-- Unified Professional Qualification Documents (shown for Docente, Docente Pos, or Técnico) -->
      <div id="professional-documents" class="document-subsection conditional-section">
        <h6 class="subsection-title">
          <i class="fas fa-graduation-cap me-2"></i>
          Comprovação de Qualificação Técnica
        </h6>

        <div class="file-upload-group">
          <label for="formacao_escolar" class="file-label">
            Formação Escolar <span class="required-indicator">*</span>
          </label>
          <input class="form-control professional-doc-field" type="file" id="formacao_escolar"
            name="formacao_escolar" accept="application/pdf">
          <div class="invalid-feedback">Comprovação de formação escolar é obrigatória</div>
          <div class="form-text">Diploma, certificado ou declaração de conclusão</div>
        </div>

        <div class="file-upload-group">
          <label for="experiencia_profissional" class="file-label">
            Comprovante de experiência profissional <span class="required-indicator">*</span>
          </label>
          <input class="form-control professional-doc-field" type="file" id="experiencia_profissional"
            name="experiencia_profissional" accept="application/pdf">
          <div class="invalid-feedback">Comprovante de experiência é obrigatório</div>
          <div class="form-text">Carteira de trabalho, declaração ou contrato</div>
        </div>

        <!-- Optional fields for Docente roles -->
        <div class="docente-only-fields">
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
      </div>

      <!-- Intérprete Documents (shown when Intérprete is selected) -->
      <div id="interprete-documents" class="document-subsection conditional-section">
        <h6 class="subsection-title">
          <i class="fas fa-hands me-2"></i>
          Documentação Específica - Intérprete de Libras
        </h6>

        <div class="file-upload-group">
          <label for="certificacao_libras" class="file-label">
            Certificação em Libras <span class="required-indicator">*</span>
          </label>
          <input class="form-control interprete-doc-field" type="file" id="certificacao_libras"
            name="certificacao_libras" accept="application/pdf">
          <div class="invalid-feedback">Certificação em Libras é obrigatória</div>
          <div class="form-text">Prolibras ou certificação equivalente</div>
        </div>

        <div class="file-upload-group">
          <label for="experiencia_libras" class="file-label">
            Comprovante de experiência em interpretação de Libras <span class="required-indicator">*</span>
          </label>
          <input class="form-control interprete-doc-field" type="file" id="experiencia_libras"
            name="experiencia_libras" accept="application/pdf">
          <div class="invalid-feedback">Comprovante de experiência é obrigatório</div>
        </div>
      </div>

      <!-- Economic-Financial Qualification (Common) -->
      <div class="document-subsection">
        <h6 class="subsection-title">
          <i class="fas fa-file-invoice-dollar me-2"></i>
          Qualificação Econômico-Financeira e Regularidade Fiscal
        </h6>

        <div class="file-upload-group">
          <label for="certidao_estadual" class="file-label">
            Certidão Negativa Estadual <span class="required-indicator">*</span>
          </label>
          <input class="form-control" type="file" id="certidao_estadual"
            name="certidao_estadual" accept="application/pdf" required>
          <div class="invalid-feedback">Certidão Negativa Estadual é obrigatória</div>
          <div class="form-text">Certidão de regularidade com a Fazenda Estadual pode ser emitida <a href="https://sefaz.es.gov.br/emissao-de-certidoes" target="_blank">aqui</a></div>
        </div>

        <div class="file-upload-group">
          <label for="certidao_municipal" class="file-label">
            Certidão Negativa Municipal <span class="required-indicator">*</span>
          </label>
          <input class="form-control" type="file" id="certidao_municipal"
            name="certidao_municipal" accept="application/pdf" required>
          <div class="invalid-feedback">Certidão Negativa Municipal é obrigatória</div>
          <div class="form-text">Certidão de regularidade com a Fazenda Municipal</div>
        </div>

        <div class="file-upload-group">
          <label for="certidao_conjunta" class="file-label">
            Certidão Conjunta PGFN e RFB do Tribunal de Justiça <span class="required-indicator">*</span>
          </label>
          <input class="form-control" type="file" id="certidao_conjunta"
            name="certidao_conjunta" accept="application/pdf" required>
          <div class="invalid-feedback">Certidão Conjunta PGFN e RFB é obrigatória</div>
          <div class="form-text">Certidão de regularidade com a Receita Federal e PGFN pode ser emitida <a href="https://servicos.receitafederal.gov.br/servico/certidoes/#/home" target="_blank">aqui</a></div>
        </div>
      </div>
    </section>

    <!-- Additional Information -->
    <section class="form-section">
      <h5 class="form-subtitle">Informações Adicionais</h5>
      <div>
        <p style="font-weight: 500;">É portador de necessidades especiais?</p>
        <div class="form-check form-check-inline radio-div">
          <input class="form-check-input" type="radio" name="specialNeeds" id="specialNeedsYes" value="yes" onclick="showSpecialNeeds(true)" required>
          <span class="check"></span>
          <label class="form-check-label terms-label" for="specialNeedsYes">Sim</label>
        </div>
        <div class="form-check form-check-inline radio-div">
          <input class="form-check-input" type="radio" name="specialNeeds" id="specialNeedsNo" value="no" onclick="showSpecialNeeds(false)" required>
          <span class="check"></span>
          <label class="form-check-label terms-label" for="specialNeedsNo">Não</label>
        </div>
        <div class="did-floating-label-content" style="display:none; margin-top: 10px" id="specialNeedsDetailsContainer">
          <input name="specialNeedsDetails" id="specialNeedsDetails" class="did-floating-input form-control" type="text" placeholder=" " required />
          <label for="specialNeedsDetails" class="did-floating-label">Especifique*</label>
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- SweetAlert2 for notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  // Role checkbox management
  document.addEventListener('DOMContentLoaded', function() {
    const roleCheckboxes = document.querySelectorAll('input[name="roles[]"]');
    const scholarshipSelect = document.getElementById('scholarship');

    // Role-specific elements
    const interpreteRequirements = document.getElementById('interprete-requirements');
    const professionalDocuments = document.getElementById('professional-documents');
    const interpreteDocuments = document.getElementById('interprete-documents');
    const docenteOnlyFields = document.querySelector('.docente-only-fields');

    // Handle role checkbox changes
    roleCheckboxes.forEach(checkbox => {
      checkbox.addEventListener('change', function() {
        const roleCard = this.closest('.role-checkbox');
        const role = this.value;

        // Toggle active class on card
        if (this.checked) {
          roleCard.classList.add('active');
        } else {
          roleCard.classList.remove('active');
        }

        // Show/hide conditional sections
        handleRoleChange();
      });
    });

    function handleRoleChange() {
      const isDocente = document.getElementById('role-docente').checked;
      const isDocentePos = document.getElementById('role-docente-pos').checked;
      const isInterprete = document.getElementById('role-interprete').checked;
      const isTecnico = document.getElementById('role-tecnico').checked;

      // Handle Professional Documents (unified for Docente, Docente Pos, and Técnico)
      if (isDocente || isDocentePos || isTecnico) {
        professionalDocuments.classList.add('show');
        professionalDocuments.querySelectorAll('.professional-doc-field').forEach(field => {
          field.setAttribute('required', '');
        });

        // Show optional fields only for Docente roles
        if (docenteOnlyFields) {
          if (isDocente || isDocentePos) {
            docenteOnlyFields.style.display = 'block';
          } else {
            docenteOnlyFields.style.display = 'none';
          }
        }
      } else {
        professionalDocuments.classList.remove('show');
        professionalDocuments.querySelectorAll('.professional-doc-field').forEach(field => {
          field.removeAttribute('required');
        });
      }

      // Handle Intérprete sections
      if (isInterprete) {
        interpreteRequirements.classList.add('show');
        interpreteDocuments.classList.add('show');
        interpreteDocuments.querySelectorAll('.interprete-doc-field').forEach(field => {
          field.setAttribute('required', '');
        });
        document.querySelectorAll('.interprete-field').forEach(field => {
          field.setAttribute('required', '');
        });
      } else {
        interpreteRequirements.classList.remove('show');
        interpreteDocuments.classList.remove('show');
        interpreteDocuments.querySelectorAll('.interprete-doc-field').forEach(field => {
          field.removeAttribute('required');
        });
        document.querySelectorAll('.interprete-field').forEach(field => {
          field.removeAttribute('required');
        });
      }

      // Update scholarship options based on roles
      updateScholarshipOptions();
    }

    function updateScholarshipOptions() {
      const isDocentePos = document.getElementById('role-docente-pos').checked;
      const isInterprete = document.getElementById('role-interprete').checked;
      const isTecnico = document.getElementById('role-tecnico').checked;

      // Reset options
      scholarshipSelect.innerHTML = '<option value=""></option>';

      if (isDocentePos) {
        // Pós-graduação requires higher education
        scholarshipSelect.innerHTML += `
          <option value="Pós-graduação">Pós-graduação</option>
          <option value="Mestrado">Mestrado</option>
          <option value="Doutorado">Doutorado</option>
        `;
      } else if (isInterprete || isTecnico) {
        // Intérprete and Técnico can have lower education levels
        scholarshipSelect.innerHTML += `
          <option value="Médio">Ensino médio completo</option>
          <option value="Superior incompleto">Superior incompleto</option>
          <option value="Superior completo">Superior completo</option>
          <option value="Pós-graduação">Pós-graduação</option>
          <option value="Mestrado">Mestrado</option>
          <option value="Doutorado">Doutorado</option>
        `;
      } else {
        // Default options
        scholarshipSelect.innerHTML += `
          <option value="Superior completo">Superior completo</option>
          <option value="Pós-graduação">Pós-graduação</option>
          <option value="Mestrado">Mestrado</option>
          <option value="Doutorado">Doutorado</option>
        `;
      }
    }

    // Custom validation before form submission
    // Note: The actual form submission is handled by form-handler.js
    const cadastroForm = document.getElementById('cadastroForm');
    if (cadastroForm) {
      // Add class to identify this form for form-handler.js
      cadastroForm.classList.add('needs-validation');

      // Add custom validation for role selection
      cadastroForm.addEventListener('submit', function(event) {
        // Check if at least one role is selected
        const rolesSelected = document.querySelectorAll('input[name="roles[]"]:checked').length > 0;

        if (!rolesSelected) {
          event.preventDefault();
          event.stopPropagation();
          alert('Por favor, selecione pelo menos uma função.');
          return false;
        }

        // Set password value to CPF before submission
        const cpfField = document.querySelector('input[name="cpf"]');
        const passwordField = document.querySelector('input[name="password"]');
        if (cpfField && passwordField) {
          // Clean CPF (remove formatting) for password
          const cleanCpf = cpfField.value.replace(/\D/g, '');
          passwordField.value = cleanCpf;
        }

        // Let form-handler.js handle the actual submission
      }, true); // Use capture phase to run before form-handler.js
    }
  });

  function showSpecialNeeds(show) {
    const inputContainer = document.getElementById("specialNeedsDetailsContainer");
    const inputField = document.getElementById("specialNeedsDetails");

    if (show) {
      inputContainer.style.display = "block";
      inputField.setAttribute("required", "required");
    } else {
      inputContainer.style.display = "none";
      inputField.removeAttribute("required");
      inputField.value = "";
    }
  }
</script>

<!-- Your form handler script -->
<script src="../scripts/form-handler.js"></script>
<?php
include_once('../components/footer.php');
?>