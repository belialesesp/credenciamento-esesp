<?php
// auth/process_reset_password.php
// Updated to work with unified user table
session_start();
require_once '../backend/classes/database.class.php';

// Redirect if not POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/forgot-password.php');
    exit;
}

// Get form data
$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validate inputs
if (empty($token) || empty($password) || empty($confirmPassword)) {
    $_SESSION['reset_error'] = 'Todos os campos são obrigatórios.';
    header('Location: ../pages/reset-password.php?token=' . urlencode($token));
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
    header('Location: ../pages/reset-password.php?token=' . urlencode($token));
    exit;
}

// Validate password strength
if (!validatePasswordStrength($password)) {
    $_SESSION['reset_error'] = 'A senha deve ter pelo menos 8 caracteres, incluindo maiúsculas, minúsculas, números e caracteres especiais.';
    header('Location: ../pages/reset-password.php?token=' . urlencode($token));
    exit;
}

try {
    $connection = new Database();
    $conn = $connection->connect();
    
    // Find user by token in the unified user table
    $stmt = $conn->prepare("
        SELECT id, name, email, user_type 
        FROM user 
        WHERE password_reset_token = :token 
        AND password_reset_expires > NOW()
        LIMIT 1
    ");
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['reset_error'] = 'Link de redefinição inválido ou expirado.';
        header('Location: ../pages/forgot-password.php');
        exit;
    }
    
    // Update password in the unified user table
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    $updateStmt = $conn->prepare("
        UPDATE user 
        SET password_hash = :password_hash,
            password_reset_token = NULL,
            password_reset_expires = NULL,
            first_login = FALSE
        WHERE id = :id
    ");
    
    $updateStmt->bindParam(':password_hash', $passwordHash);
    $updateStmt->bindParam(':id', $user['id']);
    $updateStmt->execute();
    
    // Log successful password reset
    error_log("Password reset successful for user ID: " . $user['id'] . " (type: " . $user['user_type'] . ")");
    
    // Set success message and redirect to login
    $_SESSION['login_message'] = 'Senha redefinida com sucesso! Faça login com sua nova senha.';
    header('Location: ../pages/login.php');
    exit;
    
} catch (PDOException $e) {
    $_SESSION['reset_error'] = 'Erro ao redefinir senha. Por favor, tente novamente.';
    error_log("Password reset error: " . $e->getMessage());
    header('Location: ../pages/reset-password.php?token=' . urlencode($token));
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
    if (!preg_match('/[@$!%*?&#]/', $password)) {
        return false;
    }
    
    return true;
}