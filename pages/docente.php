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
  <style>
    /* Keep only the styles that aren't handled by Bootstrap */
    .activity-item {
      background-color: white;
    }

    .activity-evaluation {
      border-left: 3px solid #dee2e6;
      padding-left: 10px;
    }
    
    /* Custom styling for accordion items */
    .accordion-item {
      border-radius: 5px;
      overflow: hidden;
      margin-bottom: 1rem;
    }
    
    .accordion-button:not(.collapsed) {
      background-color: #e9ecef;
      color: #212529;
    }
  </style>
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
      <?php
      // Group disciplines by ID to show each course only once
      $courses_map = [];
      foreach ($disciplines as $discipline) {
        $disc_id = method_exists($discipline, 'getId') ? $discipline->getId() : (property_exists($discipline, 'id') ? $discipline->id : 0);

        if (!isset($courses_map[$disc_id])) {
          $courses_map[$disc_id] = $discipline;
        }
      }

      $discipline_index = 0;
      ?>

      <!-- Bootstrap Accordion Wrapper -->
      <div class="accordion" id="disciplinesAccordion">
      <?php foreach ($courses_map as $discipline):
        $discipline_index++;
        $disc_id = method_exists($discipline, 'getId') ? $discipline->getId() : (property_exists($discipline, 'id') ? $discipline->id : 0);
        $disc_name = method_exists($discipline, 'getName') ? $discipline->getName() : (property_exists($discipline, 'name') ? $discipline->name : 'Nome não disponível');
        $disc_eixo = method_exists($discipline, 'getEixo') ? $discipline->getEixo() : (property_exists($discipline, 'eixo') ? $discipline->eixo : null);
        $disc_estacao = method_exists($discipline, 'getEstacao') ? $discipline->getEstacao() : (property_exists($discipline, 'estacao') ? $discipline->estacao : null);
        $disc_modules = method_exists($discipline, 'getModules') ? $discipline->getModules() : (property_exists($discipline, 'modules') ? $discipline->modules : []);

        // Get activities for this discipline
        $disc_activities = property_exists($discipline, 'activities') ? $discipline->activities : [];

        $accordion_id = 'discipline-' . $discipline_index;
      ?>

        <?php if (!empty($disc_activities)): ?>
          <!-- BOOTSTRAP ACCORDION ITEM -->
          <div class="accordion-item mb-3" style="border-left: 4px solid #3498db;">
            <h2 class="accordion-header" id="heading-<?= $accordion_id ?>">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $accordion_id ?>" aria-expanded="false" aria-controls="<?= $accordion_id ?>">
                <strong><?= htmlspecialchars($disc_name) ?></strong>
                <span class="badge bg-secondary ms-2"><?= count($disc_activities) ?> atividade<?= count($disc_activities) > 1 ? 's' : '' ?></span>
              </button>
            </h2>
            
            <!-- Collapsible Details Section -->
            <div id="<?= $accordion_id ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?= $accordion_id ?>" data-bs-parent="#disciplinesAccordion">
              <div class="accordion-body" style="background: #f8f9fa;">

                <?php if ($disc_eixo || $disc_estacao): ?>
                  <div class="discipline-details mb-2">
                    <p class="text-muted" style="margin-bottom: 0; font-size: 14px;">
                      <?php if ($disc_eixo): ?>Eixo: <?= htmlspecialchars($disc_eixo) ?><?php endif; ?>
                      <?php if ($disc_eixo && $disc_estacao): ?> | <?php endif; ?>
                      <?php if ($disc_estacao): ?>Estação: <?= htmlspecialchars($disc_estacao) ?><?php endif; ?>
                    </p>
                  </div>
                <?php endif; ?>

                <?php if (!empty($disc_modules)): ?>
                  <p class="text-muted mb-3" style="font-size: 14px;">Módulos: <?= implode(', ', $disc_modules) ?></p>
                <?php endif; ?>

                <!-- Activities List -->
                <div class="activities-list mt-3">
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
            <?php if ($disc_eixo || $disc_estacao): ?>
              <p class="text-muted mt-2 mb-0" style="font-size: 14px;">
                <?php if ($disc_eixo): ?>Eixo: <?= htmlspecialchars($disc_eixo) ?><?php endif; ?>
                <?php if ($disc_eixo && $disc_estacao): ?> | <?php endif; ?>
                <?php if ($disc_estacao): ?>Estação: <?= htmlspecialchars($disc_estacao) ?><?php endif; ?>
              </p>
            <?php endif; ?>
          </div>
        <?php endif; ?>

      <?php endforeach; ?>
      </div> <!-- Close accordion wrapper -->
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
      <?php
      // Group disciplines by ID to show each course only once
      $courses_map = [];
      foreach ($disciplines as $discipline) {
        $disc_id = method_exists($discipline, 'getId') ? $discipline->getId() : (property_exists($discipline, 'id') ? $discipline->id : 0);

        if (!isset($courses_map[$disc_id])) {
          $courses_map[$disc_id] = $discipline;
        }
      }

      $discipline_index = 0;
      ?>

      <!-- Bootstrap Accordion Wrapper -->
      <div class="accordion" id="disciplinesAccordion">
      <?php foreach ($courses_map as $discipline):
        $discipline_index++;
        $disc_id = method_exists($discipline, 'getId') ? $discipline->getId() : (property_exists($discipline, 'id') ? $discipline->id : 0);
        $disc_name = method_exists($discipline, 'getName') ? $discipline->getName() : (property_exists($discipline, 'name') ? $discipline->name : 'Nome não disponível');
        $disc_eixo = method_exists($discipline, 'getEixo') ? $discipline->getEixo() : (property_exists($discipline, 'eixo') ? $discipline->eixo : null);
        $disc_estacao = method_exists($discipline, 'getEstacao') ? $discipline->getEstacao() : (property_exists($discipline, 'estacao') ? $discipline->estacao : null);
        $disc_modules = method_exists($discipline, 'getModules') ? $discipline->getModules() : (property_exists($discipline, 'modules') ? $discipline->modules : []);

        // Get activities for this discipline
        $disc_activities = property_exists($discipline, 'activities') ? $discipline->activities : [];

        $accordion_id = 'discipline-' . $discipline_index;
      ?>

        <?php if (!empty($disc_activities)): ?>
          <!-- BOOTSTRAP ACCORDION ITEM -->
          <div class="accordion-item mb-3" style="border-left: 4px solid #3498db;">
            <h2 class="accordion-header" id="heading-<?= $accordion_id ?>">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $accordion_id ?>" aria-expanded="false" aria-controls="<?= $accordion_id ?>">
                <strong><?= htmlspecialchars($disc_name) ?></strong>
                <span class="badge bg-secondary ms-2"><?= count($disc_activities) ?> atividade<?= count($disc_activities) > 1 ? 's' : '' ?></span>
              </button>
            </h2>
            
            <!-- Collapsible Details Section -->
            <div id="<?= $accordion_id ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?= $accordion_id ?>" data-bs-parent="#disciplinesAccordion">
              <div class="accordion-body" style="background: #f8f9fa;">

                <?php if ($disc_eixo || $disc_estacao): ?>
                  <div class="discipline-details mb-2">
                    <p class="text-muted" style="margin-bottom: 0; font-size: 14px;">
                      <?php if ($disc_eixo): ?>Eixo: <?= htmlspecialchars($disc_eixo) ?><?php endif; ?>
                      <?php if ($disc_eixo && $disc_estacao): ?> | <?php endif; ?>
                      <?php if ($disc_estacao): ?>Estação: <?= htmlspecialchars($disc_estacao) ?><?php endif; ?>
                    </p>
                  </div>
                <?php endif; ?>

                <?php if (!empty($disc_modules)): ?>
                  <p class="text-muted mb-3" style="font-size: 14px;">Módulos: <?= implode(', ', $disc_modules) ?></p>
                <?php endif; ?>

                <!-- Activities List -->
                <div class="activities-list mt-3">
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
            <?php if ($disc_eixo || $disc_estacao): ?>
              <p class="text-muted mt-2 mb-0" style="font-size: 14px;">
                <?php if ($disc_eixo): ?>Eixo: <?= htmlspecialchars($disc_eixo) ?><?php endif; ?>
                <?php if ($disc_eixo && $disc_estacao): ?> | <?php endif; ?>
                <?php if ($disc_estacao): ?>Estação: <?= htmlspecialchars($disc_estacao) ?><?php endif; ?>
              </p>
            <?php endif; ?>
          </div>
        <?php endif; ?>

      <?php endforeach; ?>
      </div> <!-- Close accordion wrapper -->
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
    // For page access check:
    const isAdmin = <?= json_encode(isAdministrativeRole()) ?>;

    function canSendInvites() {
      return userRoles.includes('admin') || userRoles.includes('gedth');
    }

    function canViewContractInfo() {
      return userRoles.includes('admin') || userRoles.includes('gese');
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

      function updateGeneralStatus(userId, status) {
        const url = '../backend/api/update_teacher_status.php';
        const data = new FormData();
        data.append('user_id', userId);
        data.append('status', status !== null ? status : '');

        fetch(url, {
            method: 'POST',
            body: data
          })
          .then(response => response.json())
          .then(result => {
            if (result.success) {
              alert('Status geral atualizado com sucesso!');
              location.reload();
            } else {
              alert('Erro ao atualizar status: ' + (result.message || 'Erro desconhecido'));
            }
          })
          .catch(error => {
            alert('Erro na requisição: ' + error);
          });
      }

      function updateEvaluation(userId, disciplineId, evaluationType, value) {
        const url = '../backend/api/update_teacher_evaluation.php';
        const data = new FormData();
        data.append('user_id', userId);
        data.append('discipline_id', disciplineId);
        data.append('evaluation_type', evaluationType);
        data.append('value', value !== null ? value : '');

        fetch(url, {
            method: 'POST',
            body: data
          })
          .then(response => response.json())
          .then(result => {
            if (result.success) {
              alert('Avaliação atualizada com sucesso!');
              location.reload();
            } else {
              alert('Erro ao atualizar avaliação: ' + (result.message || 'Erro desconhecido'));
            }
          })
          .catch(error => {
            alert('Erro na requisição: ' + error);
          });
      }

      function updateEvaluationForActivity(userId, disciplineId, activityId, evaluationType, value) {
        const url = '../backend/api/update_teacher_evaluation_activity.php';
        const data = new FormData();
        data.append('user_id', userId);
        data.append('discipline_id', disciplineId);
        data.append('activity_id', activityId);
        data.append('evaluation_type', evaluationType);
        data.append('value', value !== null ? value : '');

        fetch(url, {
            method: 'POST',
            body: data
          })
          .then(response => response.json())
          .then(result => {
            if (result.success) {
              alert('Avaliação da atividade atualizada com sucesso!');
              location.reload();
            } else {
              alert('Erro ao atualizar avaliação: ' + (result.message || 'Erro desconhecido'));
            }
          })
          .catch(error => {
            alert('Erro na requisição: ' + error);
          });
      }
    <?php endif; ?>
  </script>

<?php include '../components/footer.php';
  ob_end_flush();
}
?>