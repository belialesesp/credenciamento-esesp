<?php
// pages/docente.php

require_once '../init.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
  // For AJAX requests, return error message
  if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    echo '<div class="alert alert-danger">Sessão expirada. Por favor, faça login novamente.</div>';
    exit();
  } else {
    header('Location: login.php');
    exit();
  }
}

// Check if this is an AJAX request
$is_ajax_request = isset($_GET['ajax']) && $_GET['ajax'] == 1;

// Include styles and header only if not AJAX request
if (!$is_ajax_request) {
  echo '<link rel="stylesheet" href="../styles/user.css">';
  include '../components/header.php';
}

require_once '../pdf/assets/title_case.php';
require_once '../backend/services/teacher.service.php';

// Initialize database connection
$connection = new Database();
$conn = $connection->connect();

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
    WHERE u.id = ? AND ur.role = 'docente'
");
$stmt->execute([$requested_id]);

if (!$stmt->fetch()) {
  if ($is_ajax_request) {
    echo '<div class="alert alert-danger">Docente não encontrado.</div>';
    exit();
  } else {
    header('Location: docentes.php');
    exit();
  }
}

$is_own_profile = false;
if ($is_admin) {
  // Admin can access any profile
  $is_own_profile = false;
} elseif (hasRole('docente') && $_SESSION['user_id'] == $requested_id) {
  // User with 'docente' role viewing their own profile
  $is_own_profile = true;
} elseif ($_SESSION['user_id'] == $requested_id) {
  // Any user viewing their own profile (backward compatibility)
  $is_own_profile = true;
} else {
  // Not authorized
  if ($is_ajax_request) {
    echo '<div class="alert alert-danger">
                <h5>Acesso não autorizado</h5>
                <p>Você não tem permissão para visualizar este perfil.</p>
                <p>Possíveis razões:</p>
                <ul>
                    <li>Este não é o seu perfil</li>
                    <li>Você não possui a role "docente"</li>
                    <li>Você não é um administrador</li>
                </ul>
                <p>Debug Info:</p>
                <small>
                    Seu ID: ' . $_SESSION['user_id'] . '<br>
                    ID Requisitado: ' . $requested_id . '<br>
                    Suas Roles: ' . implode(', ', $_SESSION['user_roles'] ?? []) . '<br>
                    É Admin: ' . ($is_admin ? 'Sim' : 'Não') . '
                </small>
              </div>';
    exit();
  } else {
    header('Location: home.php');
    exit();
  }
}

// Use existing connection
$teacherService = new TeacherService($conn);

