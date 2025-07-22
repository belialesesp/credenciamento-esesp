<?php
// auth/process_forgot_password.php
// Updated to work with unified user table
session_start();
require_once '../backend/classes/database.class.php';
require_once '../backend/helpers/email.helper.php';

// Redirect if not POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/forgot-password.php');
    exit;
}

// Get and clean input
$cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$email = trim($_POST['email'] ?? '');

// Validate inputs
if (empty($cpf) || empty($email)) {
    $_SESSION['forgot_error'] = 'Por favor, preencha todos os campos.';
    header('Location: ../pages/forgot-password.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['forgot_error'] = 'Por favor, insira um email válido.';
    header('Location: ../pages/forgot-password.php');
    exit;
}

try {
    $connection = new Database();
    $conn = $connection->connect();
    
    // Search in the unified user table
    $stmt = $conn->prepare("
        SELECT id, name, email, cpf, user_type 
        FROM user 
        WHERE cpf = :cpf AND email = :email
        LIMIT 1
    ");
    
    $stmt->bindParam(':cpf', $cpf);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // For security, don't reveal if user exists or not
        $_SESSION['forgot_success'] = 'Se um usuário foi encontrado com essas informações, instruções foram enviadas para o email cadastrado.';
        header('Location: ../pages/forgot-password.php');
        exit;
    }
    
    // Generate secure token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Update user with reset token in the unified user table
    $updateStmt = $conn->prepare("
        UPDATE user 
        SET password_reset_token = :token, 
            password_reset_expires = :expires 
        WHERE id = :id
    ");
    
    $updateStmt->bindParam(':token', $token);
    $updateStmt->bindParam(':expires', $expires);
    $updateStmt->bindParam(':id', $user['id']);
    $updateStmt->execute();
    
    // Send email using the helper function
    $resetLink = getResetLink($token);
    $emailSent = sendPasswordResetEmail($user['email'], $user['name'], $resetLink, $expires, $user['user_type']);
    
    if ($emailSent) {
        $_SESSION['forgot_success'] = 'Instruções para redefinir sua senha foram enviadas para seu email.';
        
        // Log the password reset request
        error_log("Password reset requested for user ID: " . $user['id'] . " (type: " . $user['user_type'] . ")");
    } else {
        $_SESSION['forgot_error'] = 'Erro ao enviar email. Por favor, tente novamente mais tarde.';
    }
    
} catch (PDOException $e) {
    $_SESSION['forgot_error'] = 'Erro no sistema. Por favor, tente novamente mais tarde.';
    error_log("Forgot password error: " . $e->getMessage());
}

header('Location: ../pages/forgot-password.php');
exit;

/**
 * Generate the password reset link (no longer needs user type in URL)
 */
function getResetLink($token) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $basePath = dirname(dirname($_SERVER['REQUEST_URI']));
    
    return $protocol . '://' . $host . $basePath . '/pages/reset-password.php?token=' . $token;
}