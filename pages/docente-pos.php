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
    <?php foreach($disciplines as $discipline): 
      // Get the status for this specific discipline
      $discStatusQuery = "
        SELECT enabled 
        FROM postg_teacher_disciplines 
        WHERE teacher_id = :teacher_id AND discipline_id = :discipline_id
        LIMIT 1
      ";
      $discStatusStmt = $conn->prepare($discStatusQuery);
      $discStatusStmt->execute([
        ':teacher_id' => $teacher_id,
        ':discipline_id' => $discipline->id
      ]);
      $discStatus = $discStatusStmt->fetchColumn();
      
      $statusText = match ($discStatus) {
        '1', 1 => 'Apto',
        '0', 0 => 'Inapto',
        default => 'Aguardando',
      };
      
      $statusBadgeClass = match ($discStatus) {
        '1', 1 => 'status-approved',
        '0', 0 => 'status-not-approved',
        default => 'status-pending',
      };
      
      // Convert status to integer for comparison
      $statusInt = $discStatus === null || $discStatus === '' ? null : (int)$discStatus;
    ?>
    <div class="discipline-item">
      <div class="discipline-header">
        <div class="discipline-info">
          <p>
            <strong><?= htmlspecialchars($discipline->name) ?></strong>
            <span class="discipline-status <?= $statusBadgeClass ?>"><?= $statusText ?></span>
          </p>
          <p><?= htmlspecialchars($discipline->post_graduation) ?></p>
          <p>Eixo: <?= htmlspecialchars($discipline->eixo) ?></p>
        </div>
        <div class="discipline-actions">
          <button 
            class="btn-approve" 
            onclick="updateDisciplineStatus(<?= $teacher_id ?>, <?= $discipline->id ?>, 1)"
            <?= $statusInt === 1 ? 'disabled' : '' ?>>
            Aprovar
          </button>
          <button 
            class="btn-reject" 
            onclick="updateDisciplineStatus(<?= $teacher_id ?>, <?= $discipline->id ?>, 0)"
            <?= $statusInt === 0 ? 'disabled' : '' ?>>
            Reprovar
          </button>
          <button 
            class="btn-reset" 
            onclick="updateDisciplineStatus(<?= $teacher_id ?>, <?= $discipline->id ?>, null)"
            <?= $statusInt === null ? 'disabled' : '' ?>>
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

<!-- Update the buttons section at the bottom of the page -->
<div class="btns-container">
  <button 
    class="ok-btn" 
    onclick="updateTeacherStatus(<?= $teacher_id ?>, 1)" 
    <?= $teacherStatus === 1 ? 'disabled' : '' ?>>
    Habilitar Todas Disciplinas
  </button>
  <button 
    class="cancel-btn" 
    onclick="updateTeacherStatus(<?= $teacher_id ?>, 0)" 
    <?= $teacherStatus === 0 ? 'disabled' : '' ?>>
    Desabilitar Todas Disciplinas
  </button>
</div>

<!-- Add this script section before the closing body tag -->
// Replace the existing JavaScript in pages/docentes-pos.php with this improved version

// Add this JavaScript to the bottom of docente-pos.php (before closing </body> tag)

<script>
function updateDisciplineStatus(teacherId, disciplineId, status) {
    // Show loading state
    const buttons = document.querySelectorAll(`[onclick*="updateDisciplineStatus(${teacherId}, ${disciplineId}"]`);
    buttons.forEach(btn => {
        btn.disabled = true;
        btn.style.opacity = '0.6';
    });

    // Prepare JSON data (matching your existing API pattern)
    const data = {
        teacher_id: teacherId,
        discipline_id: disciplineId,
        status: status === null ? 'null' : status
    };

    fetch('../backend/api/update_postg_teacher_discipline_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the page to show updated status
            window.location.reload();
        } else {
            alert('Erro ao atualizar status: ' + (data.error || 'Erro desconhecido'));
            // Re-enable buttons on error
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.style.opacity = '1';
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erro ao atualizar status');
        // Re-enable buttons on error
        buttons.forEach(btn => {
            btn.disabled = false;
            btn.style.opacity = '1';
        });
    });
}

// Optional: Add confirmation for status changes
function confirmUpdateDisciplineStatus(teacherId, disciplineId, status) {
    const statusText = status === 1 ? 'Aprovado' : status === 0 ? 'Reprovado' : 'Aguardando';
    if (confirm(`Confirma a alteração do status para "${statusText}"?`)) {
        updateDisciplineStatus(teacherId, disciplineId, status);
    }
}
</script>