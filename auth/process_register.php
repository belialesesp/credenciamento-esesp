<?php
require_once '../backend/classes/database.class.php';
// session_start();
header('Content-Type: application/json');

function post_register() {
  
  try {
    validate_data();

    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $conection = new Database();
    $conn = $conection->connect();
  
    $sql = '
      INSERT INTO
        user (
        name,
        email,
        password_hash
      ) VALUES (
       :name,
       :email,
       :password_hash
      )    
    ';
  
    $query_run = $conn->prepare($sql);
    $data = [
      ':name' => $_POST['name'],
      ':email' => $_POST['email'],
      ':password_hash' => $password_hash
    ];

    $query_run->execute($data);

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Registro realizado com sucesso']);
    exit;

  } catch (PDOException $e) {

    header('Content-Type: application/json');
    header('HTTP/1.1 400 Bad Request');
    echo json_encode([
      'success' => false, 
      'message' => $e->errorInfo[1] == 1062 ? 'Email já cadastrado!' : $e->getMessage()
    ]);
    exit;

  } catch (Exception $e) {

    // Send JSON error response
    header('Content-Type: application/json');
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
  }
}

function validate_data() {
  if(empty($_POST['name'])) {
    throw new Exception('O nome é obrigatório');
  }
  
  if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    throw new Exception('Email inválido');
  }

  if(strlen($_POST['password']) < 8) {
    throw new Exception('A senha deve ter no mínimo 8 caracteres');
  }

  if(! preg_match("/[a-z]/i", $_POST['password'])) {
    throw new Exception('A senha deve contar pelo menos uma letra');
  }

  if(! preg_match("/[0-9]/i", $_POST['password'])) {
    throw new Exception('A senha deve contar pelo menos um número');
  }

  if($_POST['password'] !== $_POST['password_confirmation']) {
    throw new Exception('As senhas devem ser iguais');
  }

}

post_register();