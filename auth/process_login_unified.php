<?php
// auth/process_login_unified.php
session_start();
require_once '../backend/classes/auth.class.php';

// Process POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.php');
    exit;
}

// Get CPF and password
$cpf = $_POST['cpf'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($cpf) || empty($password)) {
    $_SESSION['login_error'] = 'Por favor, preencha todos os campos.';
    header('Location: ../pages/login.php');
    exit;
}

// Try to authenticate
$auth = new AuthHelper();
$result = $auth->authenticate($cpf, $password);

if ($result) {
    // Set session variables
    $_SESSION['user_id'] = $result['user']['id'];
    $_SESSION['user_name'] = $result['user']['name'];
    $_SESSION['user_email'] = $result['user']['email'];
    $_SESSION['user_type'] = $result['type'];
    $_SESSION['user_table'] = $result['table'];
    $_SESSION['first_login'] = $result['user']['first_login'] ?? false;
    
    // For compatibility with existing code
    $_SESSION['type_id'] = $result['user']['id'];
    
    // Log successful login
    error_log("Successful login: User={$result['user']['name']}, Type={$result['type']}");
    
    // Redirect to appropriate page with ID parameter
    $redirectUrl = '../pages/' . $result['profile_page'] . '?id=' . $result['user']['id'];
    header('Location: ' . $redirectUrl);
    exit;
} else {
    $_SESSION['login_error'] = 'CPF ou senha inválidos!';
    header('Location: ../pages/login.php');
    exit;
}