<?php
// auth/process_reset_password.php
session_start();
require_once '../backend/classes/database.class.php';

// Redirect if not POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/forgot-password.php');
    exit;
}

// Get form data
$token = $_POST['token'] ?? '';
$userType = $_POST['type'] ?? '';
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validate inputs
if (empty($token) || empty($password) || empty($confirmPassword)) {
    $_SESSION['reset_error'] = 'Todos os campos são obrigatórios.';
    header('Location: ../pages/reset-password.php?token=' . urlencode($token) . '&type=' . urlencode($userType));
    exit;
}

// Validate token format
if (!ctype_xdigit($token) || strlen($token) !== 64) {
    $_SESSION['reset_error'] = 'Token inválido.';
    header('Location: ../pages/forgot-password.php');
    exit;
}

// Check if passwords match
if ($password !== $confirmPassword) {
    $_SESSION['reset_error'] = 'As senhas não coincidem.';
    header('Location: ../pages/reset-password.php?token=' . urlencode($token) . '&type=' . urlencode($userType));
    exit;
}

// Validate password strength
if (!validatePasswordStrength($password)) {
    $_SESSION['reset_error'] = 'A senha não atende aos requisitos de segurança.';
    header('Location: ../pages/reset-password.php?token=' . urlencode($token) . '&type=' . urlencode($userType));
    exit;
}

try {
    $connection = new Database();
    $conn = $connection->connect();
    
    $user = null;
    $tableName = '';
    
    if (empty($userType)) {
        // Check main user table
        $stmt = $conn->prepare("
            SELECT id, name, email 
            FROM user 
            WHERE password_reset_token = :token 
            AND password_reset_expires > NOW()
            LIMIT 1
        ");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $tableName = 'user';
    } else {
        // Check specific user type table
        $tableMap = [
            'teacher' => 'teacher',
            'postg_teacher' => 'postg_teacher',
            'interpreter' => 'interpreter',
            'technician' => 'technician'
        ];
        
        if (isset($tableMap[$userType])) {
            $tableName = $tableMap[$userType];
            $stmt = $conn->prepare("
                SELECT id, name, email 
                FROM $tableName 
                WHERE password_reset_token = :token 
                AND password_reset_expires > NOW()
                LIMIT 1
            ");
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    
    if (!$user) {
        $_SESSION['reset_error'] = 'Link de redefinição inválido ou expirado.';
        header('Location: ../pages/forgot-password.php');
        exit;
    }
    
    // Update password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    $updateStmt = $conn->prepare("
        UPDATE $tableName 
        SET password_hash = :password_hash,
            password_reset_token = NULL,
            password_reset_expires = NULL
        WHERE id = :id
    ");
    
    $updateStmt->bindParam(':password_hash', $passwordHash);
    $updateStmt->bindParam(':id', $user['id']);
    $updateStmt->execute();
    
    // If this is the main user table, mark first login as false
    if ($tableName === 'user') {
        $firstLoginStmt = $conn->prepare("
            UPDATE user 
            SET first_login = FALSE 
            WHERE id = :id
        ");
        $firstLoginStmt->bindParam(':id', $user['id']);
        $firstLoginStmt->execute();
    }
    
    // Log successful password reset
    error_log("Password reset successful for user ID: " . $user['id'] . " in table: $tableName");
    
    // Set success message and redirect to login
    $_SESSION['login_message'] = 'Senha redefinida com sucesso! Faça login com sua nova senha.';
    header('Location: ../pages/login.php');
    exit;
    
} catch (PDOException $e) {
    $_SESSION['reset_error'] = 'Erro ao redefinir senha. Por favor, tente novamente.';
    error_log("Password reset error: " . $e->getMessage());
    header('Location: ../pages/reset-password.php?token=' . urlencode($token) . '&type=' . urlencode($userType));
    exit;
}

/**
 * Validate password strength
 */
function validatePasswordStrength($password) {
    // At least 8 characters
    if (strlen($password) < 8) {
        return false;
    }
    
    // At least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    // At least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    // At least one number
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    // At least one special character
    if (!preg_match('/[@$!%*?&]/', $password)) {
        return false;
    }
    
    return true;
}