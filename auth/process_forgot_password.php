<?php
// auth/process_forgot_password.php
session_start();
require_once '../backend/classes/database.class.php';

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
    
    // Check if user exists with this CPF and email
    $stmt = $conn->prepare("
        SELECT id, name, email, user_type 
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
    
    // Send email
    $resetLink = getResetLink($token);
    $emailSent = sendPasswordResetEmail($user['email'], $user['name'], $resetLink, $expires);
    
    if ($emailSent) {
        $_SESSION['forgot_success'] = 'Instruções para redefinir sua senha foram enviadas para seu email.';
        
        // Log the password reset request
        error_log("Password reset requested for user ID: " . $user['id']);
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
    
    return $protocol . '://' . $host . $basePath . '/pages/reset-password.php?token=' . $token;
}

/**
 * Send password reset email
 */
function sendPasswordResetEmail($email, $name, $resetLink, $expires) {
    $subject = 'Redefinição de Senha - Sistema de Credenciamento';
    
    $expiresFormatted = date('d/m/Y \à\s H:i', strtotime($expires));
    
    $message = "
    <html>
    <head>
        <title>Redefinição de Senha</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border: 1px solid #dee2e6; border-radius: 0 0 5px 5px; }
            .button { display: inline-block; padding: 12px 30px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #6c757d; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Redefinição de Senha</h2>
            </div>
            <div class='content'>
                <p>Olá, {$name}!</p>
                
                <p>Recebemos uma solicitação para redefinir a senha da sua conta no Sistema de Credenciamento.</p>
                
                <p>Para redefinir sua senha, clique no botão abaixo:</p>
                
                <div style='text-align: center;'>
                    <a href='{$resetLink}' class='button'>Redefinir Senha</a>
                </div>
                
                <p>Ou copie e cole o seguinte link no seu navegador:</p>
                <p style='word-break: break-all; background: #e9ecef; padding: 10px; border-radius: 3px;'>{$resetLink}</p>
                
                <p><strong>Este link expirará em {$expiresFormatted}.</strong></p>
                
                <p>Se você não solicitou a redefinição de senha, ignore este email. Sua senha permanecerá a mesma.</p>
                
                <div class='footer'>
                    <p>Este é um email automático. Por favor, não responda.</p>
                    <p>Sistema de Credenciamento © " . date('Y') . "</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Headers for HTML email
    $headers = array(
        'From' => 'noreply@' . $_SERVER['HTTP_HOST'],
        'Reply-To' => 'noreply@' . $_SERVER['HTTP_HOST'],
        'MIME-Version' => '1.0',
        'Content-type' => 'text/html; charset=UTF-8',
        'X-Mailer' => 'PHP/' . phpversion()
    );
    
    $headersString = '';
    foreach ($headers as $key => $value) {
        $headersString .= $key . ': ' . $value . "\r\n";
    }
    
    // Try to send email
    $sent = mail($email, $subject, $message, $headersString);
    
    // If mail() fails, you might want to use PHPMailer or another library
    // For now, we'll just return the result
    return $sent;
}