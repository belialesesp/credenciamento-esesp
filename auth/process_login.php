<?php

session_start();
require_once '../backend/classes/database.class.php';

function processLogin($email, $password) {
  $response = [
    'success' => false,
    'message' => ''
  ];

  try {
    $conection = new Database();
    $conn = $conection->connect();

    $sql = "SELECT * FROM user WHERE email = :email";
    $query_run = $conn->prepare($sql);
    $data = [
        ':email' => $email,
    ];
    $query_run->execute($data);

    $user = $query_run->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $response['success'] = true;
        $response['message'] = 'Login realizado com sucesso!';
    } else {
        $response['message'] = 'Email ou senha inválidos!';
    }
  } catch (PDOException $e) {
    $response['message'] = 'Erro ao processar login: ' . $e->getMessage();
  }

  return $response;
}

// Processa a requisição POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'] ?? '';
  $password = $_POST['password'] ?? '';
  
  $result = processLogin($email, $password);
  
  if ($result['success']) {
    header('Location: ../pages/home.php');
    exit;
  } else {
    $_SESSION['login_error'] = $result['message'];
    header('Location: ../pages/login.php');
    exit;
  }
}