try {
  $teacher = $teacherService->getTeacher($requested_id);

  if (!$teacher) {
    if ($is_ajax_request) {
      echo '<div class="alert alert-danger">Docente não encontrado.</div>';
      exit();
    } else {
      ob_start();
      include '../components/header.php';
      echo '<div class="container">
                  <h1 class="main-title">Erro</h1>
                  <p>Docente não encontrado.</p>
                  <a href="docentes.php" class="btn btn-primary">Voltar para lista de docentes</a>
                </div>';
      include '../components/footer.php';
      ob_end_flush();
      exit();
    }
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
  $activities = $teacher->activities;
  $lectures = $teacher->lectures;
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
  $path = $file_path;
} catch (Exception $e) {
  if ($is_ajax_request) {
    echo '<div class="alert alert-danger">Erro ao carregar dados do docente: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit();
  } else {
    ob_start();
    include '../components/header.php';
    echo '<div class="container">
              <h1 class="main-title">Erro</h1>
              <p>Erro ao carregar dados do docente: ' . htmlspecialchars($e->getMessage()) . '</p>
              <a href="docentes.php" class="btn btn-primary">Voltar para lista de docentes</a>
            </div>';
    include '../components/footer.php';
    ob_end_flush();
    exit();
  }
}

// For AJAX requests, we'll output only the content without the container
if ($is_ajax_request) {
  // Start output buffering to capture the content
  ob_start();
?>

  <?php if ($is_admin): ?>
    <a href="docentes.php" class="back-link">Voltar</a>
  <?php endif; ?>

  <h1 class="main-title">Dados do Docente</h1>

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
      <p class="col-12"><?= htmlspecialchars((string)$address) ?></p>
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
    <?php if (!empty($disciplines)): ?>
      <?php foreach ($disciplines as $discipline): ?>
        <?php
        // Handle both getter methods and public properties
        $disc_id = method_exists($discipline, 'getId') ? $discipline->getId() : (property_exists($discipline, 'id') ? $discipline->id : 0);
        $disc_name = method_exists($discipline, 'getName') ? $discipline->getName() : (property_exists($discipline, 'name') ? $discipline->name : 'Nome não disponível');
        $disc_eixo = method_exists($discipline, 'getEixo') ? $discipline->getEixo() : (property_exists($discipline, 'eixo') ? $discipline->eixo : null);
        $disc_estacao = method_exists($discipline, 'getEstacao') ? $discipline->getEstacao() : (property_exists($discipline, 'estacao') ? $discipline->estacao : null);
        $disc_modules = method_exists($discipline, 'getModules') ? $discipline->getModules() : (property_exists($discipline, 'modules') ? $discipline->modules : []);

        // Get evaluation status for display only
        $gese_eval = $discipline->gese_evaluation ?? null;
        $ped_eval = $discipline->pedagogico_evaluation ?? null;

        // Determine final status based on evaluations
        $finalStatusText = 'Aguardando';
        $finalStatusClass = 'status-pending';

        if ($gese_eval !== null && $ped_eval !== null) {
          if ($gese_eval === 1 && $ped_eval === 1) {
            $finalStatusText = 'Apto';
            $finalStatusClass = 'status-approved';
          } elseif ($gese_eval === 0 || $ped_eval === 0) {
            $finalStatusText = 'Inapto';
            $finalStatusClass = 'status-not-approved';
          }
        } elseif ($gese_eval !== null || $ped_eval !== null) {
          $finalStatusText = 'Em avaliação';
          $finalStatusClass = 'status-pending';
        }
        ?>

        <div class="discipline-item mb-3">
          <div class="discipline-header">
            <p><strong><?= htmlspecialchars($disc_name) ?></strong>
              <span class="user-status <?= $finalStatusClass ?> ms-3"><?= $finalStatusText ?></span>
            </p>
          </div>

          <?php if ($disc_eixo || $disc_estacao): ?>
            <div class="discipline-details">
              <p class="text-muted">
                <?php if ($disc_eixo): ?>Eixo: <?= htmlspecialchars($disc_eixo) ?><?php endif; ?>
                <?php if ($disc_eixo && $disc_estacao): ?> | <?php endif; ?>
                <?php if ($disc_estacao): ?>Estação: <?= htmlspecialchars($disc_estacao) ?><?php endif; ?>
              </p>
            </div>
          <?php endif; ?>

          <?php if (!empty($disc_modules)): ?>
            <p class="text-muted">Módulos: <?= implode(', ', $disc_modules) ?></p>
          <?php endif; ?>

          <!-- Show evaluation status badges for user's information only -->
          <div class="evaluation-status mt-2">
            <span class="badge <?= $gese_eval === 1 ? 'bg-success' : ($gese_eval === 0 ? 'bg-danger' : 'bg-warning') ?>">
              Avaliação Documental: <?= $gese_eval === 1 ? 'Aprovado' : ($gese_eval === 0 ? 'Reprovado' : 'Pendente') ?>
            </span>
            <span class="badge <?= $ped_eval === 1 ? 'bg-success' : ($ped_eval === 0 ? 'bg-danger' : 'bg-warning') ?> ms-2">
              Avaliação Pedagógica: <?= $ped_eval === 1 ? 'Aprovado' : ($ped_eval === 0 ? 'Reprovado' : 'Pendente') ?>
            </span>
          </div>
        </div>
      <?php endforeach ?>
    <?php else: ?>
      <p>Nenhum curso cadastrado.</p>
    <?php endif; ?>
  </div>

  <?php if (!empty($lectures)): ?>
    <div class="info-section">
      <h3>Palestras</h3>
      <?php foreach ($lectures as $lecture): ?>
        <div class="lecture-item mb-3">
          <p><strong><?= titleCase($lecture->name) ?></strong></p>
          <p><?= $lecture->details ?></p>
        </div>
      <?php endforeach ?>
    </div>
  <?php endif; ?>

  <div class="info-section">
    <h3>Categoria, Atividades e Serviços</h3>
    <?php if (!empty($activities)): ?>
      <ul>
        <?php foreach ($activities as $activity): ?>
          <li><?= $activity['name'] ?></li>
        <?php endforeach ?>
      </ul>
    <?php else: ?>
      <p>Nenhuma atividade cadastrada.</p>
    <?php endif; ?>
  </div>

  <script>
    // Function to check if user can send invites (admin or GEDTH only)
    function canSendInvites() {
      return userRoles.includes('admin') || userRoles.includes('gedth');
    }

    // Function to check if user can view/edit contract info (admin or GESE only)
    function canViewContractInfo() {
      return (userRoles.includes('admin') && !userRoles.includes('gedth')) || userRoles.includes('gese');
    }

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
                // Restore original button text
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

      function showNotification(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
        alertDiv.style.zIndex = '9999';
        alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
        document.body.appendChild(alertDiv);

        setTimeout(() => {
          alertDiv.remove();
        }, 5000);
      }

      function updateTeacherStatus(userId, status) {
        if (confirm('Tem certeza que deseja ' + (status ? 'aprovar' : 'reprovar') + ' este docente?')) {
          fetch('../backend/api/update_teacher_status.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                user_id: userId,
                status: status
              })
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                alert('Status geral atualizado com sucesso!');
                location.reload();
              } else {
                alert('Erro ao atualizar status: ' + (data.message || 'Erro desconhecido'));
              }
            })
            .catch(error => {
              alert('Erro ao processar requisição');
              console.error('Error:', error);
            });
        }
      }
    <?php endif; ?>
  </script>

