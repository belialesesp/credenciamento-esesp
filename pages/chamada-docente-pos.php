<?php

require_once "../pdf/assets/title_case.php";
require_once '../components/header.php';
require_once '../backend/api/get_registers.php';
require_once '../backend/api/get_all_courses.php';
require_once '../backend/classes/database.class.php';


$conection = new Database();
$conn = $conection->connect(); 

$date = '2025-02-28';

$teachers = get_postdocentes_call($conn, $date);
$courses = get_all_postg_courses($conn);

// var_dump($teachers[0]);

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
  <table class="table table-striped">
    <thead>
      <tr class="row">
        <th class="col-4">Curso</th>
        <th class="col-3">Categoria</th>
        <th class="col-4">Nome</th>
        <th class="col-1 text-center">Inscrição</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($teachers as $teacher): 
        $created_at = $teacher['created_at'];
        $date = new DateTime($created_at);
        $dateF = $date->format('d/m/Y');

      ?>
      <tr class="row">
        <td class="col-4"><?= $teacher['course'] ?></td>
        <td class="col-3"><?= $teacher['category'] ?></td>
        <td class="col-4"><?= titleCase($teacher['name']) ?></td>        
        <td class="col-1 text-center"><?= $dateF ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
  function fetchFilteredData() {
    const category = document.getElementById('category').value;
    const course = document.getElementById('course').value;

    console.log('category: ', category)
    console.log('course: ', course)

    if (!category && !course) {
      fetch('../backend/api/get_filtered_postg_call.php')
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
   
    fetch(`../backend/api/get_filtered_postg_call.php?${queryParams.toString()}`)
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
      const date = new Date(teacher.created_at);
      const dateF = date.toLocaleString('pt-BR', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric',
      });
        
      const row = `
        <tr class='row'>
          <td class='col-4'>${titleCase(teacher.course)}</td>
          <td class='col-3'>${teacher.category}</td>
          <td class='col-4'>${titleCase(teacher.name)}</td>
          <td class='col-1'>${dateF}</td>
        </tr>
      `;
      tbody.innerHTML += row;
    });
  }
  function titleCase(str) {
    return str.toLowerCase().replace(/(?:^|\s)\w/g, function(letter) {
      return letter.toUpperCase();
    });
  }
</script>