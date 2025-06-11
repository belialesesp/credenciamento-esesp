<?php

require_once "../pdf/assets/title_case.php";
require_once '../components/header.php';
require_once '../backend/api/get_registers.php';
require_once '../backend/api/get_all_courses.php';
require_once '../backend/classes/database.class.php';

// session_start();


$conection = new Database();
$conn = $conection->connect(); 

$teachers = get_postg_docente($conn);
$courses = get_all_postg_courses($conn);

function truncate_text($text, $length = 50, $suffix = '...') {
    // Remove extra whitespace and line breaks
    $text = trim(preg_replace('/\s+/', ' ', $text));
    
    // If text is already short enough, return it
    if (strlen($text) <= $length) {
        return $text;
    }
    
    // Find the last space within the length limit
    $truncated = substr($text, 0, $length);
    $lastSpace = strrpos($truncated, ' ');
    
    // If we found a space, truncate there; otherwise at the length limit
    if ($lastSpace !== false) {
        $truncated = substr($truncated, 0, $lastSpace);
    }
    
    return $truncated . $suffix;
}
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
  <h1 class="main-title">Docentes / Assessoramento Técnico <br> Pós Graduação</h1>
  <div class="filter-container">
    <div class="filter-group">
      <label for="category">Filtrar por categoria</label>
      <select name="category" id="category">
        <option value=""></option>
        <option value="1">Docente</option>
        <option value="2">Docente Conteudista</option>
        <option value="9">Assessoramento Técnico</option>
      </select>
    </div>
    <div class="filter-group">
      <label for="course">Filtrar por cursos</label>
      <select name="course" id="course">
        <option value=""></option>
        <?php foreach($courses as $course): ?>
            <option value="<?= $course['id'] ?>" 
                    title="<?= htmlspecialchars($course['name']) ?>">
                <?= htmlspecialchars(truncate_text($course['name'], 45)) ?>
            </option>
        <?php endforeach; ?>  
      </select>
    </div>
    <div class="filter-group">
      <label for="status">Filtrar por status</label>
      <select name="status" id="status">
        <option value=""></option>
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
        <th>Chamado em</th>
        <th>Data de Inscrição</th>
        <th>Cursos e Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($teachers as $teacher): 
    $created_at = $teacher['created_at'];
    $date = new DateTime($created_at);
    $dateF = $date->format('d/m/Y H:i');

    $called_at = $teacher['called_at'];
    $date_calledF = ($called_at === null || $called_at === '') 
        ? '---' 
        : (new DateTime($called_at))->format('d/m/Y');

    // ✅ Parse discipline statuses instead of using enabled
    $disciplinesList = "";
    if (!empty($teacher['discipline_statuses'])) {
        $disciplines = explode('||', $teacher['discipline_statuses']);
        $disciplineItems = [];
        
        foreach ($disciplines as $disc) {
            if (empty($disc)) continue;
            $parts = explode(':', $disc);
            if (count($parts) >= 3) {
                $discName = $parts[1];
                $discStatus = $parts[2];
                
                $statusText = match($discStatus) {
                    '1' => 'Apto',
                    '0' => 'Inapto',
                    default => 'Aguardando'
                };
                
                $statusClass = match($discStatus) {
                    '1' => 'text-success',
                    '0' => 'text-danger',
                    default => 'text-warning'
                };
                
                $disciplineItems[] = "<span class='d-block'><strong>{$discName}:</strong> <span class='{$statusClass}'>{$statusText}</span></span>";
            }
        }
        
        $disciplinesList = implode("", $disciplineItems);
    }
  ?>
      <tr onclick="window.location.href='docente-pos.php?id=<?= $teacher['id']?>'" style="cursor: pointer;">
    <td><?= titleCase($teacher['name']) ?></td>
    <td><?= strtolower($teacher['email']) ?></td>
    <td><?= $date_calledF ?></td>
    <td><?= $dateF ?></td>
    <td><?= $disciplinesList ?: '<span class="text-muted">Sem disciplinas</span>' ?></td>
  </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>

  function fetchFilteredData() {
    const category = document.getElementById('category').value;
    const course = document.getElementById('course').value;
    const status = document.getElementById('status').value;

    if (!category && !course && !status) {
      fetch('../backend/api/get_filtered_teachers_postg.php')
        .then(response => response.json())
        .then(data => {
          updateTable(data);
        })
        .catch(error => console.error('Erro:', error));
      return;
    }
    
    const queryParams = new URLSearchParams();
    if (category) queryParams.append('category', category);
    if (course) queryParams.append('course', course);
    if (status) queryParams.append('status', status);
    
    fetch(`../backend/api/get_filtered_teachers_postg.php?${queryParams.toString()}`)
    .then(response => response.json())
    .then(data => {
        updateTable(data);
    })
    .catch(error => console.error('Erro:', error));
  }

  document.getElementById('category').addEventListener('change', fetchFilteredData);
  document.getElementById('course').addEventListener('change', fetchFilteredData);
  document.getElementById('status').addEventListener('change', fetchFilteredData);

  document.getElementById('category').addEventListener('change', fetchFilteredData);
  document.getElementById('course').addEventListener('change', fetchFilteredData);


  function updateTable(teachers) {
    const tbody = document.querySelector('table tbody');
    tbody.innerHTML = '';
    
    teachers.forEach(teacher => {
      const enabled = getStatusText(teacher.enabled);
      const statusClass = getStatusClass(teacher.enabled);
      const date = new Date(teacher.created_at);
      const dateF = date.toLocaleString('pt-BR', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
      });
      const calledF = teacher.called_at 
          ? new Date(teacher.called_at).toLocaleString('pt-BR', {
              day: '2-digit',
              month: '2-digit',
              year: 'numeric',
          })
          : '---';
        
      const row = `
        <tr onclick="window.location.href='docente-pos.php?id=${teacher.id}'" style="cursor: pointer;">
          <td>${titleCase(teacher.name)}</td>
          <td>${teacher.email.toLowerCase()}</td>
          <td>${calledF}</td>
          <td>${dateF}</td>
          <td class="${statusClass}">${enabled}</td>
        </tr>
      `;
      tbody.innerHTML += row;
    });
  }

  function getStatusText(enabled) {
    switch(enabled) {
      case 1: return 'Apto';
      case 0: return 'Inapto';
      default: return 'Aguardando';
    }
  }

  function getStatusClass(enabled) {
    switch(enabled) {
      case 1: return 'status-approved';
      case 0: return 'status-not-approved';
      default: return 'status-pending';
    }
  }

  function titleCase(str) {
    return str.toLowerCase().replace(/(?:^|\s)\w/g, function(letter) {
      return letter.toUpperCase();
    });
  }
</script>