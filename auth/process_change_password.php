<?php
// auth/process_change_password.php
session_start();
require_once '../backend/classes/database.class.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Get return URL from referrer or default to home
    $returnUrl = $_SERVER['HTTP_REFERER'] ?? '../pages/home.php';
    
    // Validate inputs
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $_SESSION['password_error'] = 'Todos os campos são obrigatórios.';
        header('Location: ' . $returnUrl);
        exit;
    }
    
    if ($newPassword !== $confirmPassword) {
        $_SESSION['password_error'] = 'As senhas não coincidem.';
        header('Location: ' . $returnUrl);
        exit;
    }
    
    // Validate password strength
    if (!validatePasswordStrength($newPassword)) {
        $_SESSION['password_error'] = 'A senha deve ter pelo menos 8 caracteres, incluindo maiúsculas, minúsculas, números e símbolos.';
        header('Location: ' . $returnUrl);
        exit;
    }
    
    try {
        $connection = new Database();
        $conn = $connection->connect();
        
        // Get current user data
        $stmt = $conn->prepare("SELECT password_hash FROM user WHERE id = :id");
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();
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
        
        // Update password
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("UPDATE user SET password_hash = :password, first_login = FALSE WHERE id = :id");
        $updateStmt->bindParam(':password', $newPasswordHash);
        $updateStmt->bindParam(':id', $_SESSION['user_id']);
        
        if ($updateStmt->execute()) {
            $_SESSION['first_login'] = false;
            $_SESSION['password_message'] = 'Senha alterada com sucesso!';
            
            // Log password change
            error_log("Password changed for user ID: " . $_SESSION['user_id']);
        } else {
            $_SESSION['password_error'] = 'Erro ao alterar senha. Tente novamente.';
        }
        
    } catch (PDOException $e) {
        $_SESSION['password_error'] = 'Erro no sistema. Tente novamente mais tarde.';
        error_log("Password change error: " . $e->getMessage());
    }
    
    header('Location: ' . $returnUrl);
    exit;
}

// Redirect if not POST
header('Location: ../pages/home.php');
exit;

/**
 * Validate password strength
 * Requirements:
 * - At least 8 characters
 * - At least one uppercase letter
 * - At least one lowercase letter
 * - At least one number
 * - At least one special character
 */
function validatePasswordStrength($password) {
    if (strlen($password) < 8) {
        return false;
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    if (!preg_match('/[@$!%*?&]/', $password)) {
        return false;
    }
    
    return true;
}