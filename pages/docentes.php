<?php

require_once "../pdf/assets/title_case.php";
require_once '../components/header.php';
require_once '../backend/api/get_registers.php';
require_once '../backend/api/get_all_courses.php';
require_once '../backend/classes/database.class.php';

$conection = new Database();
$conn = $conection->connect();

$teachers = get_docente($conn);
$courses = get_all_courses($conn);
?>

<style>
  .discipline-status {
    display: inline-block;
    padding: 2px 8px;
    margin: 2px;
    border-radius: 4px;
    font-size: 12px;
    background-color: #f0f0f0;
  }

  .discipline-status.status-approved {
    background-color: #d4edda;
    color: #155724;
  }

  .discipline-status.status-not-approved {
    background-color: #f8d7da;
    color: #721c24;
  }

  .discipline-status.status-pending {
    background-color: #fff3cd;
    color: #856404;
  }

  .discipline-info {
    margin-bottom: 8px;
  }

  .teacher-row {
    cursor: pointer;
  }

  .teacher-row:hover {
    background-color: #f5f5f5;
  }
</style>

<div class="container">
  <h1 class="main-title">Docentes</h1>
  <div class="filter-container">
    <div class="filter-group">
      <label for="category">Filtrar por categoria</label>
      <select name="category" id="category">
        <option value=""></option>
        <option value="1">Docente</option>
        <option value="2">Docente Conteudista</option>
        <option value="3">Docente Assistente</option>
        <option value="4">Coordenador Técnico</option>
        <option value="5">Conferencista / Palestrante</option>
        <option value="6">Painelista / Debatedor</option>
        <option value="7">Moderador</option>
        <option value="8">Reunião Técnica</option>
        <option value="9">Assessoramento Técnico</option>
        <option value="10">Revisão de Texto</option>
        <option value="11">Entrevista</option>
      </select>
    </div>
    <div class="filter-group">
      <label for="course">Filtrar por cursos</label>
      <select name="course" id="course">
        <option value=""></option>
        <?php
        foreach ($courses as $course): ?>
          <option value="<?= $course['id'] ?>"><?= $course['name'] ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group">
      <label for="status">Filtrar por status</label>
      <select name="status" id="status">
        <option value="">Todos</option>
        <option value="1">Apto</option>
        <option value="0">Inapto</option>
        <option value="null">Aguardando</option>
      </select>
    </div>
  </div>
  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th>Nome</th>
        <th>Email</th>
        <th>Data de Inscrição</th>
        <th>Cursos e Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($teachers as $teacher):
        $created_at = $teacher['created_at'];
        $date = new DateTime($created_at);
        $dateF = $date->format('d/m/Y H:i');

        // Parse discipline statuses
        $disciplineStatuses = [];
        if (!empty($teacher['discipline_statuses'])) {
          $statusPairs = explode('||', $teacher['discipline_statuses']);
          foreach ($statusPairs as $pair) {
            if (!empty($pair)) {
              list($discId, $discName, $status) = explode(':', $pair);
              $disciplineStatuses[] = [
                'id' => $discId,
                'name' => $discName,
                'status' => $status === 'null' ? null : (int)$status
              ];
            }
          }
        }
      ?>
        <tr class="teacher-row" onclick="window.location.href='docente.php?id=<?= $teacher['id'] ?>'">
          <td><?= titleCase($teacher['name']) ?></td>
          <td><?= strtolower($teacher['email']) ?></td>
          <td><?= $dateF ?></td>
          <td>
            <?php foreach ($disciplineStatuses as $disc):
              $statusText = match ($disc['status']) {
                1 => 'Apto',
                0 => 'Inapto',
                default => 'Aguardando',
              };
              $statusClass = match ($disc['status']) {
                1 => 'status-approved',
                0 => 'status-not-approved',
                default => 'status-pending',
              };
            ?>
              <div class="discipline-info">
                <strong><?= htmlspecialchars($disc['name']) ?>:</strong>
                <span class="discipline-status <?= $statusClass ?>"><?= $statusText ?></span>
              </div>
            <?php endforeach; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
// Update the JavaScript status handling
function fetchFilteredData() {
  const category = document.getElementById('category').value;
  const course = document.getElementById('course').value;
  const status = document.getElementById('status').value;

  const queryParams = new URLSearchParams();
  if (category) queryParams.append('category', category);
  if (course) queryParams.append('course', course);
  if (status) queryParams.append('status', status);
  
  fetch(`../backend/api/get_filtered_teachers.php?${queryParams.toString()}`)
  .then(response => response.json())
  .then(data => {
      updateTable(data);
  })
  .catch(error => console.error('Erro:', error));
}

// Update the status text/class functions to handle null properly
function getStatusText(status) {
  if (status === null || status === 'null' || status === '') {
    return 'Aguardando';
  } else if (status == 1) {
    return 'Apto';
  } else if (status == 0) {
    return 'Inapto';
  }
  return 'Aguardando';
}

function getStatusClass(status) {
  if (status === null || status === 'null' || status === '') {
    return 'status-pending';
  } else if (status == 1) {
    return 'status-approved';
  } else if (status == 0) {
    return 'status-not-approved';
  }
  return 'status-pending';
}
</script>