<?php
// pages/interprete.php - FIXED VERSION

require_once '../init.php';
require_once '../backend/classes/database.class.php';
require_once '../pdf/assets/title_case.php';

// Check authentication
requireLogin();

// Get database connection
$conection = new Database();
$conn = $conection->connect();

// Check admin status
$isAdmin = false;
if (isset($_SESSION['user_roles']) && is_array($_SESSION['user_roles'])) {
  $is_admin = isAdministrativeRole();
}

// Get requested ID from URL
$requested_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verify the requested user exists and is an interpreter
$stmt = $conn->prepare("
    SELECT u.id 
    FROM user u
    INNER JOIN user_roles ur ON ur.user_id = u.id
    WHERE u.id = ? AND ur.role = 'interprete' AND u.enabled = 1
");
$stmt->execute([$requested_id]);

if (!$stmt->fetch()) {
  header('Location: interpretes.php');
  exit();
}

// Check access permissions
$is_own_profile = false;
if ($is_admin) {
  $is_own_profile = false;
} elseif (hasRole('interprete') && $_SESSION['user_id'] == $requested_id) {
  $is_own_profile = true;
} else {
  header('Location: home.php');
  exit();
}

// NOW fetch the interpreter data from the USER table, not interpreter table
$sql = "
    SELECT 
        u.id,
        u.name,
        u.email,
        u.special_needs,
        u.document_number,
        u.document_emissor,
        u.document_uf,
        u.phone,
        u.cpf,
        u.created_at,
        u.called_at,
        u.street,
        u.city,
        u.state,
        u.zip_code,
        u.number,
        u.complement,
        u.neighborhood,
        u.scholarship,
        u.enabled,
        u.gese_evaluation,
        u.pedagogico_evaluation,
        d.path AS file_path
    FROM user u
    LEFT JOIN documents d ON d.user_id = u.id
    WHERE u.id = :id
";

try {
  $stmt = $conn->prepare($sql);
  $stmt->execute([':id' => $requested_id]);
  $interpreter = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$interpreter) {
    throw new Exception("Intérprete não encontrado");
  }

  // Extract data
  $name = $interpreter['name'];
  $document_number = $interpreter['document_number'];
  $document_emissor = $interpreter['document_emissor'];
  $document_uf = $interpreter['document_uf'];
  $phone = $interpreter['phone'];
  $cpf = $interpreter['cpf'];
  $email = $interpreter['email'];
  $special_needs = $interpreter['special_needs'];
  $created_at = $interpreter['created_at'];
  $called_at = $interpreter['called_at'];
  $enabled = $interpreter['enabled'];
  $scholarship = $interpreter['scholarship'] ?? '';

  // Address
  $address = $interpreter['street'] ?? '';
  $city = $interpreter['city'] ?? '';
  $state = $interpreter['state'] ?? '';
  $zip = $interpreter['zip_code'] ?? '';

  // Document path
  $file_path = $interpreter['file_path'] ?? '';

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

  // Format called_at date
  $calledDateF = '';
  if ($called_at) {
    $calledDate = new DateTime($called_at);
    $calledDateF = $calledDate->format('d/m/Y H:i');
  } else {
    $calledDateF = 'Não chamado';
  }

  // Format filepath
  $path = '';
  if ($file_path) {
    $string = $file_path;
    $position = strpos($string, "interpretes");
    if ($position !== false) {
      $start = $position + strlen("interpretes/");
      $path = substr($string, $start);
    }
  }

  // Get evaluation status directly from the query result
  $gese_eval = $interpreter['gese_evaluation'] ?? null;
  $ped_eval = $interpreter['pedagogico_evaluation'] ?? null;
} catch (Exception $e) {
  // Include header only for error display
  echo '<link rel="stylesheet" href="../styles/user.css">';
  include '../components/header.php';

  echo '<div class="container">
            <h1 class="main-title">Erro</h1>
            <p>Erro ao carregar dados: ' . htmlspecialchars($e->getMessage()) . '</p>
            <a href="interpretes.php" class="btn btn-primary">Voltar para lista de intérpretes</a>
          </div>';
  include '../components/footer.php';
  exit();
}

// MOVED: Include styles and header AFTER all redirects and data loading
echo '<link rel="stylesheet" href="../styles/user.css">';
include '../components/header.php';
?>

