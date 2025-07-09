<?php 
// pages/docente.php - Complete version with authentication
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
    if (!$is_admin && $_SESSION['user_type'] === 'teacher') {
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
    $teacher_id = $requested_id;
} elseif ($_SESSION['user_type'] === 'teacher' && $_SESSION['type_id'] == $requested_id) {
    // User viewing their own profile
    $teacher_id = $requested_id;
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
require_once '../backend/services/teacher.service.php';

// Get teacher data
$conection = new Database();
$conn = $conection->connect();
$teacherService = new TeacherService($conn);

try {
    $teacher = $teacherService->getTeacher($teacher_id);
    
    // Check if teacher was found
    if (!$teacher) {
        echo '<div class="container">
                <h1 class="main-title">Erro</h1>
                <p>Docente não encontrado.</p>
                <a href="docentes.php" class="btn btn-primary">Voltar para lista de docentes</a>
              </div>';
        include '../components/footer.php';
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
    $activities = $teacher->activities;
    $special_needs = $teacher->special_needs;
    $lectures = $teacher->lectures;
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
    echo '<div class="container">
            <h1 class="main-title">Erro</h1>
            <p>Erro ao carregar dados do docente: ' . htmlspecialchars($e->getMessage()) . '</p>
            <a href="docentes.php" class="btn btn-primary">Voltar para lista de docentes</a>
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
  <a href="docentes.php" class="back-link">Voltar</a>
  <?php endif; ?>
  
  <h1 class="main-title">Dados do Docente</h1>

  <div class="info-section">
    <h3 class="">Dados pessoais</h3>
    <div class="row">
      <div class="col-9">
        <p class="col-12"><strong>Nome</strong></p>
        <p class="col-12"> <?= titleCase($name) ?></p>
      </div>
      <div class="col-3">
        <p class="col-12"><strong>Data de Inscrição</strong></p>
        <p class="col-12"> <?= $dateF ?> </p>
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
      <p class="col-12"><strong>CPF</strong> </p>
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
    <?php if($special_needs != 'Não'): ?>
    <div class="row">
      <p class="col-12"><strong>Necessidades Especiais</strong></p>
      <p class="col-12"><?= $special_needs ?></p>
    </div>
    <?php endif; ?>
  </div>

  <div class="info-section">
    <h3>Formação</h3>
    <?php if (!empty($education_degree)): ?>
      <?php foreach($education_degree as $education): ?>
      <p><?= $education->degree ?> - <?= $education->institution ?></p>
      <?php endforeach ?>
    <?php else: ?>
      <p>Nenhuma formação cadastrada.</p>
    <?php endif; ?>
  </div>

  <div class="info-section">
    <h3>Curso(s)</h3>
    <?php if (!empty($disciplines)): ?>
      <?php foreach($disciplines as $discipline): ?>
      <div class="row mb-2 align-items-center">
        <div class="col-md-6">
          <p class="mb-0"><?= $discipline['name'] ?></p>
        </div>
        <div class="col-md-3">
          <?php 
          $discStatusText = match($discipline['enabled']) {
            '1' => 'Apto',
            '0' => 'Inapto',
            default => 'Aguardando'
          };
          $discStatusClass = match($discipline['enabled']) {
            '1' => 'text-success',
            '0' => 'text-danger',
            default => 'text-warning'
          };
          ?>
          <span class="<?= $discStatusClass ?>"><strong><?= $discStatusText ?></strong></span>
        </div>
        <?php if($is_admin): ?>
        <div class="col-md-3">
          <button class="btn btn-sm btn-success" 
                  onclick="updateDisciplineStatus(<?= $teacher_id ?>, <?= $discipline['id'] ?>, 1)"
                  <?= $discipline['enabled'] == '1' ? 'disabled' : '' ?>>
            Aprovar
          </button>
          <button class="btn btn-sm btn-danger" 
                  onclick="updateDisciplineStatus(<?= $teacher_id ?>, <?= $discipline['id'] ?>, 0)"
                  <?= $discipline['enabled'] == '0' ? 'disabled' : '' ?>>
            Reprovar
          </button>
          <button class="btn btn-sm btn-secondary" 
                  onclick="updateDisciplineStatus(<?= $teacher_id ?>, <?= $discipline['id'] ?>, null)"
                  <?= $discipline['enabled'] === null ? 'disabled' : '' ?>>
            Resetar
          </button>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach ?>
    <?php else: ?>
      <p>Nenhum curso cadastrado.</p>
    <?php endif; ?>
  </div>

  <div class="info-section">
    <h3>Categoria</h3>
    <?php if (!empty($activities)): ?>
      <?php foreach($activities as $activity): ?>
      <p><?= $activity['name'] ?></p>
      <?php endforeach ?>
    <?php else: ?>
      <p>Nenhuma categoria cadastrada.</p>
    <?php endif; ?>
  </div>

  <?php $displayStyle = !empty($lectures) ? 'block' : 'none'; ?>

  <div class="info-section" style="display: <?= $displayStyle ?>;">
    <h3>Palestras</h3>
    <?php if (!empty($lectures)): ?>
      <?php foreach($lectures as $lecture): ?>
      <p><strong><?= $lecture->name ?></strong></p>
      <p style="text-align: justify;"><?= $lecture->details ?></p><br>
      <?php endforeach ?>
    <?php endif; ?>
  </div>

  <div class="info-section">
    <h3>Documentos</h3>
    <?php if (!empty($path)): ?>
      <a href="../backend/documentos/docentes/<?=$path?>" target="_blank">Download</a>
    <?php else: ?>
      <p>Nenhum documento disponível.</p>
    <?php endif; ?>
  </div>

  <?php if ($is_own_profile): ?>
  <!-- Password Change Section -->
  <div class="info-section">
    <h3>Alterar Senha</h3>
    
    <?php if(isset($_SESSION['password_message'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['password_message']) ?>
        </div>
        <?php unset($_SESSION['password_message']); ?>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['password_error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['password_error']) ?>
        </div>
        <?php unset($_SESSION['password_error']); ?>
    <?php endif; ?>
    
    <?php if($_SESSION['first_login'] ?? false): ?>
        <div class="alert alert-warning">
            <strong>Primeiro acesso!</strong> Por segurança, recomendamos que você altere sua senha.
        </div>
    <?php endif; ?>
    
    <form method="post" action="../auth/process_change_password.php" class="needs-validation" novalidate>
        <div class="row">
            <div class="col-md-12 mb-3">
                <label for="current_password">Senha Atual</label>
                <input type="password" class="form-control" id="current_password" 
                       name="current_password" required>
                <small class="form-text text-muted">
                    Se é seu primeiro acesso, use seu CPF (apenas números)
                </small>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="new_password">Nova Senha</label>
                <input type="password" class="form-control" id="new_password" 
                       name="new_password" required minlength="8">
                <small class="form-text text-muted">
                    Mínimo 8 caracteres, com letras maiúsculas, minúsculas, números e símbolos (@$!%*?&)
                </small>
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="confirm_password">Confirmar Nova Senha</label>
                <input type="password" class="form-control" id="confirm_password" 
                       name="confirm_password" required>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">Alterar Senha</button>
    </form>
  </div>
  <?php endif; ?>

  <?php if($is_admin): ?>
  <div class="info-section">
    <h3>Status do Docente</h3>
    <div class="row">
      <p class="col-3"><strong>Status:</strong></p>
      <p class="col-9 user-status <?= $statusClass ?>"><?= $statusText ?></p>
    </div>
    <div class="row">
      <button class="btn ok-btn" onclick="updateTeacherStatus(<?= $teacher_id ?>, 1)"
              <?= $enabled == 1 ? 'disabled' : '' ?>>Aprovar</button>
      <button class="btn cancel-btn" onclick="updateTeacherStatus(<?= $teacher_id ?>, 0)"
              <?= $enabled == 0 ? 'disabled' : '' ?>>Reprovar</button>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php 
  include '../components/footer.php';
?>

<script>
// Password validation
document.getElementById('confirm_password')?.addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    if (newPassword !== this.value) {
        this.setCustomValidity('As senhas devem ser iguais');
    } else {
        this.setCustomValidity('');
    }
});

<?php if($is_admin): ?>
function updateDisciplineStatus(teacherId, disciplineId, status) {
  const statusText = status === 1 ? 'aprovar' : (status === 0 ? 'reprovar' : 'resetar o status d');
  
  if(confirm(`Tem certeza que deseja ${statusText}o docente para este curso?`)) {
    fetch('../backend/api/update_teacher_discipline_status.php', {
      method: 'POST',
      headers: {
        'Content-type': 'application/json'
      },
      body: JSON.stringify({
        teacher_id: teacherId,
        discipline_id: disciplineId,
        status: status === null ? 'null' : status
      })
    })
    .then(response => response.json())
    .then(data => {
      if(data.success) {
        Toastify({
          text: "Status do curso atualizado!",
          className: "statusToast",
          style: {
            background: "#38b000",
          },
        }).showToast();
        
        // Reload the page to show updated status
        setTimeout(() => {
          location.reload();
        }, 1000);
      } else {
        alert('Erro ao atualizar o status: ' + (data.message || 'Erro desconhecido'));
      }
    })
    .catch(error => {
      console.error('Erro: ', error);
      alert('Erro ao atualizar o status');
    });
  }
}

function updateTeacherStatus(teacherId, status) {
  if(confirm("Tem certeza que deseja alterar o status do docente?")) {
    fetch('../backend/api/update_teacher_status.php', {
      method: 'POST',
      headers: {
        'Content-type': 'application/json'
      },
      body: JSON.stringify({
        teacher_id: teacherId,
        status: status
      })
    })
    .then(response=> response.json())
    .then(data => {
      if(data.success) {
        const statusElement = document.querySelector('.user-status');
        const enableButton = document.querySelector('.ok-btn');
        const disableButton = document.querySelector('.cancel-btn');
        
        const statusText = status === 1 ? 'Apto' : 'Inapto';

        statusElement.textContent = statusText;
        statusElement.className = 'user-status ' + (status === 1 ? 'status-approved' : 'status-not-approved');

        enableButton.disabled = (status === 1);
        disableButton.disabled = (status === 0);         

        Toastify({
          text: "Status do docente atualizado!",
          className: "statusToast",
          style: {
            background: "#38b000",
          },
        }).showToast();

      } else {
        alert('Erro ao atualizar o status' + (data.message || 'Erro desconhecido'))
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