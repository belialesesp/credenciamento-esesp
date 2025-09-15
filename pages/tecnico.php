<?php
// pages/tecnico.php - Complete version with AJAX support
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
require_once '../backend/services/technician.service.php';

// Initialize database connection
$connection = new Database();
$conn = $connection->connect();

// Check if user is admin
$is_admin = false;
if (isset($_SESSION['user_id'])) {
    $admin_check = $conn->prepare("
        SELECT COUNT(*) 
        FROM user_roles 
        WHERE user_id = ? AND role = 'admin'
    ");
    $admin_check->execute([$_SESSION['user_id']]);
    $is_admin = ($admin_check->fetchColumn() > 0);
}

// Get requested ID from URL
$requested_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verify the requested user exists and is a technician
$stmt = $conn->prepare("
    SELECT u.id 
    FROM user u
    INNER JOIN user_roles ur ON ur.user_id = u.id
    WHERE u.id = ? AND ur.role = 'tecnico' AND u.enabled = 1
");
$stmt->execute([$requested_id]);

if (!$stmt->fetch()) {
    if ($is_ajax_request) {
        echo '<div class="alert alert-danger">Técnico não encontrado.</div>';
        exit();
    } else {
        header('Location: tecnicos.php');
        exit();
    }
}

// Check access permissions
$is_own_profile = false;
if ($is_admin) {
    // Admin can access any profile
    $is_own_profile = false;
} elseif (hasRole('tecnico') && $_SESSION['user_id'] == $requested_id) {
    // User viewing their own profile
    $is_own_profile = true;
} else {
    // Not authorized
    if ($is_ajax_request) {
        echo '<div class="alert alert-danger">Acesso não autorizado.</div>';
        exit();
    } else {
        header('Location: home.php');
        exit();
    }
}

// Use the TechnicianService
$technicianService = new TechnicianService($conn);

try {
    $technician = $technicianService->getTechnician($requested_id);

    if (!$technician) {
        if ($is_ajax_request) {
            echo '<div class="alert alert-danger">Técnico não encontrado.</div>';
            exit();
        } else {
            echo '<div class="container">
                    <h1 class="main-title">Erro</h1>
                    <p>Técnico não encontrado.</p>
                    <a href="tecnicos.php" class="btn btn-primary">Voltar para lista de técnicos</a>
                  </div>';
            include '../components/footer.php';
            exit();
        }
    }

    // Extract technician data
    $name = $technician->getName();
    $cpf = $technician->getCpf();
    $email = $technician->getEmail();
    $phone = $technician->getPhone();
    $document_number = $technician->getDocumentNumber();
    $document_emissor = $technician->getDocumentEmissor();
    $document_uf = $technician->getDocumentUf();
    $scholarship = $technician->getScholarship();
    $special_needs = $technician->getSpecialNeeds();
    $enabled = $technician->getEnabled();
    $file_path = $technician->getFilePath();

    $createdAt = $technician->getCreatedAt();
    $calledAt = $technician->getCalledAt();

    $dateF = $createdAt ? date('d/m/Y', strtotime($createdAt)) : '—';
    $calledDateF = $calledAt ? date('d/m/Y', strtotime($calledAt)) : '—';

    $addressObj = $technician->getAddress();
    $addressStr = '';
    if ($addressObj) {
        $addressStr = titleCase($addressObj->getStreet()) . ', ' .
            titleCase($addressObj->getCity()) . ' - ' .
            strtoupper($addressObj->getState()) . ', CEP: ' .
            $addressObj->getZip();
    }

    // Status text and class
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

} catch (Exception $e) {
    if ($is_ajax_request) {
        echo '<div class="alert alert-danger">Erro ao carregar dados do técnico: ' . htmlspecialchars($e->getMessage()) . '</div>';
        exit();
    } else {
        echo '<div class="container">
                <h1 class="main-title">Erro</h1>
                <p>Erro ao carregar dados do técnico: ' . htmlspecialchars($e->getMessage()) . '</p>
                <a href="tecnicos.php" class="btn btn-primary">Voltar para lista de técnicos</a>
              </div>';
        include '../components/footer.php';
        exit();
    }
}

// For AJAX requests, output only the content
if ($is_ajax_request) {
    ob_start();
?>

<?php if ($is_own_profile): ?>
    <div class="alert alert-info d-flex justify-content-between align-items-center mb-3">
        <span>Bem-vindo(a) ao seu perfil, <?= titleCase($name) ?>!</span>
        <a href="../auth/logout.php" class="btn btn-danger btn-sm">Sair</a>
    </div>
<?php endif; ?>

<?php if ($is_admin): ?>
    <a href="tecnicos.php" class="back-link">Voltar</a>
<?php endif; ?>

<h1 class="main-title">Dados do Técnico</h1>

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
        <?php if ($calledDateF != '—'): ?>
            <div class="col-3">
                <p class="col-12"><strong>Data de Convocação</strong></p>
                <p class="col-12"><?= $calledDateF ?></p>
            </div>
        <?php endif; ?>
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
        <p class="col-12"><?= $addressStr ?></p>
    </div>
    <div class="row">
        <p class="col-12"><strong>Escolaridade</strong></p>
        <p class="col-12"><?= $scholarship ?></p>
    </div>
    <?php if ($special_needs && $special_needs != 'Não'): ?>
        <div class="row">
            <p class="col-12"><strong>Necessidades Especiais</strong></p>
            <p class="col-12"><?= $special_needs ?></p>
        </div>
    <?php endif; ?>
</div>

<div class="info-section">
    <h3>Documentos</h3>
    <?php if (!empty($file_path)): ?>
        <a href="../backend/documentos/tecnicos/<?= $file_path ?>" target="_blank">Download</a>
    <?php else: ?>
        <p>Nenhum documento disponível.</p>
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

<?php if ($is_admin): ?>
    <div class="info-section">
        <h3>Status do Técnico</h3>
        <div class="row mb-3">
            <div class="col-3">
                <strong>Status:</strong>
                <span class="user-status <?= $statusClass ?>"><?= $statusText ?></span>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <button type="button"
                        class="btn btn-success mr-2"
                        onclick="updateTechnicianStatus(<?= $requested_id ?>, 1)"
                    <?= $enabled == 1 ? 'disabled' : '' ?>>
                    <i class="fas fa-check"></i> Aprovar
                </button>
                <button type="button"
                        class="btn btn-danger"
                        onclick="updateTechnicianStatus(<?= $requested_id ?>, 0)"
                    <?= $enabled == 0 ? 'disabled' : '' ?>>
                    <i class="fas fa-times"></i> Reprovar
                </button>
                <button type="button"
                        class="btn btn-secondary"
                        onclick="updateTechnicianStatus(<?= $requested_id ?>, null)"
                    <?= $enabled === null ? 'disabled' : '' ?>>
                    <i class="fas fa-undo"></i> Resetar Status
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
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
    function updateTechnicianStatus(userId, status) {
        const statusText = status === 1 ? 'aprovar' : (status === 0 ? 'reprovar' : 'resetar o status');
        
        if (confirm('Tem certeza que deseja ' + statusText + ' este técnico?')) {
            fetch('../backend/api/update_technician_status.php', {
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
                    alert('Status atualizado com sucesso!');
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
    // Output the buffered content and exit for AJAX
    $content = ob_get_clean();
    echo $content;
    exit();
} else {
    // For non-AJAX requests, wrap in container and include footer
?>

<div class="container container-user">
    <?php if ($is_own_profile): ?>
        <div class="alert alert-info d-flex justify-content-between align-items-center mb-3">
            <span>Bem-vindo(a) ao seu perfil, <?= titleCase($name) ?>!</span>
            <a href="../auth/logout.php" class="btn btn-danger btn-sm">Sair</a>
        </div>
    <?php endif; ?>

    <?php if ($is_admin): ?>
        <a href="tecnicos.php" class="back-link">Voltar</a>
    <?php endif; ?>

    <h1 class="main-title">Dados do Técnico</h1>

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
      <p class="col-12"><?= $addressStr ?></p>
    </div>
    <div class="row">
      <p class="col-12"><strong>Escolaridade</strong></p>
      <p class="col-12"><?= $scholarship ?></p>
    </div>
    <?php if ($special_needs && $special_needs != 'Não'): ?>
      <div class="row">
        <p class="col-12"><strong>Necessidades Especiais</strong></p>
        <p class="col-12"><?= $special_needs ?></p>
      </div>
    <?php endif; ?>
  </div>

  <div class="info-section">
    <h3>Documentos</h3>
    <?php if (!empty($file_path)): ?>
      <a href="../backend/documentos/tecnicos/<?= $file_path ?>" target="_blank">Download</a>
    <?php else: ?>
      <p>Nenhum documento disponível.</p>
    <?php endif; ?>
  </div>

  <?php if ($is_admin): ?>
    <div class="info-section">
      <h3>Status do Técnico</h3>
      <div class="row mb-3">
        <div class="col-3">
          <strong>Status:</strong>
          <span class="user-status <?= $statusClass ?>"><?= $statusText ?></span>
        </div>
      </div>
      <div class="row">
        <div class="col-12">
          <button type="button"
            class="btn btn-success mr-2"
            onclick="updateTechnicianStatus(<?= $requested_id ?>, 1)"
            <?= $enabled == 1 ? 'disabled' : '' ?>>
            <i class="fas fa-check"></i> Aprovar
          </button>
          <button type="button"
            class="btn btn-danger"
            onclick="updateTechnicianStatus(<?= $requested_id ?>, 0)"
            <?= $enabled == 0 ? 'disabled' : '' ?>>
            <i class="fas fa-times"></i> Reprovar
          </button>
          <button type="button"
            class="btn btn-secondary"
            onclick="updateTechnicianStatus(<?= $requested_id ?>, null)"
            <?= $enabled === null ? 'disabled' : '' ?>>
            <i class="fas fa-undo"></i> Resetar status
          </button>
        </div>
      </div>
    </div>
    <style>
      .info-section .btn {
        margin-right: 10px;
        margin-top: 5px;
      }
      .user-status { font-weight: bold; font-size: 1.1em; }
      .user-status.status-approved { color: #28a745; }
      .user-status.status-not-approved { color: #dc3545; }
      .user-status.status-pending { color: #ffc107; }
      .info-section .row .col-12 { display: flex; align-items: center; }
    </style>
  <?php endif; ?>

</div>

<?php include '../components/footer.php'; } 
?>

<script>
  <?php if ($is_admin): ?>
  function updateTechnicianStatus(technicianId, status) {
    const statusText = status === 1 ? 'aprovar' : (status === 0 ? 'reprovar' : 'resetar o status');

    if (confirm(`Tem certeza que deseja ${statusText} o técnico?`)) {
      fetch('../backend/api/update_technician_status.php', {
          method: 'POST',
          headers: { 'Content-type': 'application/json' },
          body: JSON.stringify({
            technician_id: technicianId,
            status: status === null ? 'null' : status
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const statusElement = document.querySelector('.user-status');
            const enableButton = document.querySelector('.info-section .btn-success');
            const disableButton = document.querySelector('.info-section .btn-danger');
            const resetButton = document.querySelector('.info-section .btn-secondary');

            let newStatusText, newStatusClass;
            if (status === 1) {
              newStatusText = 'Apto';
              newStatusClass = 'status-approved';
            } else if (status === 0) {
              newStatusText = 'Inapto';
              newStatusClass = 'status-not-approved';
            } else {
              newStatusText = 'Aguardando aprovação';
              newStatusClass = 'status-pending';
            }

            statusElement.textContent = newStatusText;
            statusElement.className = 'user-status ' + newStatusClass;

            enableButton.disabled = (status === 1);
            disableButton.disabled = (status === 0);
            resetButton.disabled = (status === null);

            Toastify({
              text: "Status atualizado!",
              className: "statusToast",
              style: { background: "#38b000" },
            }).showToast();
          } else {
            alert('Erro ao atualizar status: ' + (data.message || 'Unknown error'));
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Erro ao atualizar status.');
        });
    }
  }
  <?php endif; ?>
</script>
