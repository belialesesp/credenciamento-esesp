<?php
require "../vendor/autoload.php";
require_once "./assets/title_case.php";

use Dompdf\Dompdf;
use Dompdf\Options;

// Pegar os dados do interprete
require_once "../backend/classes/database.class.php";
require_once "../backend/services/interprete.service.php";

session_start();

if (!isset($_SESSION['form_submitted']) || !$_SESSION['form_submitted']) {
  die('Acesso não permitido.');
}


if(isset($_GET["id"])) {
  $interpreter_id = $_GET["id"];
  $conection = new Database();
  $conn = $conection->connect();
  $interpreterService = new InterpreterService($conn);
  
  $interpreter = $interpreterService->getInterpreter($interpreter_id);

  // Pegar as informações do docente
  $name = $interpreter->name;
  $document_number = $interpreter->document_number;
  $document_emissor = $interpreter->document_emissor;
  $document_uf = $interpreter->document_uf;
  $phone = $interpreter->phone;
  $cpf = $interpreter->cpf;
  $email = $interpreter->email;
  $created_at = $interpreter->created_at;
  $scholarship = $interpreter->scholarship;
  $address = $interpreter->address;
  $city = $interpreter->address->city;
  $state = $interpreter->address->state;
  $zip = $interpreter->address->zip;


  // Formatar data
  $date = new DateTime($created_at);
  $dateF = $date->format('d/m/Y H:i');


  //Gerar pdf

  $html = file_get_contents("templateInterprete.html");
  $html = str_replace(
    [
      "{{date}}",
      "{{name}}",
      "{{document_number}}",
      "{{document_emissor}}",
      "{{document_uf}}",
      "{{cpf}}",
      "{{phone}}",
      "{{email}}",
      "{{address}}",
      "{{city}}",
      "{{state}}",
      "{{zip}}",
      "{{scholarship}}",
    ],
    [
      $dateF,
      titleCase($name),
      $document_number,
      strtoupper($document_emissor),
      strtoupper($document_uf),
      $cpf,
      $phone,
      $email,
      $address,
      $city,
      strtoupper($state),
      $zip,
      $scholarship,
    ],
    $html
  );

  $options = new Options;
  $options->setChroot(__DIR__);
  $options->setIsRemoteEnabled(true);

  $dompdf = new Dompdf($options);
  $dompdf->setPaper("A4","portrait");
  $dompdf->loadHtml($html);
  $dompdf->render();
  $dompdf->addInfo("Title", "Comprovante de Credenciamento");
  $dompdf->stream("comprovante_credenciamento.pdf", ["Attachment" => 0]);

  $pdf_name = __DIR__ . '/fichasInterpretes/' . $name . '.pdf';

  $output = $dompdf->output();

  file_put_contents($pdf_name, $output);


  unset($_SESSION['form_submitted']);

}





