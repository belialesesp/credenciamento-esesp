<?php
// auth/process_password_change.php
session_start();
require_once '../backend/classes/auth.class.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_table'])) {
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/home.php');
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

if ($newPassword !== $confirmPassword) {
    $_SESSION['password_error'] = 'As senhas não coincidem.';
    header('Location: ' . $returnUrl);
    exit;
}

// Validate password strength
if (!AuthHelper::validatePasswordStrength($newPassword)) {
    $_SESSION['password_error'] = 'A senha deve ter pelo menos 8 caracteres, incluindo maiúsculas, minúsculas, números e símbolos (@$!%*?&).';
    header('Location: ' . $returnUrl);
    exit;
}

try {
    $auth = new AuthHelper();
    
    // Get current user data
    $connection = new Database();
    $conn = $connection->connect();
    
    $table = $_SESSION['user_table'];
    $userId = $_SESSION['user_id'];
    
    // Get current password hash
    $stmt = $conn->prepare("SELECT password_hash, cpf FROM $table WHERE id = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['password_error'] = 'Usuário não encontrado.';
        header('Location: ' . $returnUrl);
        exit;
    }
    
    // Check current password
    $isValid = false;
    
    if (empty($user['password_hash'])) {
        // First login - password should be CPF
        $cleanCpf = preg_replace('/\D/', '', $user['cpf']);
        $isValid = ($currentPassword === $cleanCpf);
    } else {
        // Verify against stored hash
        $isValid = password_verify($currentPassword, $user['password_hash']);
    }
    
    if (!$isValid) {
        $_SESSION['password_error'] = 'Senha atual incorreta.';
        header('Location: ' . $returnUrl);
        exit;
    }
    
    // Update password
    if ($auth->updatePassword($table, $userId, $newPassword)) {
        $_SESSION['first_login'] = false;
        $_SESSION['password_success'] = 'Senha alterada com sucesso!';
        
        // Log password change
        error_log("Password changed for user ID: $userId in table: $table");
        
        // If this was a first login, redirect to profile without the change password form
        if (isset($_GET['action']) && $_GET['action'] === 'change-password') {
            $returnUrl = strtok($returnUrl, '?') . '?id=' . $userId;
        }
    } else {
        $_SESSION['password_error'] = 'Erro ao alterar senha. Tente novamente.';
    }
    
} catch (Exception $e) {
    $_SESSION['password_error'] = 'Erro no sistema. Tente novamente mais tarde.';
    error_log("Password change error: " . $e->getMessage());
}

header('Location: ' . $returnUrl);
exit;