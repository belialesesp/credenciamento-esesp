<?php 

echo '<link rel="stylesheet" href="../styles/user.css">';

include '../components/header.php';

require_once '../pdf/assets/title_case.php';
require_once '../backend/classes/database.class.php';
require_once '../backend/services/technician.service.php';


if(isset($_GET["id"])) {
  $technician_id = $_GET['id'];
  $conection = new Database();
  $conn = $conection->connect();
  $technicianService = new TechnicianService($conn);
  
  $technician = $technicianService->getTechnician($technician_id);

  $name = $technician->name;
  $document_number = $technician->document_number;
  $document_emissor = $technician->document_emissor;
  $document_uf = $technician->document_uf;
  $phone = $technician->phone;
  $cpf = $technician->cpf;
  $email = $technician->email;
  $created_at = $technician->created_at;
  $address = $technician->address;
  $city = $technician->address->city;
  $state = $technician->address->state;
  $zip = $technician->address->zip;
  $file_path = $technician->file_path;
  $special_needs = $technician->special_needs;
  $scholarship = $technician->scholarship;

  $enabled = match ($technician->enabled) {
    1 => 'Apto',
    0 => 'Inapto',
    default => 'Aguardando aprovação', 
  };

  $statusClass = match ($technician->enabled) {
    1 => 'status-approved',
    0 => 'status-not-approved',
    default => 'status-pending',
  };

  // Formatar data
  $date = new DateTime($created_at);
  $dateF = $date->format('d/m/Y H:i');

  // Formatar filepath
  $string = $file_path;
  $position = strpos($string, "tecnicos");
  $start = $position + strlen("tecnicos/");
  $path = substr($string, $start);

}

?>

<div class="container container-user">
  <a href="tecnicos.php" class="back-link">Voltar</a>
  <h1 class="main-title">Dados do Técnico</h1>

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
      <p class="col-12"><strong>Escolaridade</strong></p>
      <p class="col-12"><?= $scholarship ?></p>
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
    <h3>Documentos</h3>
    <a href="../backend/documentos/tecnicos/<?=$path?>" target="_blank">Download</a>
    
  </div>

  <div class="btns-container">
    <button class="ok-btn" onclick="updateTechnicianStatus(<?= $technician_id ?>, 1)" <?= $technician->enabled === 1 ? 'disabled' : '' ?> >Habilitar Técnico</button>
    <button class="cancel-btn" onclick="updateTechnicianStatus(<?= $technician_id ?>, 0)" <?= $technician->enabled === 0 ? 'disabled' : '' ?> >Desabilitar Técnico</button>
  </div>

</div>

<script>

  function updateTechnicianStatus(technicianId, status) {

  if(confirm("Tem certeza que deseja alterar o status do técnico?")) {
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
          text: "Status do técnico atualizado!",
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