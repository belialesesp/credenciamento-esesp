<?php 

require_once '../components/header.php';

if(isset($_GET['id'])) {
  $id = $_GET['id'];
  $category = $_GET['category'];

} else {
  header('Location: error.html');
}

?>


<div class="container container-center">
  <h1>Credenciamento realizado com sucesso!</h1>

  <p>Clique no botão abaixo para gerar seu comprovante</p>

  <a href="../pdf/generate-<?= $category ?>-pdf.php?id=<?= $id ?>" class="generate-pdf-link">Gerar comprovante</a>

  <div class="info-box">
    <h4>Atenção!</h4>
    <p>
      Para visualizar ou imprimir esse documento, é
      necessário ter o
      <a href="https://get.adobe.com/br/reader/" target="_blank"
        >Adobe Reader</a
      >
      instalado em seu computador.
    </p>
    <p>
      Em caso de dúvidas ou problemas, entre em contato conosco pelo
      e-mail
      <a href="mailto:credenciamento@esesp.es.gov.br">credenciamento@esesp.es.gov.br</a>.
    </p>
  </div>
  
</div>


<?php

require_once '../components/footer.php';