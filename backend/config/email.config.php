<?php
// backend/config/email.config.php

/**
 * Email configuration for the password reset system
 * 
 * For production, consider using PHPMailer or another robust email library
 * instead of the basic mail() function
 */

// Email settings
define('EMAIL_FROM_ADDRESS', 'noreply@yourdomain.com');
define('EMAIL_FROM_NAME', 'Sistema de Credenciamento');
define('EMAIL_REPLY_TO', 'support@yourdomain.com');

// SMTP settings (if using PHPMailer)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // 'tls' or 'ssl'
define('SMTP_AUTH', true);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');

// Reset token settings
define('RESET_TOKEN_EXPIRY_HOURS', 1); // How long reset tokens are valid
define('RESET_TOKEN_LENGTH', 32); // Length of reset token in bytes

/**
 * Example PHPMailer implementation
 * Uncomment and modify if you want to use PHPMailer instead of mail()
 */
/*
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendEmailWithPHPMailer($to, $subject, $body) {
    require_once 'vendor/autoload.php'; // Assuming PHPMailer installed via Composer
    
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
        
        // Recipients
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(EMAIL_REPLY_TO);
        
        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: {$mail->ErrorInfo}");
        return false;
    }
}
*/