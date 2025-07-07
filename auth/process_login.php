<?php
// auth/process_login.php - REPLACE THE EXISTING FILE WITH THIS
session_start();
require_once '../backend/classes/database.class.php';

function processUnifiedLogin($username, $password) {
    $response = [
        'success' => false,
        'message' => '',
        'redirect' => ''
    ];

    try {
        $connection = new Database();
        $conn = $connection->connect();

        // Clean username (remove CPF formatting if present)
        $cleanUsername = preg_replace('/[^0-9a-zA-Z@._-]/', '', $username);
        
        // Check if it's an email or CPF
        $isEmail = filter_var($username, FILTER_VALIDATE_EMAIL);
        
        if ($isEmail || $username === 'credenciamento') {
            // Email login (admin or special case)
            $sql = "SELECT id, name, email, password_hash, user_type, type_id, first_login 
                    FROM user 
                    WHERE email = :username";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':username', $username); // Use original username for 'credenciamento'
        } else {
            // CPF login (docentes, tecnicos, interpretes)
            $sql = "SELECT id, name, email, password_hash, user_type, type_id, first_login 
                    FROM user 
                    WHERE cpf = :username";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':username', $cleanUsername);
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
                    // Check if tecnico.php exists, otherwise go to home
                    if (file_exists('../pages/tecnico.php')) {
                        $response['redirect'] = '../pages/tecnico.php?id=' . $user['type_id'];
                    } else {
                        $response['redirect'] = '../pages/home.php';
                    }
                    break;
                case 'interpreter':
                    // Check if interprete.php exists, otherwise go to home
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
            $response['message'] = 'Usuário ou senha inválidos!';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Erro ao processar login: ' . $e->getMessage();
        error_log("Login error: " . $e->getMessage());
    }

    return $response;
}

// Process POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Support both old (email) and new (username) field names
    $username = $_POST['username'] ?? $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = 'Por favor, preencha todos os campos.';
        header('Location: ../pages/login.php');
        exit;
    }
    
    $result = processUnifiedLogin($username, $password);
    
    if ($result['success']) {
        // Handle remember me
        if (isset($_POST['remember']) && $_POST['remember']) {
            setcookie('remember_username', $username, time() + (86400 * 30), '/');
        }
        
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