<div class="container container-user">
  <?php if ($is_own_profile): ?>
    <div class="alert alert-info d-flex justify-content-between align-items-center mb-3">
      <span>Bem-vindo(a) ao seu perfil, <?= titleCase($name) ?>!</span>
      <a href="../auth/logout.php" class="btn btn-danger btn-sm">Sair</a>
    </div>
  <?php endif; ?>

  <?php if ($is_admin): ?>
    <a href="interpretes.php" class="back-link">Voltar</a>
  <?php endif; ?>

  <h1 class="main-title">Dados do Intérprete</h1>

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
      <div class="col-3">
        <p class="col-12"><strong>Data de Chamamento</strong></p>
        <p class="col-12"><?= $calledDateF ?></p>
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
    <h3>Documentos</h3>
    <?php if (!empty($path)): ?>
      <a href="../backend/documentos/interpretes/<?= $path ?>" target="_blank">Download</a>
    <?php else: ?>
      <p>Nenhum documento disponível.</p>
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

  <?php if ($is_admin): ?>
    <div class="info-section">
      <h3>Avaliação</h3>
      <div class="row">
        <div class="col-12">
          <p><strong>Status Atual:</strong>
            <span class="user-status <?= $statusClass ?>"><?= $statusText ?></span>
          </p>
        </div>
      </div>

      <?php
      // Get evaluation status
      $gese_eval = $interpreter['gese_evaluation'] ?? null;
      $ped_eval = $interpreter['pedagogico_evaluation'] ?? null;

      $show_gese = (isGESE() || (isAdmin() && !isGEDTH())) && !isGEDTH();
      $show_ped = (isPedagogico() || (isAdmin() && !isGEDTH())) && !isGEDTH();
      ?>

      <!-- Show evaluation status badges -->
      <div class="evaluation-status mb-3">
        <span class="badge <?= $gese_eval === 1 ? 'bg-success' : ($gese_eval === 0 ? 'bg-danger' : 'bg-warning') ?>">
          Avaliação Documental: <?= $gese_eval === 1 ? 'Aprovado' : ($gese_eval === 0 ? 'Reprovado' : 'Pendente') ?>
        </span>
        <span class="badge <?= $ped_eval === 1 ? 'bg-success' : ($ped_eval === 0 ? 'bg-danger' : 'bg-warning') ?> ms-2">
          Avaliação Pedagógica: <?= $ped_eval === 1 ? 'Aprovado' : ($ped_eval === 0 ? 'Reprovado' : 'Pendente') ?>
        </span>
      </div>

      <!-- GESE Evaluation Buttons -->
      <?php if ($show_gese): ?>
        <div class="mb-3">
          <label class="form-label fw-bold">Avaliação Documental (GESE):</label>
          <div class="btn-group" role="group">
            <button type="button"
              class="btn btn-success"
              onclick="updateEvaluation(<?= $requested_id ?>, 'gese', 1)"
              <?= $gese_eval === 1 ? 'disabled' : '' ?>>
              <i class="fas fa-check"></i> Aprovar Documentação
            </button>
            <button type="button"
              class="btn btn-danger"
              onclick="updateEvaluation(<?= $requested_id ?>, 'gese', 0)"
              <?= $gese_eval === 0 ? 'disabled' : '' ?>>
              <i class="fas fa-times"></i> Reprovar Documentação
            </button>
            <button type="button"
              class="btn btn-secondary"
              onclick="updateEvaluation(<?= $requested_id ?>, 'gese', null)"
              <?= $gese_eval === null ? 'disabled' : '' ?>>
              <i class="fas fa-undo"></i> Resetar
            </button>
          </div>
        </div>
      <?php endif; ?>

      <!-- Pedagogico Evaluation Buttons -->
      <?php if ($show_ped): ?>
        <div class="mb-3">
          <label class="form-label fw-bold">Avaliação Pedagógica (Pedagógico):</label>
          <div class="btn-group" role="group">
            <button type="button"
              class="btn btn-success"
              onclick="updateEvaluation(<?= $requested_id ?>, 'pedagogico', 1)"
              <?= $ped_eval === 1 ? 'disabled' : '' ?>>
              <i class="fas fa-check"></i> Aprovar Pedagogia
            </button>
            <button type="button"
              class="btn btn-danger"
              onclick="updateEvaluation(<?= $requested_id ?>, 'pedagogico', 0)"
              <?= $ped_eval === 0 ? 'disabled' : '' ?>>
              <i class="fas fa-times"></i> Reprovar Pedagogia
            </button>
            <button type="button"
              class="btn btn-secondary"
              onclick="updateEvaluation(<?= $requested_id ?>, 'pedagogico', null)"
              <?= $ped_eval === null ? 'disabled' : '' ?>>
              <i class="fas fa-undo"></i> Resetar
            </button>
          </div>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<?php
