<?php
// auth/process_forgot_password.php - Works with unified user table
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
    
    // Check if user exists in the unified user table
    $stmt = $conn->prepare("
        SELECT u.id, u.name, u.email, u.cpf,
               GROUP_CONCAT(ur.role) as roles
        FROM user u
        LEFT JOIN user_roles ur ON u.id = ur.user_id
        WHERE u.cpf = :cpf 
        AND u.email = :email
        GROUP BY u.id
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
        error_log("Password reset attempted for non-existent user: CPF=$cpf");
        
        header('Location: ../pages/forgot-password.php');
        exit;
    }
    
    // Generate secure token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Update user with reset token
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
    
    // Determine user type for email (backward compatibility)
    $roles = explode(',', $user['roles']);
    $userType = determineUserType($roles);
    
    // Send email using the helper function
    $resetLink = getResetLink($token);
    $emailSent = sendPasswordResetEmail($user['email'], $user['name'], $resetLink, $expires, $userType);
    
    if ($emailSent) {
        $_SESSION['forgot_success'] = 'Instruções para redefinir sua senha foram enviadas para seu email.';
        
        // Log the password reset request
        error_log("Password reset requested: UserID=" . $user['id'] . ", Roles=" . $user['roles']);
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
 * Generate the password reset link
 */
function getResetLink($token) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $basePath = dirname(dirname($_SERVER['REQUEST_URI']));
    
    // No need to pass type anymore since we're using unified table
    return $protocol . '://' . $host . $basePath . '/pages/reset-password.php?token=' . $token;
}

/**
 * Determine user type from roles (for backward compatibility with email templates)
 */
function determineUserType($roles) {
    // Priority order
    if (in_array('admin', $roles)) return 'admin';
    if (in_array('docente_pos', $roles)) return 'postg_teacher';
    if (in_array('docente', $roles)) return 'teacher';
    if (in_array('tecnico', $roles)) return 'technician';
    if (in_array('interprete', $roles)) return 'interpreter';
    return 'user';
}