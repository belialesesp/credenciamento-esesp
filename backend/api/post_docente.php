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
    
    // Call post_docente with the address ID
    post_docente($id, $conn);

  } else {
    $_SESSION['message'] = 'Algo de errado aconteceu';
    header('Location: ../../error.html');
    exit(0);
  }
}

function post_docente($address_id, $conn) {
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
     teacher (
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

    // CREATE USER ACCOUNT HERE - Now with correct variables in scope
    create_user_account($conn, $id, $name, $email, $cpf, 'teacher');

    post_activities($conn, $id);
    post_education($conn, $id);
    post_certificate($conn, $id);
    post_discipline($conn, $id);
    post_modules($conn, $id);

    $_SESSION['form_submitted'] = true;

    $response = [
      'success' => true,
      'redirect_url' =>  "https://credenciamento.esesp.es.gov.br/credenciamento/pages/sucesso.php?category=docente&id=$id"
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

function create_user_account($conn, $teacher_id, $name, $email, $cpf, $user_type) {
    try {
        // Clean CPF
        $cleanCpf = preg_replace('/[^0-9]/', '', $cpf);
        
        // Check if user already exists
        $checkStmt = $conn->prepare("SELECT id FROM user WHERE cpf = :cpf AND user_type = :type");
        $checkStmt->execute([':cpf' => $cleanCpf, ':type' => $user_type]);
        
        if (!$checkStmt->fetch()) {
            // Create user with CPF as default password
            $passwordHash = password_hash($cleanCpf, PASSWORD_DEFAULT);
            
            $insertStmt = $conn->prepare("
                INSERT INTO user (name, email, cpf, password_hash, user_type, type_id, first_login) 
                VALUES (:name, :email, :cpf, :password, :type, :type_id, TRUE)
            ");
            
            $insertStmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':cpf' => $cleanCpf,
                ':password' => $passwordHash,
                ':type' => $user_type,
                ':type_id' => $teacher_id
            ]);
            
            error_log("User account created for: " . $name . " (ID: " . $teacher_id . ")");
            return true;
        } else {
            error_log("User account already exists for CPF: " . $cleanCpf);
        }
    } catch (PDOException $e) {
        error_log("Error creating user account: " . $e->getMessage());
        return false;
    }
}

function post_activities($conn, $teacher_id) {
  if($_POST['position']) {

    foreach ($_POST['position'] as $position) {
      $actitivy_id = intval($position);

      $sql = "
        INSERT INTO
        teacher_activities (
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
              teacher_id
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
    $uploadDir = realpath(__DIR__ . '/../documentos/docentes/') . '/';

    // Validar o tipo do arquivo
    if ($fileType == 'application/pdf') {

      $filePath = $uploadDir . uniqid() . '-' . $fileName;

      if (move_uploaded_file($fileTmpPath, $filePath)) {

        $sql = "
          INSERT INTO
            documents (
            name,
            path,
            teacher_id
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

function post_discipline($conn, $teacher_id) {

  if(isset($_POST['disciplina_0'])) {

    $index = 0;

    while (true) {

      $disciplina_key = "disciplina_$index";

      if (!isset($_POST[$disciplina_key])) {
        break;
      }

      $discipline = $_POST[$disciplina_key];

      $sql = "
        INSERT INTO
         teacher_disciplines(
           discipline_id,
           teacher_id
         ) VALUES (
           :discipline,
           :teacher_id
         )
      ";

      $query_run = $conn->prepare($sql);
      $data = [
        ':discipline' => $discipline,
        ':teacher_id' => $teacher_id
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

function post_modules($conn, $teacher_id) {
  $postData = $_POST;
  $modules = [];

  foreach ($postData as $key => $value) {
    if (strpos($key, 'modulos_') === 0 && !empty($value)) {
      $modules[$key] = $value;     
    } 
  }

  foreach($modules as $m) {
    foreach($m as $module) {
      $sql = "
      INSERT INTO
        teacher_module(
          teacher_id,
          module_id
        ) VALUES (
         :teacher_id,
         :module
        )
      ";

      $query_run = $conn->prepare($sql);
      $data = [
        ':module' => $module,
        ':teacher_id' => $teacher_id
      ];

      $query_execute = $query_run->execute( $data );

      if (!$query_execute) {
        $_SESSION['message'] = 'Erro ao inserir no banco de dados';
        var_dump('Erro: ', $query_run->errorInfo());
        exit(0);
      }
    }
  }
}

post_form();