<?php 
session_start();

// Check if docente is logged in
if (!isset($_SESSION['docente_id']) || $_SESSION['docente_type'] !== 'regular') {
    header('Location: login_docente.php');
    exit();
}

// Use the session docente_id instead of GET parameter for security
$teacher_id = $_SESSION['docente_id'];

echo '<link rel="stylesheet" href="../styles/user.css">';

include '../components/header.php';

require_once '../pdf/assets/title_case.php';
require_once '../backend/classes/database.class.php';
require_once '../backend/services/teacher.service.php';

$connection = new Database();
$conn = $connection->connect();
$teacherService = new TeacherService($conn);

try {
    $teacher = $teacherService->getTeacher($teacher_id);
    
    if (!$teacher) {
        throw new Exception("Dados do docente não encontrados.");
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
            <p>Erro ao carregar seus dados: ' . htmlspecialchars($e->getMessage()) . '</p>
            <a href="logout_docente.php" class="btn btn-primary">Sair</a>
          </div>';
    include '../components/footer.php';
    exit();
}
?>

<div class="container container-user">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="main-title">Meu Perfil - Docente</h1>
    <a href="../auth/logout_docente.php" class="btn btn-danger btn-sm">Sair</a>
  </div>

  <div class="alert alert-info">
    <strong>Bem-vindo(a), <?= titleCase($name) ?>!</strong><br>
    Você está logado(a) como docente regular.
  </div>

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
      <p class="col-12"><?= titleCase($address) . ', ' . titleCase($city) . ' - ' . strtoupper($state) . ', CEP: ' . $zip ?></p>
    </div>
    <?php if($special_needs): ?>
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
      <p><strong><?= $education->degree ?></strong> - <?= $education->institution ?></p>
      <?php endforeach ?>
    <?php else: ?>
      <p>Nenhuma formação cadastrada.</p>
    <?php endif; ?>
  </div>

  <div class="info-section">
    <h3>Cursos e Status</h3>
    <?php if (!empty($disciplines)): ?>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Curso</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($disciplines as $discipline): 
            $statusText = match($discipline['enabled']) {
              '1' => 'Apto',
              '0' => 'Inapto',
              default => 'Aguardando aprovação'
            };
            $statusClass = match($discipline['enabled']) {
              '1' => 'text-success',
              '0' => 'text-danger',
              default => 'text-warning'
            };
          ?>
          <tr>
            <td><?= $discipline['name'] ?></td>
            <td class="<?= $statusClass ?>"><strong><?= $statusText ?></strong></td>
          </tr>
          <?php endforeach ?>
        </tbody>
      </table>
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

  <?php if (!empty($lectures)): ?>
  <div class="info-section">
    <h3>Palestras</h3>
    <?php foreach($lectures as $lecture): ?>
    <p><strong><?= $lecture->name ?></strong></p>
    <p style="text-align: justify;"><?= $lecture->details ?></p><br>
    <?php endforeach ?>
  </div>
  <?php endif; ?>

  <div class="info-section">
    <h3>Documentos</h3>
    <?php if (!empty($path)): ?>
      <a href="../backend/documentos/docentes/<?=$path?>" target="_blank" class="btn btn-primary">
        <i class="fas fa-download"></i> Download dos Documentos
      </a>
    <?php else: ?>
      <p>Nenhum documento disponível.</p>
    <?php endif; ?>
  </div>
</div>

<?php include '../components/footer.php'; ?>