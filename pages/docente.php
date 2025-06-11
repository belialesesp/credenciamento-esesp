<?php 

echo '<link rel="stylesheet" href="../styles/user.css">';

include '../components/header.php';

require_once '../pdf/assets/title_case.php';
require_once '../backend/classes/database.class.php';
require_once '../backend/services/teacher.service.php';

// Initialize variables to prevent undefined warnings
$teacher = null;
$teacher_id = null;

// Check if ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])) {
    echo '<div class="container">
            <h1 class="main-title">Erro</h1>
            <p>ID do docente não fornecido.</p>
            <a href="docentes.php" class="btn btn-primary">Voltar para lista de docentes</a>
          </div>';
    include '../components/footer.php';
    exit();
}

// Get teacher data
$teacher_id = intval($_GET['id']);
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
// Add debugging
error_log('Teacher ID: ' . $teacher_id);
error_log('Disciplines loaded: ' . json_encode($disciplines));
error_log('Number of disciplines: ' . count($disciplines));
    $enabled = match ($teacher->enabled) {
        1 => 'Apto',
        0 => 'Inapto',
        default => 'Aguardando aprovação', 
    };

    $statusClass = match ($teacher->enabled) {
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
  <a href="docentes.php" class="back-link">Voltar</a>
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
      <p class="col-12"><?= titleCase($address) . ', ' . titleCase($city) . ' - ' . strtoupper($state) ?></p>
    </div>
    <div class="row">
      <p class="col-12"><strong>Necessidades Especiais?</strong></p>
      <p class="col-12"><?= $special_needs ?></p>
    </div>
  </div>

  <div class="info-section">
    <h3>Habilitação</h3>
    <?php if (!empty($education_degree)): ?>
      <?php foreach($education_degree as $degree): ?>
        <div class="row">
          <p class="col-12"><strong> <?= $degree->degree ?></strong> - <?= $degree->name ?></p>
          <p class="col-12"> <?= $degree->institution ?></p>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>Nenhuma habilitação cadastrada.</p>
    <?php endif; ?>
  </div>

  <div class="info-section">
    <h3>Cursos</h3>
    <?php if (!empty($disciplines)): ?>
      <?php foreach($disciplines as $discipline): 
        $statusText = $discipline->getStatusText();
        $statusClass = $discipline->getStatusClass();
      ?>
      <div class="discipline-item">
        <div class="row">
          <div class="col-8">
            <p><strong><?= $discipline->name ?></strong>
              <span class="discipline-status-badge <?= $statusClass ?>"><?= $statusText ?></span>
            </p>
            <?php if (!empty($discipline->modules) && count($discipline->modules) > 1): ?> 
              <?php foreach($discipline->modules as $module): ?> 
              <p class="modules-span">Módulo: <?= $module ?></p>
            <?php endforeach; endif; ?> 
          </div>
          <p class="col-12">Eixo: <?= $discipline->eixo ?></p>
          <p class="col-12">Estação: <?= $discipline->estacao ?></p>
        </div>
        <div class="discipline-actions">
          <button class="btn-approve" 
                  onclick="updateDisciplineStatus(<?= $teacher_id ?>, <?= $discipline->getId() ?>, 1)" 
                  <?= $discipline->enabled === 1 ? 'disabled' : '' ?>>
            Aprovar para este curso
          </button>
          <button class="btn-reject" 
                  onclick="updateDisciplineStatus(<?= $teacher_id ?>, <?= $discipline->getId() ?>, 0)" 
                  <?= $discipline->enabled === 0 ? 'disabled' : '' ?>>
            Reprovar para este curso
          </button>
          <button class="btn-reset" 
                  onclick="updateDisciplineStatus(<?= $teacher_id ?>, <?= $discipline->getId() ?>, null)" 
                  <?= $discipline->enabled === null ? 'disabled' : '' ?>>
            Resetar status
          </button>
        </div>
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

</div>

<?php 
  include '../components/footer.php';
?>

<script>
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
</script>