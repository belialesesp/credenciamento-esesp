<?php
require "../vendor/autoload.php";
require_once "./assets/title_case.php";

use Dompdf\Dompdf;
use Dompdf\Options;

// Pegar os dados do técnico
require_once "../backend/classes/database.class.php";
require_once "../backend/services/technician.service.php";

session_start();

// if (!isset($_SESSION['form_submitted']) || !$_SESSION['form_submitted']) {
//   die('Acesso não permitido.');
// }


if(isset($_GET["id"])) {
  $technician_id = $_GET["id"];
  $conection = new Database();
  $conn = $conection->connect();
  $technicianService = new TechnicianService($conn);
  
  $technician = $technicianService->getTechnician($technician_id);

  // Pegar as informações do docente
  $name = $technician->name;
  $document_number = $technician->document_number;
  $document_emissor = $technician->document_emissor;
  $document_uf = $technician->document_uf;
  $phone = $technician->phone;
  $cpf = $technician->cpf;
  $email = $technician->email;
  $created_at = $technician->created_at;
  $scholarship = $technician->scholarship;
  $address = $technician->address;
  $city = $technician->address->city;
  $state = $technician->address->state;
  $zip = $technician->address->zip;


  // Formatar data
  $date = new DateTime($created_at);
  $dateF = $date->format('d/m/Y H:i');


  //Gerar pdf

  $html = file_get_contents("templateTecnico.html");
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

  $pdf_name = __DIR__ . '/fichasTecnicos/' . $name . '.pdf';

  $output = $dompdf->output();

  file_put_contents($pdf_name, $output);


  unset($_SESSION['form_submitted']);

}





