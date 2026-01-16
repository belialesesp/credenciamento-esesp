<?php
require "../vendor/autoload.php";
require_once "./assets/title_case.php";

use Dompdf\Dompdf;
use Dompdf\Options;

// Pegar os dados do docente
require_once "../backend/classes/database.class.php";
require_once "../backend/services/teacherpos.service.php";

session_start();

// if (!isset($_SESSION['form_submitted']) || !$_SESSION['form_submitted']) {
//   die('Acesso não permitido.');
// }

if(isset($_GET["id"])) {
  $teacher_id = $_GET["id"];
  $conection = new Database();
  $conn = $conection->connect();
  $teacherService = new TeacherPostGService($conn);
  
  $teacher = $teacherService->getTeacherPostG($teacher_id);

  // Pegar as informações do docente
  $name = $teacher->name;
  $document_number = $teacher->document_number;
  $document_emissor = $teacher->document_emissor;
  $document_uf = $teacher->document_uf;
  $phone = $teacher->phone;
  $cpf = $teacher->cpf;
  $email = $teacher->email;
  $created_at = $teacher->created_at;
  $address = $teacher->address;
  $city = $teacher->address->city;
  $state = $teacher->address->state;
  $zip = $teacher->address->zip;
  $education_degree = $teacher->educations;
  $disciplines = $teacher->disciplines;
  $activities = $teacher->activities;

  // Formatar data
  $date = new DateTime($created_at);
  $dateF = $date->format('d/m/Y H:i');

  // Formatar atividades 
  $activities_str = '';

  foreach($activities as $activity) {
    $activities_str .= $activity['name'] . ', ';
  }

  $actitivitiesF = substr( $activities_str,0,-2);

  // Formatar cursos
  $disciplines_str = '';

  foreach($disciplines as $discipline) {
    $disciplines_str .= 'Pós Graduação: ' . $discipline->post_graduation . '<br>' . ' Curso: ' . $discipline->name . '<br><br>';
  }

  // Formatar titulação
  $degree_str = '';

  foreach($education_degree as $degree) {
    $degree_str .= 'Curso: ' . $degree->name . ' - Nível: ' . $degree->degree . ' - Instituição: ' . $degree->institution . '<br>';
  }


  //Gerar pdf

  $html = file_get_contents("templateDocentePos.html");
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
      "{{disciplines}}",
      "{{degree}}",
      "{{activities}}",
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
      $disciplines_str,
      $degree_str,
      $actitivitiesF,
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

  $pdf_name = __DIR__ . '/fichasDocente/' . $name . '.pdf';

  $output = $dompdf->output();

  file_put_contents($pdf_name, $output);


  unset($_SESSION['form_submitted']);

}





