<?php

require_once "../pdf/assets/title_case.php";
require_once '../components/header.php';
require_once '../backend/api/get_registers.php';
require_once '../backend/classes/database.class.php';


$conection = new Database();
$conn = $conection->connect(); 

$date = '2025-02-28';

$interpreters = get_interpreter_call($conn, $date);

?>

<div class="container">
  <h1 class="main-title">Intérpretes de Libras</h1>
  <table class="table table-striped">
    <thead>
      <tr class="row">
        <th class="col-6">Nome</th>
        <th class="col-3">Data de Inscrição</th>
        <th class="col-3">Situação</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($interpreters as $interpreter): 
        $enabled = match ($interpreter['enabled']) {
          1 => 'Apto',
          0 => 'Inapto',
          default => 'Aguardando', 
        };
        $statusClass = match ($interpreter['enabled']) {
          1 => 'status-approved',
          0 => 'status-not-approved',
          default => 'status-pending',
        };

        $created_at = $interpreter['created_at'];
        $date = new DateTime($created_at);
        $dateF = $date->format('d/m/Y');

      ?>
      <tr class="row">
        <td class="col-6"><?= titleCase($interpreter['name']) ?></td>
        <td class="col-3"><?= $dateF ?></td>
        <td class="col-3 <?= $statusClass ?>"><?= $enabled ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
  function titleCase(str) {
    return str.toLowerCase().replace(/(?:^|\s)\w/g, function(letter) {
      return letter.toUpperCase();
    });
  }
</script>