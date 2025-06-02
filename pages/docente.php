<?php 

echo '<link rel="stylesheet" href="../styles/user.css">';

include '../components/header.php';

require_once '../pdf/assets/title_case.php';
require_once '../backend/classes/database.class.php';
require_once '../backend/services/teacher.service.php';


  if(isset($_GET["id"])) {
    $teacher_id = $_GET['id'];
    $conection = new Database();
    $conn = $conection->connect();
    $teacherService = new TeacherService($conn);
    
    $teacher = $teacherService->getTeacher($teacher_id);

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

    $enabled = match ($teacher->enabled) {
      1 => 'Apto',
      0 => 'Não apto',
      default => 'Aguardando aprovação', 
    };

    $statusClass = match ($teacher->enabled) {
      1 => 'status-approved',
      0 => 'status-not-approved',
      default => 'status-pending',
    };

    // Formatar data
    $date = new DateTime($created_at);
    $dateF = $date->format('d/m/Y H:i');

    // Formatar filepath
    $string = $file_path;
    $position = strpos($string, "docentes");
    $start = $position + strlen("docentes/");
    $path = substr($string, $start);

  }
 

?>


<div class="container container-user">
  <a href="docentes.php" class="back-link">Voltar</a>
  <h1 class="main-title">Dados do Docente</h1>

  <p class="user-status <?= $statusClass ?>"><?= $enabled ?></p>

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
    <?php foreach($education_degree as $degree): ?>
      <div class="row">
        <p class="col-12"><strong> <?= $degree->degree ?></strong> - <?= $degree->name ?></p>
        <p class="col-12"> <?= $degree->institution ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="info-section">
    <h3>Cursos</h3>
    <?php foreach($disciplines as $discipline): ?>
    <div class="row">
      <div class="col-12">
        <p><strong><?= $discipline->name ?></strong></p> 
         <?php if (count($discipline->modules) > 1): ?> 
          <?php foreach($discipline->modules as $module): ?> 
          <p class="modules-span">Módulo: <?= $module ?></p>
        <?php endforeach; endif; ?> 
      </div>
      <p class="col-12">Eixo: <?= $discipline->eixo ?></p>
      <p class="col-12">Estação: <?= $discipline->estacao ?></p>
    </div>
    <?php endforeach ?>
  </div>

  <div class="info-section">
    <h3>Categoria</h3>
    <?php foreach($activities as $activity): ?>
    <p><?= $activity['name'] ?></p>
    <?php endforeach ?>
  </div>

  <?php $displayStyle = $lectures ? 'block' : 'none'; ?>

  <div class="info-section" style="display: <?= $displayStyle ?>;">
    <h3>Palestras</h3>
    <?php foreach($lectures as $lecture): ?>
    <p><strong><?= $lecture->name ?></strong></p>
    <p style="text-align: justify;"><?= $lecture->details ?></p><br>
    <?php endforeach ?>
  </div>

  <div class="info-section">
    <h3>Documentos</h3>
    <a href="../backend/documentos/docentes/<?=$path?>" target="_blank">Download</a>
    
  </div>

  <div class="btns-container">
    <button class="ok-btn" onclick="updateTeacherStatus(<?= $teacher_id ?>, 1)" <?= $teacher->enabled === 1 ? 'disabled' : '' ?> >Habilitar Docente</button>
    <button class="cancel-btn" onclick="updateTeacherStatus(<?= $teacher_id ?>, 0)" <?= $teacher->enabled === 0 ? 'disabled' : '' ?> >Desabilitar Docente</button>
  </div>

</div>

<?php 
  include '../components/footer.php';

?>

<script>

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
          
          const statusText = status === 1 ? 'Apto' : 'Não apto';

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