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

    post_technician($conn, $id);

    exit(0);
  } else {
    $_SESSION['message'] = 'Algo de errado aconteceu';
    header('Location: ../../error.html');
    exit(0);
  }

}
function create_user_account($conn, $technician_id, $name, $email, $cpf, $user_type) {
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
                ':type_id' => $technician_id
            ]);
            
            return true;
        }
    } catch (PDOException $e) {
        error_log("Error creating user account: " . $e->getMessage());
        return false;
    }
}
function post_technician($conn, $address_id) {
  $name = $_POST["name"];
  $cpf = $_POST["cpf"];
  $documentNumber = $_POST["rg"];
  $rgEmissor = $_POST["rgEmissor"];
  $rgUf = $_POST["rgUf"];
  $email = $_POST["email"];
  $phone = $_POST["phone"];
  $scholarship = $_POST["scholarship"];
  $specialNeeds = strlen($_POST['specialNeedsDetails']) > 0 ? $_POST['specialNeedsDetails'] : 'Não';

  $terms = filter_input(INPUT_POST, "terms", FILTER_VALIDATE_BOOL);
  $terms2 = filter_input(INPUT_POST, "terms2", FILTER_VALIDATE_BOOL);

  if (!$terms | !$terms2) {
    die("Os termos devem ser aceitos");
  }

  $sql = "
    INSERT INTO
     technician (
      name,
      email,
      cpf,
      document_number,
      document_emissor,
      document_uf,
      phone,
      scholarship,
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
     :scholarship,
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
    ':scholarship'=> $scholarship,
    ':specialNeeds' => $specialNeeds,
    ':address_id' => $address_id
  ];

  $query_execute = $query_run->execute($data);

  if($query_execute) {
    $id = $conn->lastInsertId();
    create_user_account($conn, $id, $name, $email, $cpf, 'technician');
    post_documentation($conn, $id);
    
    $_SESSION['form_submitted'] = true;
    $response = [
      'success' => true,
      'redirect_url' =>  "https://credenciamento.esesp.es.gov.br/credenciamento/pages/sucesso.php?category=tecnico&id=$id"
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

function post_documentation($conn, $technician_id) {

  if (isset($_FILES['documents']) && $_FILES['documents']['error'] == 0) {
    $fileTmpPath = $_FILES['documents']['tmp_name'];
    $fileName = basename($_FILES['documents']['name']);
    $fileType = $_FILES['documents']['type'];
    $uploadDir = realpath(__DIR__ . '/../documentos/tecnicos/') . '/';

    // Validar o tipo do arquivo
    if ($fileType == 'application/pdf') {

      $filePath = $uploadDir . uniqid() . '-' . $fileName;

      if (move_uploaded_file($fileTmpPath, $filePath)) {

        $sql = "
          INSERT INTO
            documents (
            name,
            path,
            technician_id
          ) VALUES (
            :name,
            :path,
            :technician_id
            )
        ";

        $query_run = $conn->prepare($sql);
        $data = [
          ':name' => $fileName,
          ':path' => $filePath,
          ':technician_id' => $technician_id
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

post_form() ;
