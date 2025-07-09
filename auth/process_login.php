<?php
// auth/process_login.php - CPF Only Version
session_start();
require_once '../backend/classes/database.class.php';

function processUnifiedLogin($cpf, $password) {
    $response = [
        'success' => false,
        'message' => '',
        'redirect' => ''
    ];

    try {
        $connection = new Database();
        $conn = $connection->connect();

        // Clean CPF (remove formatting)
        $cleanCpf = preg_replace('/[^0-9a-zA-Z]/', '', $cpf);
        
        // Special case for admin login
        if ($cpf === 'credenciamento' || $cleanCpf === 'credenciamento') {
            $sql = "SELECT id, name, email, password_hash, user_type, type_id, first_login 
                    FROM user 
                    WHERE email = 'credenciamento'";
            $stmt = $conn->prepare($sql);
        } else {
            // Regular CPF login
            $sql = "SELECT id, name, email, password_hash, user_type, type_id, first_login 
                    FROM user 
                    WHERE cpf = :cpf";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':cpf', $cleanCpf);
        }
        
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['type_id'] = $user['type_id'];
            $_SESSION['first_login'] = $user['first_login'];
            
            $response['success'] = true;
            $response['message'] = 'Login realizado com sucesso!';
            
            // Determine redirect based on user type
            switch($user['user_type']) {
                case 'admin':
                    $response['redirect'] = '../pages/home.php';
                    break;
                case 'teacher':
                    $response['redirect'] = '../pages/docente.php?id=' . $user['type_id'];
                    break;
                case 'postg_teacher':
                    $response['redirect'] = '../pages/docente-pos.php?id=' . $user['type_id'];
                    break;
                case 'technician':
                    if (file_exists('../pages/tecnico.php')) {
                        $response['redirect'] = '../pages/tecnico.php?id=' . $user['type_id'];
                    } else {
                        $response['redirect'] = '../pages/home.php';
                    }
                    break;
                case 'interpreter':
                    if (file_exists('../pages/interprete.php')) {
                        $response['redirect'] = '../pages/interprete.php?id=' . $user['type_id'];
                    } else {
                        $response['redirect'] = '../pages/home.php';
                    }
                    break;
                default:
                    $response['redirect'] = '../pages/home.php';
            }
        } else {
            $response['message'] = 'CPF ou senha inválidos!';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Erro ao processar login: ' . $e->getMessage();
        error_log("Login error: " . $e->getMessage());
    }

    return $response;
}

// Process POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get CPF and password
    $cpf = $_POST['cpf'] ?? $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($cpf) || empty($password)) {
        $_SESSION['login_error'] = 'Por favor, preencha todos os campos.';
        header('Location: ../pages/login.php');
        exit;
    }
    
    $result = processUnifiedLogin($cpf, $password);
    
    if ($result['success']) {
        header('Location: ' . $result['redirect']);
        exit;
    } else {
        $_SESSION['login_error'] = $result['message'];
        header('Location: ../pages/login.php');
        exit;
    }
} else {
    // If not POST, redirect to login
    header('Location: ../pages/login.php');
    exit;
}