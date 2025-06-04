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
?>

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
        <?php 
          foreach($courses as $course):?>
          <option value="<?= $course['id']?>"><?= $course['name'] ?></option>
        <?php endforeach; ?>  
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
        <th>Situação</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($teachers as $teacher): 
        $enabled = match ($teacher['enabled']) {
          1 => 'Apto',
          0 => 'Inapto',
          default => 'Aguardando', 
        };
        $statusClass = match ($teacher['enabled']) {
          1 => 'status-approved',
          0 => 'status-not-approved',
          default => 'status-pending',
        };

        $created_at = $teacher['created_at'];
        $date = new DateTime($created_at);
        $dateF = $date->format('d/m/Y H:i');

        $called_at = $teacher['called_at'];
        $date_calledF = ($called_at === null || $called_at === '') 
            ? '---' 
            : (new DateTime($called_at))->format('d/m/Y');
        

      ?>
      <tr onclick="window.location.href='docente-pos.php?id=<?= $teacher['id']?>'" style="cursor: pointer;">
        <td><?= titleCase($teacher['name']) ?></td>
        <td><?= strtolower($teacher['email']) ?></td>
        <td><?= $date_calledF ?></td>
        <td><?= $dateF ?></td>
        <td class="<?= $statusClass ?>"><?= $enabled ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>

  function fetchFilteredData() {
    const category = document.getElementById('category').value;
    const course = document.getElementById('course').value;

    if (!category && !course) {
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
    
    fetch(`../backend/api/get_filtered_teachers_postg.php?${queryParams.toString()}`)
    .then(response => response.json())
    .then(data => {
        updateTable(data);
    })
    .catch(error => console.error('Erro:', error));
  }

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