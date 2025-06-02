<?php

require_once("../classes/database.class.php");
session_start();


function post_form() {
  if (count($_POST) > 0) {
    $conection = new Database();
    $conn = $conection->connect();

    post_address($conn);


  }  else {
    header('Location: ../../index.html');
  }

}

function post_address($conn) {
  $street = $_POST["address"];
  $addNumber = $_POST["addNumber"];
  $addComplement = $_POST["addComplement"] ?? '';
  $neighborhood = $_POST["neighborhood"];
  $city = $_POST["city"];
  $state = $_POST["state"];
  $zipCode = $_POST["zipCode"];

  $sql = "
    INSERT INTO
      address (
        street,
        address_number,
        complement,
        neighborhood,
        city,
        state,
        zip_code
      ) VALUES (
        :street,
        :addNumber,
        :addComplement,
        :neighborhood,
        :city,
        :state,
        :zipCode
      )
  ";

  $query_run = $conn->prepare($sql);

  $data = [
    ':street' => $street,
    ':addNumber' => $addNumber,
    ':addComplement' => $addComplement,
    ':neighborhood' => $neighborhood,
    ':city' => $city,
    ':state' => $state,
    ':zipCode' => $zipCode,
  ];

  $query_execute = $query_run->execute($data);

  if($query_execute) {
    $id = $conn->lastInsertId();

    post_spcdemand($id, $conn);

    exit(0);
  } else {
    $_SESSION['message'] = 'Algo de errado aconteceu';
    header('Location: ../../error.html');
    exit(0);
  }

}

