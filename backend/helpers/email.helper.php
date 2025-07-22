<?php
// backend/helpers/email.helper.php

require_once __DIR__ . '/../../vendor/autoload.php'; // Make sure PHPMailer is installed via Composer
require_once __DIR__ . '/../config/email.config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send email using PHPMailer with SMTP
 * 
 * @param string $to Recipient email address
 * @param string $toName Recipient name
 * @param string $subject Email subject
 * @param string $body HTML email body
 * @return bool Success status
 */
function sendEmail($to, $toName, $subject, $body) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = SMTP_AUTH;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // For debugging (disable in production)
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        
        // Recipients
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addAddress($to, $toName);
        $mail->addReplyTo(EMAIL_REPLY_TO, EMAIL_FROM_NAME);
        
        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        // Alternative plain text version
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));
        
        $mail->send();
        
        // Log successful email
        error_log("Email sent successfully to: $to");
        
        return true;
    } catch (Exception $e) {
        // Log the error
        error_log("Email error: {$mail->ErrorInfo}");
        error_log("Failed to send email to: $to");
        
        return false;
    }
}

/**
 * Send password reset email
 * 
 * @param string $email Recipient email
 * @param string $name Recipient name
 * @param string $resetLink Reset link URL
 * @param string $expires Expiration datetime
 * @param string $userType User type (optional)
 * @return bool Success status
 */
function sendPasswordResetEmail($email, $name, $resetLink, $expires, $userType = null) {
    $subject = 'Redefinição de Senha - Sistema de Credenciamento';
    
    $expiresFormatted = date('d/m/Y \à\s H:i', strtotime($expires));
    
    // User type names
    $userTypeNames = [
        'teacher' => 'Docente',
        'postg_teacher' => 'Docente Pós-Graduação',
        'interpreter' => 'Intérprete',
        'technician' => 'Técnico'
    ];
    $userTypeName = isset($userType) ? ($userTypeNames[$userType] ?? 'Usuário') : '';
    
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
            .info-box { background: #e9ecef; padding: 15px; border-radius: 5px; margin: 15px 0; }
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
                ";
    
    // Add user type info if available
    if ($userType) {
        $message .= "
                <div class='info-box'>
                    <strong>Tipo de usuário:</strong> {$userTypeName}<br>
                    <strong>Email:</strong> {$email}
                </div>
        ";
    }
    
    $message .= "
                <p>Para redefinir sua senha, clique no botão abaixo:</p>
                
                <div style='text-align: center;'>
                    <a href='{$resetLink}' class='button'>Redefinir Senha</a>
                </div>
                
                <p>Ou copie e cole o seguinte link no seu navegador:</p>
                <p style='word-break: break-all; background: #e9ecef; padding: 10px; border-radius: 3px; font-size: 12px;'>{$resetLink}</p>
                
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
    
    return sendEmail($email, $name, $subject, $message);
}

/**
 * Fallback to PHP mail() if PHPMailer fails
 * This should only be used as a last resort
 */
function sendEmailFallback($to, $subject, $body) {
    $headers = array(
        'From' => EMAIL_FROM_ADDRESS,
        'Reply-To' => EMAIL_REPLY_TO,
        'MIME-Version' => '1.0',
        'Content-type' => 'text/html; charset=UTF-8',
        'X-Mailer' => 'PHP/' . phpversion()
    );
    
    $headersString = '';
    foreach ($headers as $key => $value) {
        $headersString .= $key . ': ' . $value . "\r\n";
    }
    
    return mail($to, $subject, $body, $headersString);
}