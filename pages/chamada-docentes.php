<?php

require_once "../pdf/assets/title_case.php";
require_once '../components/header.php';
require_once '../backend/api/get_registers.php';
require_once '../backend/api/get_all_courses.php';
require_once '../backend/classes/database.class.php';

$conection = new Database();
$conn = $conection->connect(); 

$date = '2025-02-28';
$teachers = get_docentes_call($conn, $date);
$courses = get_all_courses($conn);

?>

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
          foreach($courses as $course):?>
          <option value="<?= $course['id']?>"><?= $course['name'] ?></option>
        <?php endforeach; ?>  
      </select>
    </div>
    <div class="filter-group">
      <label for="status">Filtrar por status</label>
      <select name="status" id="status">
        <option value=""></option>
        <option value="1">Apto</option>
        <option value="0">Inapto</option>
        <option value="pending">Aguardando</option>
      </select>
    </div>
  </div>
  <table class="table table-striped">
    <thead>
      <tr class="row">
        <th class="col-4">Curso</th>
        <th class="col-3">Categoria</th>
        <th class="col-3">Nome</th>
        <th class="col-1 text-center">Inscrição</th>
        <th class="col-1 text-center">Status</th>
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
        $dateF = $date->format('d/m/Y');

      ?>
      <tr class="row">
        <td class="col-4"><?= $teacher['course'] ?></td>
        <td class="col-3"><?= $teacher['category'] ?></td>
        <td class="col-3"><?= titleCase($teacher['name']) ?></td>        
        <td class="col-1 text-center"><?= $dateF ?></td>
        <td class="col-1 text-center <?= $statusClass ?>"><?= $enabled ?></td>
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
      fetch('../backend/api/get_filtered_teacher_call.php')
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
   
    fetch(`../backend/api/get_filtered_teacher_call.php?${queryParams.toString()}`)
    .then(response => response.json())
    .then(data => {
        updateTable(data);
    })
    .catch(error => console.error('Erro:', error));
  }

  document.getElementById('category').addEventListener('change', fetchFilteredData);
  document.getElementById('course').addEventListener('change', fetchFilteredData);
  document.getElementById('status').addEventListener('change', fetchFilteredData);

  function updateTable(teachers) {
    const tbody = document.querySelector('table tbody');
    tbody.innerHTML = '';
    
    teachers.forEach(teacher => {
      const date = new Date(teacher.created_at);
      const dateF = date.toLocaleString('pt-BR', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric',
      });
      
      const enabled = getStatusText(teacher.enabled);
      const statusClass = getStatusClass(teacher.enabled);
        
      const row = `
        <tr class='row'>
          <td class='col-4'>${titleCase(teacher.course)}</td>
          <td class='col-3'>${teacher.category}</td>
          <td class='col-3'>${titleCase(teacher.name)}</td>
          <td class='col-1 text-center'>${dateF}</td>
          <td class='col-1 text-center ${statusClass}'>${enabled}</td>
        </tr>
      `;
      tbody.innerHTML += row;
    });
  }

  function getStatusText(enabled) {
    switch(enabled) {
      case 1: 
      case '1': 
        return 'Apto';
      case 0:
      case '0': 
        return 'Inapto';
      default: 
        return 'Aguardando';
    }
  }

  function getStatusClass(enabled) {
    switch(enabled) {
      case 1:
      case '1': 
        return 'status-approved';
      case 0:
      case '0': 
        return 'status-not-approved';
      default: 
        return 'status-pending';
    }
  }

  function titleCase(str) {
    return str.toLowerCase().replace(/(?:^|\s)\w/g, function(letter) {
      return letter.toUpperCase();
    });
  }
</script>