include '../components/footer.php';
?>

<script>
  // Always add these at the top:
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

  // Password validation
  document.getElementById('confirm_password')?.addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    if (newPassword !== this.value) {
      this.setCustomValidity('As senhas devem ser iguais');
    } else {
      this.setCustomValidity('');
    }
  });

  <?php if ($is_admin): ?>

    function updateEvaluation(userId, evaluationType, status) {
      const evaluationLabels = {
        'gese': 'Avaliação Documental',
        'pedagogico': 'Avaliação Pedagógica'
      };

      const statusText = status === 1 ? 'aprovar' : (status === 0 ? 'reprovar' : 'resetar');
      const evaluationLabel = evaluationLabels[evaluationType] || evaluationType;

      if (!confirm(`Tem certeza que deseja ${statusText} a ${evaluationLabel}?`)) {
        return;
      }

      const clickedButton = event.target;
      const buttonGroup = clickedButton.closest('.btn-group');
      const allButtons = buttonGroup.querySelectorAll('button');

      allButtons.forEach(btn => {
        btn.disabled = true;
        if (btn === clickedButton) {
          btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
        }
      });

      fetch('../backend/api/update_staff_evaluation.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            user_id: userId,
            user_type: 'interpreter',
            evaluation_type: evaluationType,
            status: status
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showNotification(`${evaluationLabel} atualizada com sucesso!`, 'success');

            updateEvaluationBadges(evaluationType, status, data.verification);
            updateButtonStates(buttonGroup, status);
          } else {
            showNotification('Erro: ' + (data.message || 'Erro desconhecido'), 'danger');
            allButtons.forEach(btn => {
              btn.disabled = false;
              restoreButtonText(btn, evaluationType, status);
            });
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showNotification('Erro ao processar solicitação', 'danger');
          allButtons.forEach(btn => {
            btn.disabled = false;
            restoreButtonText(btn, evaluationType, status);
          });
        });
    }

    function updateEvaluationBadges(evaluationType, status, verification) {
      const badges = document.querySelector('.evaluation-status');

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

      if (verification) {
        updateMainStatusBadge(verification);
      }
    }

    function updateMainStatusBadge(verification) {
      const mainStatusBadge = document.querySelector('.user-status');
      const geseEval = verification.gese_evaluation;
      const pedEval = verification.pedagogico_evaluation;

      let statusText = 'Aguardando aprovação';
      let statusClass = 'status-pending';

      if (geseEval !== null && pedEval !== null) {
        if (geseEval === 1 && pedEval === 1) {
          statusText = 'Apto';
          statusClass = 'status-approved';
        } else if (geseEval === 0 || pedEval === 0) {
          statusText = 'Inapto';
          statusClass = 'status-not-approved';
        }
      }

      mainStatusBadge.className = 'user-status ' + statusClass;
      mainStatusBadge.textContent = statusText;
    }

    function updateButtonStates(buttonGroup, newStatus) {
      const buttons = buttonGroup.querySelectorAll('button');

      buttons.forEach(btn => {
        const btnStatus = btn.onclick.toString().match(/,\s*(\d+|null)\s*\)/);
        if (btnStatus) {
          const btnStatusValue = btnStatus[1] === 'null' ? null : parseInt(btnStatus[1]);
          btn.disabled = (btnStatusValue === newStatus);
          restoreButtonText(btn, null, btnStatusValue);
        }
      });
    }

    function restoreButtonText(btn, evaluationType, status) {
      const icons = {
        1: '<i class="fas fa-check"></i>',
        0: '<i class="fas fa-times"></i>',
        null: '<i class="fas fa-undo"></i>'
      };

      const texts = {
        1: ['Aprovar Documentação', 'Aprovar Pedagogia'],
        0: ['Reprovar Documentação', 'Reprovar Pedagogia'],
        null: ['Resetar', 'Resetar']
      };

      const icon = icons[status] || icons[null];
      const isGese = btn.onclick.toString().includes("'gese'");
      const textIndex = isGese ? 0 : 1;
      const text = texts[status]?.[textIndex] || 'Resetar';

      btn.innerHTML = `${icon} ${text}`;
    }

    function showNotification(message, type) {
      const alertDiv = document.createElement('div');
      alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
      alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
      alertDiv.innerHTML = `
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;
      document.body.appendChild(alertDiv);
      setTimeout(() => alertDiv.remove(), 5000);
    }
  <?php endif; ?>
</script>

<?php
if ($is_ajax_request) {
  $content = ob_get_clean();
  echo $content;
  exit();
} else {
  include '../components/footer.php';
}
?>