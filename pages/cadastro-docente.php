<?php
include_once('../components/header.php');
?>

<style>
  .select2-container--default .select2-selection--multiple {
    padding: 10px 0px 20px 5px !important;
    border: solid 1px #3d85d8 !important;
  }

  .select2-container .select2-selection--multiple .select2-search--inline .select2-search__field {
    color: #3d85d8 !important;
    font-size: 1em;
    font-style: italic;
  }
  .form-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e9ecef;
        }
        
        .form-subtitle {
            color: #495057;
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .document-subsection {
            background: white;
            border-radius: 6px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid #dee2e6;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .subsection-title {
            color: #212529;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #007bff;
        }
        
        .file-upload-group {
            margin-bottom: 1rem;
        }
        
        .file-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .file-input-wrapper {
            position: relative;
        }
        
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .form-text {
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .required-indicator {
            color: #dc3545;
            font-weight: bold;
        }
        
        .upload-status {
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .upload-success {
            color: #198754;
        }
        
        .upload-error {
            color: #dc3545;
        }
        
        .optional-label {
            font-style: italic;
            color: #6c757d;
        }
</style>

<main>
  <div class="container">
    <h1 class="main-title">Credenciamento de Docentes</h1>

    <form id="docenteForm" class="needs-validation" enctype="multipart/form-data" novalidate>
      <section class="form-section">
        <h5 class="form-subtitle">Dados Pessoais</h5>
        <div class="row">
          <div class="did-floating-label-content col-12">
            <input name="name" id="name" class="did-floating-input form-control" type="text" placeholder=" " required />
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
            <input name="rgEmissor" id="rgEmissor" class="did-floating-input form-control" type="text" placeholder=" " required />
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
                  required>
                  <option value=""></option>
                  <option value="Graduação">Graduação</option>
                  <option value="Especialidade">Especialização</option>
                  <option value="Mestrado">Mestrado</option>
                  <option value="Doutorado">Doutorado</option>
                </select>
                <label class="did-floating-label">Escolaridade*</label>
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
                    placeholder=" " />
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
                    placeholder=" " />
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
            id="scholarshipBtn">
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
                  name="eixo_0"
                  id="eixo_0"
                  class="did-floating-select form-select eixo-select"
                  onchange="loadEstacoes(this)"
                  required>
                </select>
                <option value=""></option>
                <label for="eixo_0" class="did-floating-label">Eixo*</label>
              </div>
            </div>

            <div class="row">
              <div class="did-floating-label-content">
                <select
                  name="estacao_0"
                  id="estacao_0"
                  class="did-floating-select form-select"
                  onchange="loadDisciplinas(this)"
                  required>
                </select>
                <option value=""></option>
                <label for="estacao_0" class="did-floating-label">Estação/ Órgão*</label>
              </div>
            </div>

            <div class="row">
              <div class="did-floating-label-content">
                <select
                  name="disciplina_0"
                  id="disciplina_0"
                  class="did-floating-select form-select disciplines"
                  onchange="loadModulos(this)"
                  required>
                </select>
                <option value=""></option>
                <label for="disciplina_0" class="did-floating-label">Curso/ Programa*</label>
              </div>
            </div>
            <div class="row" id="lectureSection_0" style="display: none;">
              <div class="did-floating-label-content col-12">
                <input
                  class="did-floating-input"
                  type="text"

                  name="lectureName_0"
                  placeholder=" " />
                <label class="did-floating-label">Nome*</label>
              </div>

              <div class="did-floating-label-content col-12">
                <textarea
                  class="did-floating-input"
                  name="lectureDetail_0"
                  style="height: 200px; padding-top: 10px;"

                  placeholder=" "></textarea>

                <label class="did-floating-label">Detalhes*</label>
              </div>
            </div>

            <div class="row module-section" id="moduleSection_0" style="display: none;">
              <div class="did-floating-label-content col-12 ">
                <select
                  name="modulos_0[]"

                  id="modulos_0"
                  class="did-floating-select form-select modulos"
                  multiple>
                </select>
              </div>
            </div>



          </div>

          <button
            class="btnF add-section-btn"
            onclick="cloneSection('disciplines')"
            type="button"
            id="disciplinesBtn">
            Adicionar mais
          </button>
        </div>
      </section>

      <section class="form-section">
        <h5 class="form-subtitle">Categoria, Atividades e Serviços</h5>
        <div class="checkbox-group">
          <p class="control-label col-md-12" for="position" style="font-weight:500">Selecione as categorias nas quais você deseja se credenciar</p>
          <div class="col-md-6">
            <input type="checkbox" name="position[]" value="1" id="position1" /> <label for="position1">Docente</label><br>
            <input type="checkbox" name="position[]" value="2" id="position2" /> <label for="position2"> Docente Conteudista</label><br>
            <input type="checkbox" name="position[]" value="3" id="position3" /> <label for="position3">Docente Assistente</label> <br>
            <input type="checkbox" name="position[]" value="4" id="position4" /> <label for="position4">Coordenador Técnico</label> <br>
            <input type="checkbox" name="position[]" value="5" id="position5" /> <label for="position5">Conferencista/Palestrante</label> <br>
            <input type="checkbox" name="position[]" value="6" id="position6" /> <label for="position6">Painelista/Debatedor</label> <br>
            <input type="checkbox" name="position[]" value="7" id="position7" /> <label for="position7">Moderador</label> <br>
            <input type="checkbox" name="position[]" value="8" id="position8" /> <label for="position8">Reunião Técnica</label> <br>
            <input type="checkbox" name="position[]" value="9" id="position9" /> <label for="position9">Assessoramento Técnico</label> <br>
            <input type="checkbox" name="position[]" value="10" id="position10" /> <label for="position10">Revisão de Texto</label> <br>
            <input type="checkbox" name="position[]" value="11" id="position11" /> <label for="position11">Entrevista</label>
          </div>
        </div>
      </section>

      <section class="form-section">
            <h5 class="form-subtitle">
                <i class="fas fa-file-upload me-2"></i>
                Documentos
            </h5>
            <p class="text-muted mb-4">
                Envie as documentações pessoais e habilitações requeridas no Edital. 
                Cada documento deve ser enviado separadamente em formato PDF.
            </p>
            
            <!-- Documentos Pessoais -->
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
                    <label for="certificado_reservista" class="file-label">
                        Certificado de Reservista <span class="optional-label">(se aplicável)</span>
                    </label>
                    <input class="form-control" type="file" id="certificado_reservista" 
                           name="certificado_reservista" accept="application/pdf">
                    <div class="form-text">Obrigatório apenas para candidatos do sexo masculino</div>
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
                        Certificados ou declarações de conclusão de cursos <span class="optional-label">(se houver)</span>
                    </label>
                    <input class="form-control" type="file" id="certificados_cursos" 
                           name="certificados_cursos" accept="application/pdf">
                    <div class="form-text">Cursos complementares, especializações ou capacitações</div>
                </div>
            </div>
            
            <!-- Qualificação Econômico-Financeira -->
            <div class="document-subsection">
                <h6 class="subsection-title">
                    <i class="fas fa-file-invoice-dollar me-2"></i>
                    Qualificação Econômico-Financeira e Regularidade Fiscal e Trabalhista
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
                    <label for="certidao_federal" class="file-label">
                        Certidão Negativa Federal <span class="required-indicator">*</span>
                    </label>
                    <input class="form-control" type="file" id="certidao_federal" 
                           name="certidao_federal" accept="application/pdf" required>
                    <div class="invalid-feedback">Certidão Negativa Federal é obrigatória</div>
                    <div class="form-text">Certidão de regularidade com a Fazenda Federal pode ser emitida <a href="https://servicos.receitafederal.gov.br/servico/certidoes/#/home" target="_blank">aqui</a></div>
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
            
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Observações importantes:</strong>
                <ul class="mb-0 mt-2">
                    <li>Apenas documentos em formato PDF serão aceitos</li>
                    <li>Cada arquivo deve ter no máximo 5MB</li>
                    <li>Certifique-se de que todos os documentos estão legíveis</li>
                    <li>Documentos com <span class="required-indicator">*</span> são obrigatórios</li>
                </ul>
            </div>
        </section>

      <section class="form-section">
        <h5 class="form-subtitle">Informações Adicionais</h5>
        <div>
          <p>É portador de necessidades especiais?</p>
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
  import {
    handleTeacherSubmission
  } from '../scripts/main.js';
  document.addEventListener("DOMContentLoaded", () => {
    loadEixos();
    handleTeacherSubmission()
  });
</script>