function post_spcdemand($address_id, $conn) {
  $name = $_POST["name"];
  $cpf = $_POST["cpf"];
  $documentNumber = $_POST["rg"];
  $rgEmissor = $_POST["rgEmissor"];
  $rgUf = $_POST["rgUf"];
  $email = $_POST["email"];
  $phone = $_POST["phone"];
  $specialNeeds = strlen($_POST['specialNeedsDetails']) > 0 ? $_POST['specialNeedsDetails'] : 'Não';


  $terms = filter_input(INPUT_POST, "terms", FILTER_VALIDATE_BOOL);
  $terms2 = filter_input(INPUT_POST, "terms2", FILTER_VALIDATE_BOOL);

  if (!$terms | !$terms2) {
    die("Os termos devem ser aceitos");
  }

  $sql = "
    INSERT INTO
     spcdemand_teacher (
      name,
      email,
      cpf,
      document_number,
      document_emissor,
      document_uf,
      phone,
      special_needs,
      address_id
    ) VALUES (
     :name,
     :email,
     :cpf,
     :documentNumber,
     :documentEmissor,
     :documentUf,
     :phone,
     :specialNeeds,
     :address_id
     )
  ";

  $query_run = $conn->prepare($sql);

  $data = [
    ':name' => $name,
    ':email' => $email,
    ':cpf'=> $cpf,
    ':documentNumber' => $documentNumber,
    ':documentEmissor' => $rgEmissor,
    ':documentUf' => $rgUf,
    ':phone' => $phone,
    ':specialNeeds' => $specialNeeds,
    ':address_id' => $address_id
  ];

  $query_execute = $query_run->execute($data);

  if($query_execute) {
    $id = $conn->lastInsertId();

    post_activities($conn, $id);
    post_education($conn, $id);
    post_certificate($conn, $id);
    post_course($conn, $id);

    $_SESSION['form_submitted'] = true;

    $response = [
      'success' => true,
      'redirect_url' =>  "https://credenciamento.esesp.es.gov.br/credenciamento/pages/sucesso.php?category=demandaesp&id=$id"
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();

  } else {
    $response = [
      'success' => false,
      'redirect_url' => 'https://credenciamento.esesp.es.gov.br/credenciamento/pages/error.html'
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
  }


}

function post_activities($conn, $teacher_id) {
  if($_POST['position']) {

    foreach ($_POST['position'] as $position) {
      $actitivy_id = intval($position);

      $sql = "
        INSERT INTO 
        spcdemand_teacher_activities (
          teacher_id,
          activity_id
        ) VALUES (
          :teacher_id,
          :actitivy_id
        )";

        $query_run = $conn->prepare($sql);
        $data = [
            ':teacher_id' => $teacher_id,
            ':actitivy_id' => $actitivy_id
        ];

        $query_execute = $query_run->execute($data);

        if (!$query_execute) {
          $_SESSION['message'] = 'Erro ao inserir no banco de dados';
          var_dump('Erro: ', $query_run->errorInfo());
          exit(0);
        }
    };
  }
}

function post_education($conn, $teacher_id) {

  if (isset($_POST['degree_0'])) {

      $index = 0;

      while (true) {

          $degree_key = "degree_$index";
          $courseName_key = "courseName_$index";
          $institution_key = "institution_$index";


          if (!isset($_POST[$degree_key])) {
              break;
          }


          $degree = $_POST[$degree_key];
          $courseName = $_POST[$courseName_key];
          $institution = $_POST[$institution_key];


          $sql = "
          INSERT INTO
              education_degree (
                  degree,
                  institution,
                  course_name,
                  spc_teacher_id
              ) VALUES (
                  :degree,
                  :institution,
                  :courseName,
                  :teacher_id
              )
          ";

          $query_run = $conn->prepare($sql);
          $data = [
              ':degree' => $degree,
              ':institution' => $institution,
              ':courseName' => $courseName,
              ':teacher_id' => $teacher_id
          ];

          $query_execute = $query_run->execute($data);

          if (!$query_execute) {
              $_SESSION['message'] = 'Erro ao inserir no banco de dados';
              var_dump('Erro: ', $query_run->errorInfo());
              exit(0);
          }


          $index++;
      }

      $_SESSION['message'] = 'Dados inseridos com sucesso';

  } else {
      $_SESSION['message'] = 'Nenhum dado enviado';
      exit(0);
  }
}

function post_certificate($conn, $teacher_id) {

  if (isset($_FILES['documents']) && $_FILES['documents']['error'] == 0) {
    $fileTmpPath = $_FILES['documents']['tmp_name'];
    $fileName = basename($_FILES['documents']['name']);
    $fileType = $_FILES['documents']['type'];
    $uploadDir = realpath(__DIR__ . '/../documentos/demandas/') . '/';


    // Validar o tipo do arquivo
    if ($fileType == 'application/pdf') {

      $filePath = $uploadDir . uniqid() . '-' . $fileName;

      if (move_uploaded_file($fileTmpPath, $filePath)) {

        $sql = "
          INSERT INTO
            documents (
            name,
            path,
            spc_teacher_id
          ) VALUES (
            :name,
            :path,
            :teacher_id
            )
        ";

        $query_run = $conn->prepare($sql);
        $data = [
          ':name' => $fileName,
          ':path' => $filePath,
          ':teacher_id' => $teacher_id
        ];

        $query_execute = $query_run->execute( $data );

        if($query_execute) {
          $_SESSION['message'] = 'Dados inseridos com sucesso';
        } else {
          $_SESSION['message'] = 'Algo de errado aconteceu';
          exit(0);
        }

      } else {
        echo "Erro ao salvar o arquivo.";
      }
    } else {
        echo "Por favor, envie apenas arquivos PDF.";
    }

  } else {
    echo "Erro ao enviar o arquivo.";
  }

}

function post_course($conn, $teacher_id) {

  if(isset($_POST['course_0'])) {

    $index = 0;

    while (true) {

      $course_key = "course_$index";

      if (!isset($_POST[$course_key])) {
        break;
      }

      $course = $_POST[$course_key];

      $sql = "
        INSERT INTO
         spcdemand_teacher_courses(
           course_id,
           teacher_id
         ) VALUES (
           :course,
           :teacher_id
         )
      ";

      $query_run = $conn->prepare($sql);
      $data = [
        ':course' => $course,
        'teacher_id' => $teacher_id
      ];

      $query_execute = $query_run->execute( $data );

      if (!$query_execute) {
        $_SESSION['message'] = 'Erro ao inserir no banco de dados';
        var_dump('Erro: ', $query_run->errorInfo());
        exit(0);
    }


    $index++;

    }
  }

}

post_form();