<?php
  // For AJAX requests, output the buffered content and exit
  $content = ob_get_clean();
  echo $content;
  exit();
} else {
  // For non-AJAX requests, output the full page
  ob_start();
?>

  <div class="container container-user">

    <?php if ($is_admin): ?>
      <a href="docentes.php" class="back-link">Voltar</a>
    <?php endif; ?>

    <h1 class="main-title">Dados do Docente</h1>

    <!-- The rest of the content remains the same as in the AJAX version above -->
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
        <p class="col-12"><?= htmlspecialchars((string)$address) ?></p>
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
      <?php if (!empty($disciplines)): ?>
        <?php foreach ($disciplines as $discipline): ?>
          <?php
          // Handle both getter methods and public properties
          $disc_id = method_exists($discipline, 'getId') ? $discipline->getId() : (property_exists($discipline, 'id') ? $discipline->id : 0);
          $disc_name = method_exists($discipline, 'getName') ? $discipline->getName() : (property_exists($discipline, 'name') ? $discipline->name : 'Nome não disponível');
          $disc_enabled = method_exists($discipline, 'getEnabled') ? $discipline->getEnabled() : (property_exists($discipline, 'enabled') ? $discipline->enabled : null);
          $disc_eixo = method_exists($discipline, 'getEixo') ? $discipline->getEixo() : (property_exists($discipline, 'eixo') ? $discipline->eixo : null);
          $disc_estacao = method_exists($discipline, 'getEstacao') ? $discipline->getEstacao() : (property_exists($discipline, 'estacao') ? $discipline->estacao : null);
          $disc_modules = method_exists($discipline, 'getModules') ? $discipline->getModules() : (property_exists($discipline, 'modules') ? $discipline->modules : []);

          // Get evaluation status
          $gese_eval = $discipline->gese_evaluation ?? null;
          $ped_eval = $discipline->pedagogico_evaluation ?? null;

          // Determine final status based on evaluations
          $finalStatusText = 'Aguardando';
          $finalStatusClass = 'status-pending';

          if ($gese_eval !== null || $ped_eval !== null) {
            if ($gese_eval === 1 && $ped_eval === 1) {
              $finalStatusText = 'Apto';
              $finalStatusClass = 'status-approved';
            } elseif ($gese_eval === 0 || $ped_eval === 0) {
              $finalStatusText = 'Inapto';
              $finalStatusClass = 'status-not-approved';
            } else {
              $finalStatusText = 'Em avaliação';
              $finalStatusClass = 'status-pending';
            }
          }
          ?>

          <div class="discipline-item mb-3">
            <div class="discipline-header">
              <p><strong><?= htmlspecialchars($disc_name) ?></strong>
                <span class="user-status <?= $finalStatusClass ?> ms-3"><?= $finalStatusText ?></span>
              </p>
            </div>

            <?php if ($disc_eixo || $disc_estacao): ?>
              <div class="discipline-details">
                <p class="text-muted">
                  <?php if ($disc_eixo): ?>Eixo: <?= htmlspecialchars($disc_eixo) ?><?php endif; ?>
                  <?php if ($disc_eixo && $disc_estacao): ?> | <?php endif; ?>
                  <?php if ($disc_estacao): ?>Estação: <?= htmlspecialchars($disc_estacao) ?><?php endif; ?>
                </p>
              </div>
            <?php endif; ?>

            <?php if (!empty($disc_modules)): ?>
              <p class="text-muted">Módulos: <?= implode(', ', $disc_modules) ?></p>
            <?php endif; ?>

            <?php
            // Check if user should see evaluation buttons
            $show_gese = (isGESE() || (isAdmin() && !isGEDTH())) && !isGEDTH();
            $show_ped = (isPedagogico() || (isAdmin() && !isGEDTH())) && !isGEDTH();
            ?>

            <?php if ($show_gese || $show_ped): ?>
              <div class="discipline-actions mt-2">

                <!-- Show evaluation status badges -->
                <div class="evaluation-status mb-2">
                  <span class="badge <?= $gese_eval === 1 ? 'bg-success' : ($gese_eval === 0 ? 'bg-danger' : 'bg-warning') ?>">
                    Avaliação Documental: <?= $gese_eval === 1 ? 'Aprovado' : ($gese_eval === 0 ? 'Reprovado' : 'Pendente') ?>
                  </span>
                  <span class="badge <?= $ped_eval === 1 ? 'bg-success' : ($ped_eval === 0 ? 'bg-danger' : 'bg-warning') ?> ms-2">
                    Avaliação Pedagógica: <?= $ped_eval === 1 ? 'Aprovado' : ($ped_eval === 0 ? 'Reprovado' : 'Pendente') ?>
                  </span>
                </div>

                <!-- GESE Evaluation Buttons -->
                <?php if ($show_gese): ?>
                  <div class="mb-2">
                    <label class="form-label fw-bold">Avaliação Documental (GESE):</label>
                    <button class="btn btn-success btn-sm me-2"
                      onclick="updateEvaluation(<?= $requested_id ?>, <?= $disc_id ?>, 'gese', 1)"
                      <?= $gese_eval === 1 ? 'disabled' : '' ?>>
                      <i class="fas fa-check"></i> Aprovar Documentação
                    </button>
                    <button class="btn btn-danger btn-sm me-2"
                      onclick="updateEvaluation(<?= $requested_id ?>, <?= $disc_id ?>, 'gese', 0)"
                      <?= $gese_eval === 0 ? 'disabled' : '' ?>>
                      <i class="fas fa-times"></i> Reprovar Documentação
                    </button>
                    <button class="btn btn-secondary btn-sm"
                      onclick="updateEvaluation(<?= $requested_id ?>, <?= $disc_id ?>, 'gese', null)"
                      <?= $gese_eval === null ? 'disabled' : '' ?>>
                      <i class="fas fa-undo"></i> Resetar
                    </button>
                  </div>
                <?php endif; ?>

                <!-- Pedagogico Evaluation Buttons -->
                <?php if ($show_ped): ?>
                  <div>
                    <label class="form-label fw-bold">Avaliação Pedagógica (Pedagógico):</label>
                    <button class="btn btn-success btn-sm me-2"
                      onclick="updateEvaluation(<?= $requested_id ?>, <?= $disc_id ?>, 'pedagogico', 1)"
                      <?= $ped_eval === 1 ? 'disabled' : '' ?>>
                      <i class="fas fa-check"></i> Aprovar Pedagogia
                    </button>
                    <button class="btn btn-danger btn-sm me-2"
                      onclick="updateEvaluation(<?= $requested_id ?>, <?= $disc_id ?>, 'pedagogico', 0)"
                      <?= $ped_eval === 0 ? 'disabled' : '' ?>>
                      <i class="fas fa-times"></i> Reprovar Pedagogia
                    </button>
                    <button class="btn btn-secondary btn-sm"
                      onclick="updateEvaluation(<?= $requested_id ?>, <?= $disc_id ?>, 'pedagogico', null)"
                      <?= $ped_eval === null ? 'disabled' : '' ?>>
                      <i class="fas fa-undo"></i> Resetar
                    </button>
                  </div>
                <?php endif; ?>




              </div>
            <?php endif; ?>
          </div>
        <?php endforeach ?>
      <?php else: ?>
        <p>Nenhum curso cadastrado.</p>
      <?php endif; ?>
    </div>

    <?php if (!empty($lectures)): ?>
      <div class="info-section">
        <h3>Palestras</h3>
        <?php foreach ($lectures as $lecture): ?>
          <div class="lecture-item mb-3">
            <p><strong><?= titleCase($lecture->name) ?></strong></p>
            <p><?= $lecture->details ?></p>
          </div>
        <?php endforeach ?>
      </div>
    <?php endif; ?>

    <div class="info-section">
      <h3>Categoria, Atividades e Serviços</h3>
      <?php if (!empty($activities)): ?>
        <ul>
          <?php foreach ($activities as $activity): ?>
            <li><?= $activity['name'] ?></li>
          <?php endforeach ?>
        </ul>
      <?php else: ?>
        <p>Nenhuma atividade cadastrada.</p>
      <?php endif; ?>
    </div>

    <div class="info-section">
      <h3>Documentos</h3>
      <?php if (!empty($path)): ?>
        <a href="../<?= $path ?>" target="_blank">Download</a>
      <?php else: ?>
        <p>Nenhum documento disponível.</p>
        <?php if ($is_admin): ?>
          <div class="text-muted small">
            <div>Debug Info:</div>
            <div>Full Path: <?= htmlspecialchars($teacher->file_path ?? 'NULL') ?></div>
            <div>File Exists: <?= file_exists('../' . $teacher->file_path) ? 'Yes' : 'No' ?></div>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <?php if ($is_own_profile || isFirstLogin()): ?>
      <div class="info-section">
        <h3>Alterar Senha</h3>

        <?php if (isset($_SESSION['password_error'])): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['password_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php unset($_SESSION['password_error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['password_success'])): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['password_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php unset($_SESSION['password_success']); ?>
        <?php endif; ?>

        <?php if (isFirstLogin()): ?>
          <div class="alert alert-warning">
            <strong>Primeiro acesso!</strong> Por segurança, recomendamos que você altere sua senha.
          </div>
        <?php endif; ?>

        <form method="post" action="../auth/process_change_password.php" class="needs-validation" novalidate>
          <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">

          <div class="row">
            <div class="col-md-12 mb-3">
              <label for="current_password">Senha Atual</label>
              <div class="input-group">
                <input type="password" class="form-control" id="current_password"
                  name="current_password" required>
                <button class="btn btn-outline-secondary" type="button"
                  onclick="togglePassword('current_password')" tabindex="-1">
                  <i class="fas fa-eye" id="current_password_icon"></i>
                </button>
              </div>
              <small class="form-text text-muted">
                Se é seu primeiro acesso, use seu CPF (apenas números)
              </small>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="new_password">Nova Senha</label>
              <div class="input-group">
                <input type="password" class="form-control" id="new_password"
                  name="new_password" required minlength="8">
                <button class="btn btn-outline-secondary" type="button"
                  onclick="togglePassword('new_password')" tabindex="-1">
                  <i class="fas fa-eye" id="new_password_icon"></i>
                </button>
              </div>
              <small class="form-text text-muted">
                Mínimo 8 caracteres, com letras maiúsculas, minúsculas, números e símbolos (@$!%*?&)
              </small>
            </div>

            <div class="col-md-6 mb-3">
              <label for="confirm_password">Confirmar Nova Senha</label>
              <div class="input-group">
                <input type="password" class="form-control" id="confirm_password"
                  name="confirm_password" required>
                <button class="btn btn-outline-secondary" type="button"
                  onclick="togglePassword('confirm_password')" tabindex="-1">
                  <i class="fas fa-eye" id="confirm_password_icon"></i>
                </button>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-primary">Alterar Senha</button>
        </form>
      </div>
    <?php endif; ?>

  </div>
  <script>
    const userRoles = <?php echo json_encode($_SESSION['user_roles'] ?? []); ?>;

    function canSendInvites() {
      return userRoles.includes('admin') || userRoles.includes('gedth');
    }

    function canViewContractInfo() {
      return userRoles.includes('admin') || userRoles.includes('gese');
    }

    // For page access check:
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

      // Add this JavaScript at the bottom of docente.php, inside the <script> tags

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

        // Show loading state
        const buttons = event.target.parentElement.querySelectorAll('button');
        buttons.forEach(btn => btn.disabled = true);

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
              // Show success message
              showNotification(`${evaluationLabel} atualizada com sucesso!`, 'success');

              // Reload the page after a short delay
              setTimeout(() => {
                window.location.reload();
              }, 1500);
            } else {
              showNotification('Erro ao atualizar avaliação: ' + (data.message || 'Erro desconhecido'), 'danger');
              buttons.forEach(btn => btn.disabled = false);
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showNotification('Erro ao processar solicitação', 'danger');
            buttons.forEach(btn => btn.disabled = false);
          });
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

      // Keep the old function for backward compatibility
      function updateDisciplineStatus(teacherId, disciplineId, status) {
        const statusText = status === 1 ? 'aprovar' : (status === 0 ? 'reprovar' : 'resetar');

        if (!confirm(`Tem certeza que deseja ${statusText} este docente para esta disciplina?`)) {
          return;
        }

        fetch('../backend/api/update_teacher_discipline_status.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              teacher_id: teacherId,
              discipline_id: disciplineId,
              status: status
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              showNotification('Status atualizado com sucesso!', 'success');
              setTimeout(() => {
                window.location.reload();
              }, 1500);
            } else {
              showNotification('Erro ao atualizar status: ' + (data.message || 'Erro desconhecido'), 'danger');
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showNotification('Erro ao processar solicitação', 'danger');
          });
      }

      function updateTeacherStatus(userId, status) {
        if (confirm('Tem certeza que deseja ' + (status ? 'aprovar' : 'reprovar') + ' este docente?')) {
          fetch('../backend/api/update_teacher_status.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                user_id: userId,
                status: status
              })
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                alert('Status geral atualizado com sucesso!');
                location.reload();
              } else {
                alert('Erro ao atualizar status: ' + (data.message || 'Erro desconhecido'));
              }
            })
            .catch(error => {
              alert('Erro ao processar requisição');
              console.error('Error:', error);
            });
        }
      }
    <?php endif; ?>
  </script>
<?php include '../components/footer.php';
  ob_end_flush();
}
?>