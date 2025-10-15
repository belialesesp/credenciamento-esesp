<?php
// backend/config/email.config.example.php
// EXAMPLE FILE - Copy this to email.config.php and fill in your actual credentials

/**
 * Email configuration for the system
 * 
 * IMPORTANT: Copy this file to email.config.php and update with your actual credentials
 */

// Email settings
define('EMAIL_FROM_ADDRESS', 'your-email@example.com');
define('EMAIL_FROM_NAME', 'Sistema de Credenciamento da ESESP');
define('EMAIL_REPLY_TO', 'your-email@example.com');

// SMTP settings
define('SMTP_HOST', 'your-smtp-server.com');
define('SMTP_PORT', 587); 
define('SMTP_SECURE', 'tls');
define('SMTP_AUTH', true);
define('SMTP_USERNAME', 'your-username@example.com'); 
define('SMTP_PASSWORD', 'your-password-here'); 

// Reset token settings
define('RESET_TOKEN_EXPIRY_HOURS', 1);
define('RESET_TOKEN_LENGTH', 32);

// Debug mode (set to false in production)
define('EMAIL_DEBUG', true);