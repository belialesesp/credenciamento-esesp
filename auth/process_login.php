<?php
// auth/process_login.php - Updated to work with roles
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
            // Get user roles
            $roleStmt = $conn->prepare("SELECT role FROM user_roles WHERE user_id = ?");
            $roleStmt->execute([$user['id']]);
            $roles = $roleStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_type'] = $user['user_type']; // Keep for backward compatibility
            $_SESSION['type_id'] = $user['type_id'] ?? $user['id']; // For backward compatibility
            $_SESSION['first_login'] = $user['first_login'];
            $_SESSION['user_roles'] = $roles; // NEW: Store user roles
            
            $response['success'] = true;
            $response['message'] = 'Login realizado com sucesso!';
            
            // Determine redirect based on primary role
            $primaryRole = getPrimaryRole($roles);
            
            switch($primaryRole) {
                case 'admin':
                    $response['redirect'] = '../pages/home.php';
                    break;
                case 'docente':
                    $response['redirect'] = '../pages/docente.php?id=' . $user['id'];
                    break;
                case 'docente_pos':
                    $response['redirect'] = '../pages/docente-pos.php?id=' . $user['id'];
                    break;
                case 'tecnico':
                    if (file_exists('../pages/tecnico.php')) {
                        $response['redirect'] = '../pages/tecnico.php?id=' . $user['id'];
                    } else {
                        $response['redirect'] = '../pages/home.php';
                    }
                    break;
                case 'interprete':
                    if (file_exists('../pages/interprete.php')) {
                        $response['redirect'] = '../pages/interprete.php?id=' . $user['id'];
                    } else {
                        $response['redirect'] = '../pages/home.php';
                    }
                    break;
                default:
                    $response['redirect'] = '../pages/home.php';
            }
            
            // Log successful login
            error_log("Successful login: User={$user['name']}, Roles=" . implode(',', $roles));
            
        } else {
            $response['message'] = 'CPF ou senha inválidos!';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Erro ao processar login: ' . $e->getMessage();
        error_log("Login error: " . $e->getMessage());
    }

    return $response;
}

/**
 * Get primary role for routing decisions
 */
function getPrimaryRole($roles) {
    // Priority order: admin > docente_pos > docente > tecnico > interprete
    if (in_array('admin', $roles)) return 'admin';
    if (in_array('docente_pos', $roles)) return 'docente_pos';
    if (in_array('docente', $roles)) return 'docente';
    if (in_array('tecnico', $roles)) return 'tecnico';
    if (in_array('interprete', $roles)) return 'interprete';
    return null;
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