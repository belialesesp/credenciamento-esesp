<?php 

echo '<link rel="stylesheet" href="../styles/user.css">';

include '../components/header.php';

require_once '../pdf/assets/title_case.php';
require_once '../backend/classes/database.class.php';
require_once '../backend/services/teacherpos.service.php';

// Initialize variables to prevent undefined warnings
$teacher = null;
$teacher_id = null;

// Check if ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])) {
    echo '<div class="container">
            <h1 class="main-title">Erro</h1>
            <p>ID do docente não fornecido.</p>
            <a href="docentes-pos.php" class="btn btn-primary">Voltar para lista de docentes</a>
          </div>';
    include '../components/footer.php';
    exit();
}

// Get teacher data
$teacher_id = intval($_GET['id']);
$conection = new Database();
$conn = $conection->connect();
$teacherService = new TeacherPostGService($conn);

try {
    $teacher = $teacherService->getTeacherPostG($teacher_id);
    
    // Check if teacher was found
    if (!$teacher) {
        echo '<div class="container">
                <h1 class="main-title">Erro</h1>
                <p>Docente não encontrado.</p>
                <a href="docentes-pos.php" class="btn btn-primary">Voltar para lista de docentes</a>
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
        $position = strpos($string, "posgraduacao");
        if ($position !== false) {
            $start = $position + strlen("posgraduacao/");
            $path = substr($string, $start);
        }
    }
    
} catch (Exception $e) {
    echo '<div class="container">
            <h1 class="main-title">Erro</h1>
            <p>Erro ao carregar dados do docente: ' . htmlspecialchars($e->getMessage()) . '</p>
            <a href="docentes-pos.php" class="btn btn-primary">Voltar para lista de docentes</a>
          </div>';
    include '../components/footer.php';
    exit();
}

?>

<div class="container container-user">
  <a href="docentes-pos.php" class="back-link">Voltar</a>
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
          <p class="col-12"><strong><?= $degree->degree ?></strong> - <?= $degree->name ?></p>
          <p class="col-12"><?= $degree->institution ?></p>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>Nenhuma habilitação cadastrada.</p>
    <?php endif; ?>
  </div>

  <div class="info-section">
    <h3>Cursos</h3>
    <?php if (!empty($disciplines)): ?>
      <?php foreach($disciplines as $discipline): ?>
      <div class="discipline-item">
        <div class="discipline-header">
          <div class="discipline-info">
            <p>
              <strong><?= htmlspecialchars($discipline->name) ?></strong>
              <span class="discipline-status <?= $discipline->getStatusClass() ?>"><?= $discipline->getStatusText() ?></span>
            </p>
            <p><?= htmlspecialchars($discipline->post_graduation) ?></p>
            <p>Eixo: <?= htmlspecialchars($discipline->eixo) ?></p>
          </div>
          <div class="discipline-actions">
            <button 
              class="btn-approve" 
              onclick="updateDisciplineStatus(<?= $teacher_id ?>, <?= $discipline->getId() ?>, 1)"
              <?= $discipline->enabled === 1 ? 'disabled' : '' ?>>
              Aprovar
            </button>
            <button 
              class="btn-reject" 
              onclick="updateDisciplineStatus(<?= $teacher_id ?>, <?= $discipline->getId() ?>, 0)"
              <?= $discipline->enabled === 0 ? 'disabled' : '' ?>>
              Reprovar
            </button>
            <button 
              class="btn-reset" 
              onclick="updateDisciplineStatus(<?= $teacher_id ?>, <?= $discipline->getId() ?>, null)"
              <?= $discipline->enabled === null ? 'disabled' : '' ?>>
              Resetar
            </button>
          </div>
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

  <div class="info-section">
    <h3>Documentos</h3>
    <?php if (!empty($path)): ?>
      <a href="../backend/documentos/posgraduacao/<?=$path?>" target="_blank">Download</a>
    <?php else: ?>
      <p>Nenhum documento disponível.</p>
    <?php endif; ?>
  </div>

</div>

<?php 
  include '../components/footer.php';
?>

<script>
// Debug function - TEMPORARY
function testUpdateAPI() {
    console.log('Testing API call...');
    
    const testData = {
        teacher_id: <?= $teacher_id ?>,
        discipline_id: <?= !empty($disciplines) ? $disciplines[0]->getId() : 1 ?>, 
        status: 1
    };
    
    console.log('Sending data:', testData);
    
    fetch('../backend/api/update_postg_teacher_discipline_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(testData)
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text(); // Use text() first to see raw response
    })
    .then(text => {
        console.log('Raw response:', text);
        try {
            const data = JSON.parse(text);
            console.log('Parsed data:', data);
        } catch (e) {
            console.error('JSON parse error:', e);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
    });
}

// Main function for updating discipline status
function updateDisciplineStatus(teacherId, disciplineId, status) {
    console.log('updateDisciplineStatus called with:', {teacherId, disciplineId, status});
    
    // Show loading state
    const buttons = document.querySelectorAll(`[onclick*="updateDisciplineStatus(${teacherId}, ${disciplineId}"]`);
    buttons.forEach(btn => {
        btn.disabled = true;
        btn.style.opacity = '0.6';
    });

    // Prepare JSON data
    const data = {
        teacher_id: parseInt(teacherId),
        discipline_id: parseInt(disciplineId),
        status: status === null ? 'null' : parseInt(status)
    };
    
    console.log('Sending data:', data);

    fetch('../backend/api/update_postg_teacher_discipline_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            alert('Status atualizado com sucesso!');
            window.location.reload();
        } else {
            alert('Erro ao atualizar status: ' + data.message);
            // Re-enable buttons on error
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.style.opacity = '1';
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erro ao atualizar status: ' + error.message);
        // Re-enable buttons on error
        buttons.forEach(btn => {
            btn.disabled = false;
            btn.style.opacity = '1';
        });
    });
}
</script>