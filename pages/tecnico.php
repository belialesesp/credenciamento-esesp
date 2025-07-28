<?php
// pages/tecnico.php - Complete version with authentication
session_start();
require_once '../backend/classes/database.class.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit();
}

// Check if user can access this profile
$requested_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_type = $_SESSION['user_type'] ?? '';
$is_admin = ($user_type === 'admin');
$is_own_profile = false;

if (!$requested_id) {
  // No ID provided
  if (!$is_admin && $_SESSION['user_type'] === 'technician') {
    // Redirect to their own profile
    header('Location: ?id=' . $_SESSION['type_id']);
    exit();
  } else {
    header('Location: home.php');
    exit();
  }
}

// Check access permissions
if ($is_admin) {
  // Admin can see all profiles
  $technician_id = $requested_id;
} elseif ($_SESSION['user_type'] === 'technician' && $_SESSION['type_id'] == $requested_id) {
  // User viewing their own profile
  $technician_id = $requested_id;
  $is_own_profile = true;
} else {
  // Not authorized
  header('Location: home.php');
  exit();
}

// Include styles and header
echo '<link rel="stylesheet" href="../styles/user.css">';
include '../components/header.php';

require_once '../pdf/assets/title_case.php';

// Get technician data
$conection = new Database();
$conn = $conection->connect();

try {
  // Get technician data
  $sql = "SELECT t.*, a.* 
            FROM technician t
            LEFT JOIN address a ON t.address_id = a.id
            WHERE t.id = :id";
  $stmt = $conn->prepare($sql);
  $stmt->execute([':id' => $technician_id]);
  $technician = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$technician) {
    throw new Exception("Técnico não encontrado");
  }

  // Get documents
  $docSql = "SELECT * FROM documents WHERE technician_id = :id";
  $docStmt = $conn->prepare($docSql);
  $docStmt->execute([':id' => $technician_id]);
  $document = $docStmt->fetch(PDO::FETCH_ASSOC);

  // Extract data
  $name = $technician['name'];
  $document_number = $technician['document_number'];
  $document_emissor = $technician['document_emissor'];
  $document_uf = $technician['document_uf'];
  $phone = $technician['phone'];
  $cpf = $technician['cpf'];
  $email = $technician['email'];
  $scholarship = $technician['scholarship'];
  $special_needs = $technician['special_needs'];
  $created_at = $technician['created_at'];
  $enabled = $technician['enabled'];

  // Address
  $address = $technician['address'] ?? '';
  $city = $technician['city'] ?? '';
  $state = $technician['state'] ?? '';
  $zip = $technician['zip'] ?? '';

  // Document path
  $file_path = $document['file_path'] ?? '';

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
  $called_at = $technician['called_at'] ?? null;
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
    $position = strpos($string, "tecnicos");
    if ($position !== false) {
      $start = $position + strlen("tecnicos/");
      $path = substr($string, $start);
    }
  }
} catch (Exception $e) {
  echo '<div class="container">
            <h1 class="main-title">Erro</h1>
            <p>Erro ao carregar dados: ' . htmlspecialchars($e->getMessage()) . '</p>
            <a href="tecnicos.php" class="btn btn-primary">Voltar para lista de técnicos</a>
          </div>';
  include '../components/footer.php';
  exit();
}
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
      <p class="col-12"><?= titleCase($address) . ', ' . titleCase($city) . ' - ' . strtoupper($state) . ', CEP: ' . $zip ?></p>
    </div>
    <div class="row">
      <p class="col-12"><strong>Escolaridade</strong></p>
      <p class="col-12"><?= $scholarship ?></p>
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
      <a href="../backend/documentos/tecnicos/<?= $path ?>" target="_blank">Download</a>
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
            onclick="updateTechnicianStatus(<?= $technician_id ?>, 1)"
            <?= $enabled == 1 ? 'disabled' : '' ?>>
            <i class="fas fa-check"></i> Aprovar
          </button>
          <button type="button"
            class="btn btn-danger"
            onclick="updateTechnicianStatus(<?= $technician_id ?>, 0)"
            <?= $enabled == 0 ? 'disabled' : '' ?>>
            <i class="fas fa-times"></i> Reprovar
          </button>
        </div>
      </div>
    </div>
    <style>
      /* Add this CSS to fix the button display */
      .info-section .btn {
        margin-right: 10px;
        margin-top: 5px;
      }

      .user-status {
        font-weight: bold;
        font-size: 1.1em;
      }

      .user-status.status-approved {
        color: #28a745;
      }

      .user-status.status-not-approved {
        color: #dc3545;
      }

      .user-status.status-pending {
        color: #ffc107;
      }

      /* Ensure buttons are displayed inline */
      .info-section .row .col-12 {
        display: flex;
        align-items: center;
      }
    </style>
  <?php endif; ?>

</div>

<?php
include '../components/footer.php';
?>

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

    function updateTechnicianStatus(technicianId, status) {
  if (confirm("Tem certeza que deseja alterar o status do técnico?")) {
    fetch('../backend/api/update_technician_status.php', {
        method: 'POST',
        headers: {
          'Content-type': 'application/json'
        },
        body: JSON.stringify({
          technician_id: technicianId,
          status: status
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const statusElement = document.querySelector('.user-status');
          // Fix: Use more specific button selectors within the info-section
          const enableButton = document.querySelector('.info-section .btn-success');
          const disableButton = document.querySelector('.info-section .btn-danger');

          const statusText = status === 1 ? 'Apto' : 'Inapto';

          statusElement.textContent = statusText;
          statusElement.className = 'user-status ' + (status === 1 ? 'status-approved' : 'status-not-approved');

          enableButton.disabled = (status === 1);
          disableButton.disabled = (status === 0);

          Toastify({
            text: "Status do técnico atualizado!",
            className: "statusToast",
            style: {
              background: "#38b000",
            },
          }).showToast();

        } else {
          alert('Erro ao atualizar o status: ' + (data.message || 'Erro desconhecido'))
        }
      })
      .catch(error => {
        console.error('Erro: ', error);
        alert('Erro ao atualizar o status');
      })
  }
}
  <?php endif; ?>
</script>