<?php
// pages/docente-pos.php

require_once '../init.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit();
}


// Include styles and header
echo '<link rel="stylesheet" href="../styles/user.css">';
include '../components/header.php';
require_once '../pdf/assets/title_case.php';
require_once '../backend/services/teacherpos.service.php';

// Initialize database connection
$connection = new Database();
$conn = $connection->connect();

// Check if user is admin
$isAdmin = false;
if (isset($_SESSION['user_roles']) && is_array($_SESSION['user_roles'])) {
  $is_admin = isAdministrativeRole();
}

// Get requested ID from URL
$requested_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verify the requested user exists and is a teacher
$stmt = $conn->prepare("
    SELECT u.id 
    FROM user u
    INNER JOIN user_roles ur ON ur.user_id = u.id
    WHERE u.id = ? AND ur.role = 'docente_pos'
");
$stmt->execute([$requested_id]);

if (!$stmt->fetch()) {
  header('Location: docentes-pos.php');
  exit();
}

// Check access permissions
$is_own_profile = false;
if ($is_admin) {
  $is_own_profile = false;
} elseif ($_SESSION['user_type'] === 'postg_teacher' && $_SESSION['user_id'] == $requested_id) {
  $is_own_profile = true;
} else {
  header('Location: home.php');
  exit();
}

// Use existing connection
$teacherService = new TeacherPostGService($conn);

try {
  $teacher = $teacherService->getTeacherPostG($requested_id);

  if (!$teacher) {
    ob_start();
    include '../components/header.php';
    echo '<div class="container">
                <h1 class="main-title">Erro</h1>
                <p>Docente não encontrado.</p>
                <a href="docentes-pos.php" class="btn btn-primary">Voltar para lista de docentes</a>
              </div>';
    include '../components/footer.php';
    ob_end_flush();
    exit();
  }

  // Extract teacher data
  $name = $teacher->name;
  $document_number = $teacher->document_number;
  $document_emissor = $teacher->document_emissor;
  $document_uf = $teacher->document_uf;
  $phone = $teacher->phone;
  $cpf = $teacher->cpf;
  $email = $teacher->email;
  $created_at = $teacher->created_at;
  $address = $teacher->address;
  $city = $teacher->address->city;
  $state = $teacher->address->state;
  $zip = $teacher->address->zip;
  $file_path = $teacher->file_path;
  $education_degree = $teacher->educations;
  $disciplines = $teacher->disciplines;
  $post_graduation = $teacher->post_graduation;
  $activities = $teacher->activities;
  $special_needs = $teacher->special_needs;
  $enabled = $teacher->enabled;

  $statusText = match ($enabled) {
    1 => 'Apto',
    0 => 'Inapto',
    default => 'Aguardando aprovação',
  };

  $statusClass = match ($enabled) {
    1 => 'status-approved',
    0 => 'status-not-approved',
    default => 'status-pending',
  };

  // Format date
  $date = new DateTime($created_at);
  $dateF = $date->format('d/m/Y H:i');

  // Format filepath
  $path = '';
  if ($file_path) {
    $string = $file_path;
    $position = strpos($string, "posgraduacao");
    if ($position !== false) {
      $start = $position + strlen("posgraduacao/");
      $path = substr($string, $start);
    }
  }
} catch (Exception $e) {
  ob_start();
  include '../components/header.php';
  echo '<div class="container">
            <h1 class="main-title">Erro</h1>
            <p>Erro ao carregar dados do docente: ' . htmlspecialchars($e->getMessage()) . '</p>
            <a href="docentes-pos.php" class="btn btn-primary">Voltar para lista de docentes</a>
          </div>';
  include '../components/footer.php';
  ob_end_flush();
  exit();
}

ob_start();
?>
<style>
  .activities-section {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
  }

  .activity-item {
    background-color: white;
  }

  .activity-evaluation {
    border-left: 3px solid #dee2e6;
    padding-left: 10px;
  }

  .discipline-header {
    cursor: pointer;
    transition: background-color 0.2s;
    padding: 10px;
    border-radius: 5px;
    user-select: none;
  }

  .discipline-header:hover {
    background-color: #e9ecef;
  }

  .discipline-header .chevron {
    transition: transform 0.3s ease;
    display: inline-block;
    margin-right: 8px;
  }

  .discipline-header.active .chevron {
    transform: rotate(90deg);
  }

  .activities-section {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
  }

  .activities-section.show {
    max-height: 5000px;
    transition: max-height 0.5s ease-in;
  }
</style>
<div class="container container-user">
  <?php if ($is_own_profile): ?>
    <div class="alert alert-info d-flex justify-content-between align-items-center mb-3">
      <span>Bem-vindo(a) ao seu perfil, <?= titleCase($name) ?>!</span>
      <a href="../auth/logout.php" class="btn btn-danger btn-sm">Sair</a>
    </div>
  <?php endif; ?>

  <?php if ($is_admin): ?>
    <a href="docentes-pos.php" class="back-link">Voltar</a>
  <?php endif; ?>

  <h1 class="main-title">Dados do Docente - Pós-Graduação</h1>

  <div class="info-section">
    <h3>Dados pessoais</h3>
    <div class="row">
      <div class="col-9">
        <p class="col-12"><strong>Nome</strong></p>
        <p class="col-12"><?= titleCase($name) ?></p>
      </div>
      <div class="col-3">
        <p class="col-12"><strong>Data de Inscrição</strong></p>
        <p class="col-12"><?= $dateF ?></p>
      </div>
    </div>
    <div class="row">
      <p class="col-12"><strong>Telefone</strong></p>
      <p class="col-12"><?= $phone ?></p>
    </div>
    <div class="row">
      <div class="col-6">
        <p><strong>Documento de Identidade</strong></p>
        <p><?= $document_number ?></p>
      </div>
      <div class="col-4">
        <p><strong>Órgão Emissor</strong></p>
        <p><?= $document_emissor ?></p>
      </div>
      <div class="col-2">
        <p><strong>UF</strong></p>
        <p><?= strtoupper($document_uf) ?></p>
      </div>
    </div>
    <div class="row">
      <p class="col-12"><strong>CPF</strong></p>
      <p class="col-12"><?= $cpf ?></p>
    </div>
    <div class="row">
      <p class="col-12"><strong>Email</strong></p>
      <p class="col-12"><?= $email ?></p>
    </div>
    <div class="row">
      <p class="col-12"><strong>Endereço</strong></p>
      <p class="col-12"><?= titleCase($address) . ', ' . titleCase($city) . ' - ' . strtoupper($state) . ', CEP: ' . $zip ?></p>
    </div>
    <?php if ($special_needs != 'Não'): ?>
      <div class="row">
        <p class="col-12"><strong>Necessidades Especiais</strong></p>
        <p class="col-12"><?= $special_needs ?></p>
      </div>
    <?php endif; ?>
  </div>

  <div class="info-section">
    <h3>Formação</h3>
    <?php if (!empty($education_degree)): ?>
      <?php foreach ($education_degree as $education): ?>
        <p><?= $education->degree ?> - <?= $education->institution ?></p>
      <?php endforeach ?>
    <?php else: ?>
      <p>Nenhuma formação cadastrada.</p>
    <?php endif; ?>
  </div>

  <div class="info-section">
    <h3>Cursos</h3>
    <?php
    // Combine both regular disciplines and post-graduations into one array
    $all_courses = array_merge($disciplines, $post_graduation);

    if (!empty($all_courses)):
      $discipline_index = 0;
    ?>
      <?php foreach ($all_courses as $discipline):
        $discipline_index++;
        // Use getId() method instead of accessing private $id
        $disc_id = method_exists($discipline, 'getId') ? $discipline->getId() : 0;
        $disc_name = method_exists($discipline, 'getName') ? $discipline->getName() : (property_exists($discipline, 'name') ? $discipline->name : 'Nome não disponível');
        $disc_eixo = method_exists($discipline, 'getEixo') ? $discipline->getEixo() : (property_exists($discipline, 'eixo') ? $discipline->eixo : null);
        $disc_postg = property_exists($discipline, 'post_graduation') ? $discipline->post_graduation : null;

        // Get activities for this discipline
        $disc_activities = property_exists($discipline, 'activities') ? $discipline->activities : [];

        $accordion_id = 'course-' . $discipline_index;
      ?>

        <?php if (!empty($disc_activities)): ?>
          <!-- ACCORDION COURSE ITEM -->
          <div class="discipline-item mb-3" style="border-left: 4px solid #3498db; border-radius: 5px; overflow: hidden;">
            <!-- Clickable Header -->
            <div class="discipline-header" onclick="toggleCourse('<?= $accordion_id ?>')" id="header-<?= $accordion_id ?>">
              <span class="chevron">▶</span>
              <strong><?= htmlspecialchars($disc_name) ?></strong>
              <span class="badge bg-secondary ms-2"><?= count($disc_activities) ?> atividade<?= count($disc_activities) > 1 ? 's' : '' ?></span>
            </div>

            <!-- Collapsible Details Section -->
            <div class="activities-section" id="<?= $accordion_id ?>">
              <div style="padding: 15px; background: #f8f9fa;">

                <?php if ($disc_eixo || $disc_postg): ?>
                  <div class="discipline-details mb-3">
                    <p class="text-muted" style="margin-bottom: 0; font-size: 14px;">
                      <?php if ($disc_postg): ?>Pós-Graduação: <?= htmlspecialchars($disc_postg) ?><?php endif; ?>
                      <?php if ($disc_postg && $disc_eixo): ?> | <?php endif; ?>
                      <?php if ($disc_eixo): ?>Eixo: <?= htmlspecialchars($disc_eixo) ?><?php endif; ?>
                    </p>
                  </div>
                <?php endif; ?>

                <!-- Activities List -->
                <div class="activities-list mt-2">
                  <?php foreach ($disc_activities as $activity):
                    // Calculate status for this specific activity
                    $act_gese = $activity['gese_evaluation'] ?? null;
                    $act_ped = $activity['pedagogico_evaluation'] ?? null;

                    $activityStatusText = 'Em avaliação';
                    $activityStatusClass = 'status-pending';

                    if ($act_gese !== null && $act_ped !== null) {
                      if ($act_gese === 1 && $act_ped === 1) {
                        $activityStatusText = 'Apto';
                        $activityStatusClass = 'status-approved';
                      } elseif ($act_gese === 0 || $act_ped === 0) {
                        $activityStatusText = 'Inapto';
                        $activityStatusClass = 'status-not-approved';
                      }
                    } elseif ($act_gese === null && $act_ped === null) {
                      $activityStatusText = 'Aguardando';
                      $activityStatusClass = 'status-pending';
                    }
                  ?>
                    <div class="activity-item mb-2 p-3 border rounded" style="background: white;">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>
                          <i class="fas fa-clipboard-list me-2"></i>
                          <strong><?= htmlspecialchars($activity['name']) ?></strong>
                        </span>
                        <span class="user-status <?= $activityStatusClass ?>"><?= $activityStatusText ?></span>
                      </div>

                      <?php
                      // Check if user should see evaluation buttons
                      $show_gese = (isGESE() || (isAdmin() && !isGEDTH())) && !isGEDTH();
                      $show_ped = (isPedagogico() || (isAdmin() && !isGEDTH())) && !isGEDTH();
                      ?>

                      <?php if ($show_gese || $show_ped): ?>
                        <div class="activity-evaluation mt-2" style="background: #f8f9fa; padding: 10px; border-radius: 5px; border-left: 3px solid #dee2e6;">
                          <!-- Evaluation status badges -->
                          <div class="evaluation-status mb-2">
                            <span class="badge <?= $act_gese === 1 ? 'bg-success' : ($act_gese === 0 ? 'bg-danger' : 'bg-warning') ?>">
                              Aval. Documental: <?= $act_gese === 1 ? 'Aprovado' : ($act_gese === 0 ? 'Reprovado' : 'Pendente') ?>
                            </span>
                            <span class="badge <?= $act_ped === 1 ? 'bg-success' : ($act_ped === 0 ? 'bg-danger' : 'bg-warning') ?> ms-2">
                              Aval. Pedagógica: <?= $act_ped === 1 ? 'Aprovado' : ($act_ped === 0 ? 'Reprovado' : 'Pendente') ?>
                            </span>
                          </div>

                          <!-- GESE Evaluation buttons -->
                          <?php if ($show_gese): ?>
                            <div class="mb-2">
                              <label class="form-label mb-1"><strong>Avaliação Documental (GESE):</strong></label>
                              <div class="btn-group btn-group-sm" role="group">
                                <button class="btn btn-success btn-sm"
                                  onclick="updateEvaluationForActivity(<?= $requested_id ?>, <?= $disc_id ?>, <?= $activity['id'] ?>, 'gese', 1)"
                                  <?= $act_gese === 1 ? 'disabled' : '' ?>>
                                  <i class="fas fa-check"></i> Aprovar
                                </button>
                                <button class="btn btn-danger btn-sm"
                                  onclick="updateEvaluationForActivity(<?= $requested_id ?>, <?= $disc_id ?>, <?= $activity['id'] ?>, 'gese', 0)"
                                  <?= $act_gese === 0 ? 'disabled' : '' ?>>
                                  <i class="fas fa-times"></i> Reprovar
                                </button>
                                <button class="btn btn-secondary btn-sm"
                                  onclick="updateEvaluationForActivity(<?= $requested_id ?>, <?= $disc_id ?>, <?= $activity['id'] ?>, 'gese', null)"
                                  <?= $act_gese === null ? 'disabled' : '' ?>>
                                  <i class="fas fa-undo"></i> Resetar
                                </button>
                              </div>
                            </div>
                          <?php endif; ?>

                          <!-- Pedagogical Evaluation buttons -->
                          <?php if ($show_ped): ?>
                            <div>
                              <label class="form-label mb-1"><strong>Avaliação Pedagógica:</strong></label>
                              <div class="btn-group btn-group-sm" role="group">
                                <button class="btn btn-success btn-sm"
                                  onclick="updateEvaluationForActivity(<?= $requested_id ?>, <?= $disc_id ?>, <?= $activity['id'] ?>, 'pedagogico', 1)"
                                  <?= $act_ped === 1 ? 'disabled' : '' ?>>
                                  <i class="fas fa-check"></i> Aprovar
                                </button>
                                <button class="btn btn-danger btn-sm"
                                  onclick="updateEvaluationForActivity(<?= $requested_id ?>, <?= $disc_id ?>, <?= $activity['id'] ?>, 'pedagogico', 0)"
                                  <?= $act_ped === 0 ? 'disabled' : '' ?>>
                                  <i class="fas fa-times"></i> Reprovar
                                </button>
                                <button class="btn btn-secondary btn-sm"
                                  onclick="updateEvaluationForActivity(<?= $requested_id ?>, <?= $disc_id ?>, <?= $activity['id'] ?>, 'pedagogico', null)"
                                  <?= $act_ped === null ? 'disabled' : '' ?>>
                                  <i class="fas fa-undo"></i> Resetar
                                </button>
                              </div>
                            </div>
                          <?php endif; ?>
                        </div>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
        <?php else: ?>
          <!-- NO ACTIVITIES - Show course without accordion -->
          <div class="discipline-item mb-3" style="border-left: 4px solid #999; padding: 15px; background: #f8f9fa; border-radius: 5px;">
            <div class="discipline-header-static">
              <p class="mb-0"><strong><?= htmlspecialchars($disc_name) ?></strong>
                <span class="text-muted ms-3">(Sem atividades cadastradas)</span>
              </p>
            </div>
            <?php if ($disc_eixo || $disc_postg): ?>
              <p class="text-muted mt-2 mb-0" style="font-size: 14px;">
                <?php if ($disc_postg): ?>Pós-Graduação: <?= htmlspecialchars($disc_postg) ?><?php endif; ?>
                <?php if ($disc_postg && $disc_eixo): ?> | <?php endif; ?>
                <?php if ($disc_eixo): ?>Eixo: <?= htmlspecialchars($disc_eixo) ?><?php endif; ?>
              </p>
            <?php endif; ?>
          </div>
        <?php endif; ?>

      <?php endforeach; ?>
    <?php else: ?>
      <p>Nenhum curso cadastrado.</p>
    <?php endif; ?>
  </div>


  <div class="info-section">
    <h3>Documentos</h3>
    <?php if (!empty($path)): ?>
      <a href="../backend/documentos/posgraduacao/<?= $path ?>" target="_blank">Download</a>
    <?php else: ?>
      <p>Nenhum documento disponível.</p>
      <?php if ($is_admin): ?>
        <div class="text-muted small">
          <div>Debug Info:</div>
          <div>Full Path: <?= htmlspecialchars($teacher->file_path ?? 'NULL') ?></div>
          <?php if (!empty($teacher->file_path)): ?>
            <div>File Exists: <?= file_exists($teacher->file_path) ? 'Yes' : 'No' ?></div>
          <?php else: ?>
            <div>File Exists: No (path is empty)</div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <?php if ($is_own_profile): ?>
    <div class="info-section">
      <h3>Alterar Senha</h3>

      <?php if (isset($_SESSION['password_message'])): ?>
        <div class="alert alert-success">
          <?= htmlspecialchars($_SESSION['password_message']) ?>
        </div>
        <?php unset($_SESSION['password_message']); ?>
      <?php endif; ?>

      <?php if (isset($_SESSION['password_error'])): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($_SESSION['password_error']) ?>
        </div>
        <?php unset($_SESSION['password_error']); ?>
      <?php endif; ?>

      <?php if ($_SESSION['first_login'] ?? false): ?>
        <div class="alert alert-warning">
          <strong>Primeiro acesso!</strong> Por segurança, recomendamos que você altere sua senha.
        </div>
      <?php endif; ?>

      <form method="post" action="../auth/process_change_password.php" class="needs-validation" novalidate>
        <div class="row">
          <div class="col-md-12 mb-3 password-input-group">
            <label for="current_password">Senha Atual</label>
            <input type="password" class="form-control" id="current_password"
              name="current_password" required>
            <button type="button" class="password-toggle-btn" onclick="togglePassword('current_password')" tabindex="-1">
              <i class="fas fa-eye" id="current_password_icon"></i>
            </button>
            <small class="form-text text-muted">
              Se é seu primeiro acesso, use seu CPF (apenas números)
            </small>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3 password-input-group">
            <label for="new_password">Nova Senha</label>
            <input type="password" class="form-control" id="new_password"
              name="new_password" required minlength="8">
            <button type="button" class="password-toggle-btn" onclick="togglePassword('new_password')" tabindex="-1">
              <i class="fas fa-eye" id="new_password_icon"></i>
            </button>
            <small class="form-text text-muted">
              Mínimo 8 caracteres, com letras maiúsculas, minúsculas, números e símbolos (@$!%*?&)
            </small>
          </div>

          <div class="col-md-6 mb-3 password-input-group">
            <label for="confirm_password">Confirmar Nova Senha</label>
            <input type="password" class="form-control" id="confirm_password"
              name="confirm_password" required>
            <button type="button" class="password-toggle-btn" onclick="togglePassword('confirm_password')" tabindex="-1">
              <i class="fas fa-eye" id="confirm_password_icon"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary">Alterar Senha</button>
      </form>
    </div>
  <?php endif; ?>


</div>

<script>
  const userRoles = <?php echo json_encode($_SESSION['user_roles'] ?? []); ?>;
  // Function to check if user can send invites (admin or GEDTH only)
  function canSendInvites() {
    return userRoles.includes('admin') || userRoles.includes('gedth');
  }

  // Function to check if user can view/edit contract info (admin or GESE only)
  function canViewContractInfo() {
    return userRoles.includes('admin') || userRoles.includes('gese');
  }
  const isAdmin = <?= json_encode(isAdministrativeRole()) ?>;

  function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');

    if (field.type === 'password') {
      field.type = 'text';
      icon.classList.remove('fa-eye');
      icon.classList.add('fa-eye-slash');
    } else {
      field.type = 'password';
      icon.classList.remove('fa-eye-slash');
      icon.classList.add('fa-eye');
    }
  }

  <?php if ($is_admin): ?>

    function updateEvaluation(teacherId, disciplineId, evaluationType, status) {
      const evaluationLabels = {
        'gese': 'Avaliação Documental',
        'pedagogico': 'Avaliação Pedagógica'
      };

      const statusText = status === 1 ? 'aprovar' : (status === 0 ? 'reprovar' : 'resetar');
      const evaluationLabel = evaluationLabels[evaluationType] || evaluationType;

      const confirmMessage = `Tem certeza que deseja ${statusText} a ${evaluationLabel} para esta disciplina?`;

      if (!confirm(confirmMessage)) {
        return;
      }

      // Get the button that was clicked
      const clickedButton = event.target;
      const buttonContainer = clickedButton.closest('.discipline-actions');
      const allButtons = buttonContainer.querySelectorAll('button');

      // Disable all buttons and show loading
      allButtons.forEach(btn => {
        btn.disabled = true;
        if (btn === clickedButton) {
          btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
        }
      });

      fetch('../backend/api/update_teacher_evaluation.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            teacher_id: teacherId,
            discipline_id: disciplineId,
            evaluation_type: evaluationType,
            status: status
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showNotification(`${evaluationLabel} atualizada com sucesso!`, 'success');

            // Update the badges immediately without full page reload
            updateEvaluationBadges(buttonContainer, evaluationType, status, data.verification);

            // Re-enable buttons based on new status
            updateButtonStates(buttonContainer, evaluationType, status);
          } else {
            showNotification('Erro ao atualizar avaliação: ' + (data.message || 'Erro desconhecido'), 'danger');

            // Re-enable buttons on error
            allButtons.forEach(btn => {
              btn.disabled = false;
              restoreButtonText(btn, evaluationType, status);
            });
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showNotification('Erro ao processar solicitação', 'danger');

          // Re-enable buttons on error
          allButtons.forEach(btn => {
            btn.disabled = false;
            restoreButtonText(btn, evaluationType, status);
          });
        });
    }

    function updateEvaluationBadges(container, evaluationType, status, verification) {
      const badges = container.closest('.discipline-item').querySelector('.evaluation-status');

      if (evaluationType === 'gese') {
        const geseBadge = badges.querySelector('span:first-child');
        if (status === 1) {
          geseBadge.className = 'badge bg-success';
          geseBadge.textContent = 'Avaliação Documental: Aprovado';
        } else if (status === 0) {
          geseBadge.className = 'badge bg-danger';
          geseBadge.textContent = 'Avaliação Documental: Reprovado';
        } else {
          geseBadge.className = 'badge bg-warning';
          geseBadge.textContent = 'Avaliação Documental: Pendente';
        }
      } else if (evaluationType === 'pedagogico') {
        const pedBadge = badges.querySelector('span:last-child');
        if (status === 1) {
          pedBadge.className = 'badge bg-success ms-2';
          pedBadge.textContent = 'Avaliação Pedagógica: Aprovado';
        } else if (status === 0) {
          pedBadge.className = 'badge bg-danger ms-2';
          pedBadge.textContent = 'Avaliação Pedagógica: Reprovado';
        } else {
          pedBadge.className = 'badge bg-warning ms-2';
          pedBadge.textContent = 'Avaliação Pedagógica: Pendente';
        }
      }

      // Update the main status badge at the top
      if (verification) {
        updateMainStatusBadge(container, verification);
      }
    }

    function updateMainStatusBadge(container, verification) {
      const mainStatusBadge = container.closest('.discipline-item').querySelector('.user-status');
      const geseEval = verification.gese_evaluation;
      const pedEval = verification.pedagogico_evaluation;

      let statusText = 'Aguardando';
      let statusClass = 'status-pending';

      if (geseEval !== null && pedEval !== null) {
        if (geseEval === 1 && pedEval === 1) {
          statusText = 'Apto';
          statusClass = 'status-approved';
        } else if (geseEval === 0 || pedEval === 0) {
          statusText = 'Inapto';
          statusClass = 'status-not-approved';
        } else {
          statusText = 'Em avaliação';
          statusClass = 'status-pending';
        }
      } else if (geseEval !== null || pedEval !== null) {
        statusText = 'Em avaliação';
        statusClass = 'status-pending';
      }

      mainStatusBadge.className = 'user-status ' + statusClass + ' ms-3';
      mainStatusBadge.textContent = statusText;
    }

    function updateButtonStates(container, evaluationType, newStatus) {
      // Find all buttons for this evaluation type
      const buttons = container.querySelectorAll(`button[onclick*="${evaluationType}"]`);

      buttons.forEach(btn => {
        const btnStatus = btn.onclick.toString().match(/,\s*(\d+|null)\s*\)/);
        if (btnStatus) {
          const btnStatusValue = btnStatus[1] === 'null' ? null : parseInt(btnStatus[1]);

          // Disable the button that matches the new status, enable others
          if (btnStatusValue === newStatus) {
            btn.disabled = true;
          } else {
            btn.disabled = false;
          }

          // Restore original button text
          restoreButtonText(btn, evaluationType, btnStatusValue);
        }
      });
    }

    function restoreButtonText(btn, evaluationType, status) {
      // Restore original button text based on type and status
      const icons = {
        1: '<i class="fas fa-check"></i>',
        0: '<i class="fas fa-times"></i>',
        null: '<i class="fas fa-undo"></i>'
      };

      const texts = {
        gese: {
          1: 'Aprovar Documentação',
          0: 'Reprovar Documentação',
          null: 'Resetar'
        },
        pedagogico: {
          1: 'Aprovar Pedagogia',
          0: 'Reprovar Pedagogia',
          null: 'Resetar'
        }
      };

      const icon = icons[status] || icons[null];
      const text = texts[evaluationType]?.[status] || 'Resetar';

      btn.innerHTML = `${icon} ${text}`;
    }

    function showNotification(message, type) {
      // Create notification element
      const alertDiv = document.createElement('div');
      alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
      alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
      alertDiv.innerHTML = `
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
      document.body.appendChild(alertDiv);

      // Auto-remove after 5 seconds
      setTimeout(() => {
        alertDiv.remove();
      }, 5000);
    }
  <?php endif; ?>

  async function updateEvaluationForActivity(teacherId, disciplineId, activityId, evaluationType, status) {
    try {
      const response = await fetch('../backend/api/update_teacher_evaluation.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          teacher_id: teacherId,
          discipline_id: disciplineId,
          activity_id: activityId,
          evaluation_type: evaluationType,
          status: status
        })
      });

      const result = await response.json();

      if (result.success) {
        alert('Avaliação atualizada com sucesso!');
        location.reload();
      } else {
        alert('Erro ao atualizar avaliação: ' + (result.message || 'Erro desconhecido'));
      }
    } catch (error) {
      console.error('Error:', error);
      alert('Erro ao atualizar avaliação: ' + error.message);
    }
  }

  function toggleDiscipline(id) {
    const element = document.getElementById(id);
    const header = document.getElementById('header-' + id);

    if (!element || !header) {
      console.error('Element not found:', id);
      return;
    }

    if (element.classList.contains('show')) {
      element.classList.remove('show');
      header.classList.remove('active');
    } else {
      element.classList.add('show');
      header.classList.add('active');
    }
  }

  function toggleCourse(id) {
    const element = document.getElementById(id);
    const header = document.getElementById('header-' + id);

    if (!element || !header) {
      console.error('Element not found:', id);
      return;
    }

    if (element.classList.contains('show')) {
      element.classList.remove('show');
      header.classList.remove('active');
    } else {
      element.classList.add('show');
      header.classList.add('active');
    }
  }
</script>

<?php include '../components/footer.php';
ob_end_flush();
?>