<?php
include '../components/header.php';

require_once "../pdf/assets/title_case.php";
require_once '../backend/classes/database.class.php';
require_once '../backend/api/get_registers.php';

session_start();


$conection = new Database();
$conn = $conection->connect(); 

$interpreters = get_interpreters($conn);

?>

<div class="container">
  <h1 class="main-title">Intérpretes</h1>
  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th>Nome</th>
        <th>Email</th>
        <th>Telefone</th>
        <th>Data de Inscrição</th>
        <th>Situação</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($interpreters as $interpreter): 
        $enabled = match ($interpreter['enabled']) {
          1 => 'Apto',
          0 => 'Não apto',
          default => 'Aguardando aprovação', 
        };
        $statusClass = match ($interpreter['enabled']) {
          1 => 'status-approved',
          0 => 'status-not-approved',
          default => 'status-pending',
        };

        $created_at = $interpreter['created_at'];
        $date = new DateTime($created_at);
        $dateF = $date->format('d/m/Y H:i');

      ?>
      <tr onclick="window.location.href='interprete.php?id=<?= $interpreter['id']?>'" style="cursor: pointer;">
        <td><?= titleCase($interpreter['name']) ?></td>
        <td><?= $interpreter['email'] ?></td>
        <td><?= $interpreter['phone'] ?></td>
        <td><?= $dateF ?></td>
        <td class="<?= $statusClass ?>"><?= $enabled ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>