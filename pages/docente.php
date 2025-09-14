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

// Verify the requested user exists and is a teacher
$stmt = $conn->prepare("
    SELECT u.id 
    FROM user u
    INNER JOIN user_roles ur ON ur.user_id = u.id
    WHERE u.id = ? AND ur.role = 'docente' AND u.enabled = 1
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

// Check access permissions
$is_own_profile = false;
if ($is_admin) {
  $is_own_profile = false;
} elseif ($_SESSION['user_type'] === 'teacher' && $_SESSION['user_id'] == $requested_id) {
  $is_own_profile = true;
} else {
  if ($is_ajax_request) {
    echo '<div class="alert alert-danger">Acesso não autorizado.</div>';
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
  $path = '';
  if ($file_path) {
    $string = $file_path;
    $position = strpos($string, "docentes");
    if ($position !== false) {
      $start = $position + strlen("docentes/");
      $path = substr($string, $start);
    }
  }
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

<?php if ($is_own_profile): ?>
  <div class="alert alert-info d-flex justify-content-between align-items-center mb-3">
    <span>Bem-vindo(a) ao seu perfil, <?= titleCase($name) ?>!</span>
    <a href="../auth/logout.php" class="btn btn-danger btn-sm">Sair</a>
  </div>
<?php endif; ?>

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
      $disc_enabled = method_exists($discipline, 'getEnabled') ? $discipline->getEnabled() : (property_exists($discipline, 'enabled') ? $discipline->enabled : null);
      $disc_eixo = method_exists($discipline, 'getEixo') ? $discipline->getEixo() : (property_exists($discipline, 'eixo') ? $discipline->eixo : null);
      $disc_estacao = method_exists($discipline, 'getEstacao') ? $discipline->getEstacao() : (property_exists($discipline, 'estacao') ? $discipline->estacao : null);
      $disc_modules = method_exists($discipline, 'getModules') ? $discipline->getModules() : (property_exists($discipline, 'modules') ? $discipline->modules : []);

      // Determine status
      $statusText = match ($disc_enabled) {
        1 => 'Apto',
        0 => 'Inapto',
        null => 'Aguardando',
        default => 'Aguardando'
      };
      $statusClass = match ($disc_enabled) {
        1 => 'status-approved',
        0 => 'status-not-approved',
        null => 'status-pending',
        default => 'status-pending'
      };
      ?>
      <div class="discipline-item mb-3">
        <div class="discipline-header">
          <p><strong><?= htmlspecialchars($disc_name) ?></strong>
            <span class="user-status <?= $statusClass ?> ms-3"><?= $statusText ?></span>
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

        <?php if ($is_admin): ?>
          <div class="discipline-actions mt-2">
            <button class="btn btn-success btn-sm me-2"
              onclick="updateDisciplineStatus(<?= $user_id ?>, <?= $disc_id ?>, 1)"
              <?= $disc_enabled === 1 ? 'disabled' : '' ?>>
              Aprovar para este curso
            </button>
            <button class="btn btn-danger btn-sm me-2"
              onclick="updateDisciplineStatus(<?= $user_id ?>, <?= $disc_id ?>, 0)"
              <?= $disc_enabled === 0 ? 'disabled' : '' ?>>
              Reprovar para este curso
            </button>
            <button class="btn btn-secondary btn-sm"
              onclick="updateDisciplineStatus(<?= $user_id ?>, <?= $disc_id ?>, null)"
              <?= $disc_enabled === null ? 'disabled' : '' ?>>
              Resetar status
            </button>
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
      <a href="../backend/documentos/docentes/<?= $path ?>" target="_blank">Download</a>
    <?php else: ?>
      <p>Nenhum documento disponível.</p>
      <?php if ($is_admin): ?>
        <div class="text-muted small">
          <div>Debug Info:</div>
          <div>Full Path: <?= htmlspecialchars($teacher->file_path ?? 'NULL') ?></div>
          <div>File Exists: <?= file_exists($teacher->file_path) ? 'Yes' : 'No' ?></div>
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

    function updateDisciplineStatus(teacherId, disciplineId, status) {
      const statusText = status === 1 ? 'aprovar' : (status === 0 ? 'reprovar' : 'resetar o status');

      if (confirm(`Tem certeza que deseja ${statusText} o docente para este curso?`)) {
        fetch('../backend/api/update_teacher_discipline_status.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              teacher_id: teacherId,
              discipline_id: disciplineId,
              status: status === null ? 'null' : status
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              alert('Status do curso atualizado com sucesso!');
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
  <?php if ($is_own_profile): ?>
    <div class="alert alert-info d-flex justify-content-between align-items-center mb-3">
      <span>Bem-vindo(a) ao seu perfil, <?= titleCase($name) ?>!</span>
      <a href="../auth/logout.php" class="btn btn-danger btn-sm">Sair</a>
    </div>
  <?php endif; ?>

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

        // Determine status
        $statusText = match ($disc_enabled) {
          1 => 'Apto',
          0 => 'Inapto',
          null => 'Aguardando',
          default => 'Aguardando'
        };
        $statusClass = match ($disc_enabled) {
          1 => 'status-approved',
          0 => 'status-not-approved',
          null => 'status-pending',
          default => 'status-pending'
        };
        ?>
        <div class="discipline-item mb-3">
          <div class="discipline-header">
            <p><strong><?= htmlspecialchars($disc_name) ?></strong>
              <span class="user-status <?= $statusClass ?> ms-3"><?= $statusText ?></span>
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

          <?php if ($is_admin): ?>
            <div class="discipline-actions mt-2">
              <button class="btn btn-success btn-sm me-2"
                onclick="updateDisciplineStatus(<?= $user_id ?>, <?= $disc_id ?>, 1)"
                <?= $disc_enabled === 1 ? 'disabled' : '' ?>>
                Aprovar para este curso
              </button>
              <button class="btn btn-danger btn-sm me-2"
                onclick="updateDisciplineStatus(<?= $user_id ?>, <?= $disc_id ?>, 0)"
                <?= $disc_enabled === 0 ? 'disabled' : '' ?>>
                Reprovar para este curso
              </button>
              <button class="btn btn-secondary btn-sm"
                onclick="updateDisciplineStatus(<?= $user_id ?>, <?= $disc_id ?>, null)"
                <?= $disc_enabled === null ? 'disabled' : '' ?>>
                Resetar status
              </button>
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
      <a href="../backend/documentos/docentes/<?= $path ?>" target="_blank">Download</a>
    <?php else: ?>
      <p>Nenhum documento disponível.</p>
      <?php if ($is_admin): ?>
        <div class="text-muted small">
          <div>Debug Info:</div>
          <div>Full Path: <?= htmlspecialchars($teacher->file_path ?? 'NULL') ?></div>
          <div>File Exists: <?= file_exists($teacher->file_path) ? 'Yes' : 'No' ?></div>
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

    function updateDisciplineStatus(teacherId, disciplineId, status) {
      const statusText = status === 1 ? 'aprovar' : (status === 0 ? 'reprovar' : 'resetar o status');

      if (confirm(`Tem certeza que deseja ${statusText} o docente para este curso?`)) {
        fetch('../backend/api/update_teacher_discipline_status.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              teacher_id: teacherId,
              discipline_id: disciplineId,
              status: status === null ? 'null' : status
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              alert('Status do curso atualizado com sucesso!');
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