<?php
// auth/process_forgot_password_multi.php
session_start();
require_once '../backend/classes/database.class.php';
require_once '../backend/helpers/email.helper.php'; // Include the new email helper

// Redirect if not POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/forgot-password.php');
    exit;
}

// Get and clean input
$userType = $_POST['user_type'] ?? '';
$cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$email = trim($_POST['email'] ?? '');

// Validate inputs
if (empty($userType) || empty($cpf) || empty($email)) {
    $_SESSION['forgot_error'] = 'Por favor, preencha todos os campos.';
    header('Location: ../pages/forgot-password.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['forgot_error'] = 'Por favor, insira um email válido.';
    header('Location: ../pages/forgot-password.php');
    exit;
}

// Map user type to table name
$tableMap = [
    'teacher' => 'teacher',
    'postg_teacher' => 'postg_teacher',
    'interpreter' => 'interpreter',
    'technician' => 'technician'
];

if (!isset($tableMap[$userType])) {
    $_SESSION['forgot_error'] = 'Tipo de usuário inválido.';
    header('Location: ../pages/forgot-password.php');
    exit;
}

$tableName = $tableMap[$userType];

try {
    $connection = new Database();
    $conn = $connection->connect();
    
    // Check if user exists in the specified table
    $stmt = $conn->prepare("
        SELECT id, name, email, cpf 
        FROM $tableName 
        WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = :cpf 
        AND email = :email
        LIMIT 1
    ");
    
    $stmt->bindParam(':cpf', $cpf);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // For security, always show success message
        $_SESSION['forgot_success'] = 'Se um usuário foi encontrado com essas informações, instruções foram enviadas para o email cadastrado.';
        
        // Log the attempt
        error_log("Password reset attempted for non-existent user: Table=$tableName, CPF=$cpf");
        
        header('Location: ../pages/forgot-password.php');
        exit;
    }
    
    // Generate secure token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Update user with reset token
    $updateStmt = $conn->prepare("
        UPDATE $tableName 
        SET password_reset_token = :token, 
            password_reset_expires = :expires 
        WHERE id = :id
    ");
    
    $updateStmt->bindParam(':token', $token);
    $updateStmt->bindParam(':expires', $expires);
    $updateStmt->bindParam(':id', $user['id']);
    $updateStmt->execute();
    
    // Send email using the new helper function
    $resetLink = getResetLink($token, $userType);
    $emailSent = sendPasswordResetEmail($user['email'], $user['name'], $resetLink, $expires, $userType);
    
    if ($emailSent) {
        $_SESSION['forgot_success'] = 'Instruções para redefinir sua senha foram enviadas para seu email.';
        
        // Log the password reset request
        error_log("Password reset requested: Table=$tableName, UserID=" . $user['id']);
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
 * Generate the password reset link with user type
 */
function getResetLink($token, $userType) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $basePath = dirname(dirname($_SERVER['REQUEST_URI']));
    
    return $protocol . '://' . $host . $basePath . '/pages/reset-password.php?token=' . $token . '&type=' . $userType;
}