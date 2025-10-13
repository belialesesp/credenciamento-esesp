<?php
// backend/helpers/email.helper.php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/email.config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Test SMTP connection
 * 
 * @return array Connection test result
 */
function testSMTPConnection() {
    $result = [
        'success' => false,
        'message' => '',
        'details' => []
    ];
    
    try {
        $mail = new PHPMailer(true);
        
        // Enable verbose debug output
        $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
        $mail->Debugoutput = function($str, $level) use (&$result) {
            $result['details'][] = "[$level] " . trim($str);
        };
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = SMTP_AUTH;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->Timeout = 10; // Shorter timeout for testing
        
        // Try to connect
        if ($mail->smtpConnect()) {
            $mail->smtpClose();
            $result['success'] = true;
            $result['message'] = 'SMTP connection successful';
        } else {
            $result['message'] = 'Failed to connect to SMTP server';
        }
    } catch (Exception $e) {
        $result['message'] = 'SMTP connection error: ' . $e->getMessage();
    }
    
    return $result;
}

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
        // Enable debugging if needed
        if (defined('EMAIL_DEBUG') && EMAIL_DEBUG) {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer [$level]: " . trim($str));
            };
        }
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = SMTP_AUTH;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        
        // Set encryption based on port
        if (SMTP_PORT == 587) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif (SMTP_PORT == 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            // For other ports, try without encryption or auto-detect
            $mail->SMTPSecure = false;
            $mail->SMTPAutoTLS = false;
        }
        
        $mail->Port = SMTP_PORT;
        
        // Set timeout
        $mail->Timeout = 30;
        
        // Additional settings for better compatibility
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Recipients
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addAddress($to, $toName);
        $mail->addReplyTo(EMAIL_REPLY_TO, EMAIL_FROM_NAME);
        
        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));
        
        error_log("Attempting to send email to: $to via " . SMTP_HOST . ":" . SMTP_PORT);
        
        // Try to send
        $result = $mail->send();
        
        if ($result) {
            error_log("Email sent successfully to: $to");
            return true;
        } else {
            error_log("Email sending returned false for: $to");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("PHPMailer Exception: " . $e->getMessage());
        error_log("PHPMailer Error Info: " . $mail->ErrorInfo);
        
        // Log more details for debugging
        if (defined('EMAIL_DEBUG') && EMAIL_DEBUG) {
            error_log("Debug - Host: " . SMTP_HOST);
            error_log("Debug - Port: " . SMTP_PORT);
            error_log("Debug - Username: " . SMTP_USERNAME);
            error_log("Debug - From: " . EMAIL_FROM_ADDRESS);
            error_log("Debug - To: " . $to);
            
            // Try to provide more specific error information
            if (strpos($e->getMessage(), 'Could not authenticate') !== false) {
                error_log("Authentication failed - check username and password");
            } elseif (strpos($e->getMessage(), 'Connection: opening') !== false || 
                      strpos($e->getMessage(), 'Connection failed') !== false) {
                error_log("Connection failed - check host and port settings");
            } elseif (strpos($e->getMessage(), 'SMTP connect() failed') !== false) {
                error_log("SMTP connection failed - verify server is accessible");
            }
        }
        
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