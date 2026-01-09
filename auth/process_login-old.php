<?php
// auth/process_login.php
session_start();
require_once '../backend/classes/database.class.php';

function processUnifiedLogin($cpf, $password)
{
    $response = [
        'success' => false,
        'message' => '',
        'redirect' => '../pages/login.php'
    ];

    try {
        $connection = new Database();
        $conn = $connection->connect();

        // Clean CPF (remove formatting)
        $cleanCpf = preg_replace('/[^0-9]/', '', $cpf);

        // Special case for admin login
        $adminUsers = ['credenciamento', 'gese', 'pedagogico', 'gedth'];
        $isAdminLogin = false;
        $adminUser = '';

        foreach ($adminUsers as $user) {
            if ($cpf === $user || $cleanCpf === $user) {
                $isAdminLogin = true;
                $adminUser = $user;
                break;
            }
        }

        if ($isAdminLogin) {
            $sql = "SELECT id, name, email, password_hash, first_login, enabled 
            FROM user 
            WHERE email = :admin_user AND enabled = 1";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':admin_user', $adminUser);
        } else {
            // Regular CPF login
            $sql = "SELECT id, name, email, password_hash, first_login, enabled 
            FROM user 
            WHERE (cpf = :cpf OR formatted_cpf = :cpf) AND enabled = 1";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':cpf', $cleanCpf);
        }
        
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $response['message'] = 'CPF ou usuário não encontrado!';
            return $response;
        }

        if (!password_verify($password, $user['password_hash'])) {
            $response['message'] = 'CPF ou senha inválidos!';
            return $response;
        }

        // Get user roles from user_roles table
        $roleStmt = $conn->prepare("SELECT role FROM user_roles WHERE user_id = ?");
        $roleStmt->execute([$user['id']]);
        $roles = $roleStmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($roles)) {
            $response['message'] = 'Usuário não possui permissões atribuídas!';
            return $response;
        }

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['first_login'] = $user['first_login'];
        $_SESSION['user_roles'] = $roles;
        $_SESSION['is_admin'] = in_array('admin', $roles);

        // For backward compatibility during migration
        $_SESSION['user_type'] = getPrimaryRole($roles);
        $_SESSION['type_id'] = $user['id'];

        $response['success'] = true;
        $response['message'] = 'Login realizado com sucesso!';
        $response['redirect'] = '../pages/home.php'; // ✅ Always redirect to home.php

        // Update last login timestamp
        $updateStmt = $conn->prepare("UPDATE user SET called_at = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);

        // Log successful login
        error_log("Successful login: User ID={$user['id']}, Name={$user['name']}, Roles=" . implode(',', $roles));
    } catch (PDOException $e) {
        $response['message'] = 'Erro ao processar login. Tente novamente.';
        error_log("Login error: " . $e->getMessage());
    }

    return $response;
}


/**
 * Get primary role for routing decisions
 * Priority order: admin > docente_pos > docente > tecnico > interprete
 */
function getPrimaryRole($roles)
{
    $priorityRoles = ['admin', 'docente_pos', 'docente', 'tecnico', 'interprete'];

    foreach ($priorityRoles as $role) {
        if (in_array($role, $roles)) {
            return $role;
        }
    }

    return !empty($roles) ? $roles[0] : 'user';
}

/**
 * Translate role for backward compatibility with old user_type values
 */
function translateRoleToOldType($role)
{
    $mapping = [
        'admin' => 'admin',
        'docente' => 'teacher',
        'docente_pos' => 'postg_teacher',
        'tecnico' => 'technician',
        'interprete' => 'interpreter'
    ];

    return $mapping[$role] ?? 'user';
}

// Process POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get CPF and password
    $cpf = trim($_POST['cpf'] ?? $_POST['username'] ?? '');
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
