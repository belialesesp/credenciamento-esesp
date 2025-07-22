<?php
// auth/process_change_password.php
// Updated to work with unified user table
session_start();
require_once '../backend/classes/database.class.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../pages/login.php');
    exit;
}

// Redirect if not POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $returnUrl = $_SERVER['HTTP_REFERER'] ?? '../pages/home.php';
    header('Location: ' . $returnUrl);
    exit;
}

// Get form data
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$returnUrl = $_POST['return_url'] ?? '../pages/home.php';

// Validate inputs
if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    $_SESSION['password_error'] = 'Todos os campos são obrigatórios.';
    header('Location: ' . $returnUrl);
    exit;
}

// Check if new passwords match
if ($newPassword !== $confirmPassword) {
    $_SESSION['password_error'] = 'As novas senhas não coincidem.';
    header('Location: ' . $returnUrl);
    exit;
}

// Validate password strength
if (!validatePasswordStrength($newPassword)) {
    $_SESSION['password_error'] = 'A senha deve ter pelo menos 8 caracteres, incluindo maiúsculas, minúsculas, números e caracteres especiais.';
    header('Location: ' . $returnUrl);
    exit;
}

// Check if new password is different from current
if ($currentPassword === $newPassword) {
    $_SESSION['password_error'] = 'A nova senha deve ser diferente da senha atual.';
    header('Location: ' . $returnUrl);
    exit;
}

try {
    $connection = new Database();
    $conn = $connection->connect();
    
    $userId = $_SESSION['user_id'];
    
    // Get current user from unified table
    $stmt = $conn->prepare("
        SELECT password_hash 
        FROM user 
        WHERE id = :user_id
    ");
    $stmt->execute([':user_id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['password_error'] = 'Usuário não encontrado.';
        header('Location: ' . $returnUrl);
        exit;
    }
    
    // Verify current password
    if (!password_verify($currentPassword, $user['password_hash'])) {
        $_SESSION['password_error'] = 'Senha atual incorreta.';
        header('Location: ' . $returnUrl);
        exit;
    }
    
    // Update password in unified user table
    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $updateStmt = $conn->prepare("
        UPDATE user 
        SET password_hash = :password_hash,
            first_login = FALSE
        WHERE id = :user_id
    ");
    
    $updateStmt->execute([
        ':password_hash' => $newPasswordHash,
        ':user_id' => $userId
    ]);
    
    // Update session to reflect password change
    $_SESSION['first_login'] = false;
    $_SESSION['password_success'] = 'Senha alterada com sucesso!';
    
    // Log password change
    error_log("Password changed for user ID: $userId");
    
    // If this was a first login password change, redirect to profile
    if (isset($_GET['first_login']) && $_GET['first_login'] === 'true') {
        // Remove the password change requirement from the URL
        $returnUrl = strtok($returnUrl, '?');
        if (isset($_SESSION['type_id'])) {
            $returnUrl .= '?id=' . $_SESSION['type_id'];
        }
    }
    
    header('Location: ' . $returnUrl);
    exit;
    
} catch (PDOException $e) {
    $_SESSION['password_error'] = 'Erro ao alterar senha. Tente novamente mais tarde.';
    error_log("Password change error: " . $e->getMessage());
    header('Location: ' . $returnUrl);
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