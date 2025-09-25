<?php
// backend/config/email.config.php

/**
 * Email configuration for the password reset system
 * 
 * IMPORTANT: Update these settings with your actual email credentials
 */

// Email settings
define('EMAIL_FROM_ADDRESS', 'credenciamento@esesp.es.gov.br');
define('EMAIL_FROM_NAME', 'Sistema de Credenciamento da ESESP');
define('EMAIL_REPLY_TO', 'credenciamento@esesp.es.gov.br');

// SMTP settings - CORRECTED PORT!
define('SMTP_HOST', 'esesp.correio.es.gov.br');
define('SMTP_PORT', 587); // Changed from 143 to 587 - the correct SMTP port
define('SMTP_SECURE', 'tls'); // Port 587 uses TLS
define('SMTP_AUTH', true);
define('SMTP_USERNAME', 'credenciamento@esesp.es.gov.br'); 
define('SMTP_PASSWORD', 'esesp@EAD!2026'); 

// Reset token settings
define('RESET_TOKEN_EXPIRY_HOURS', 1);
define('RESET_TOKEN_LENGTH', 32);

// Debug mode (set to false in production)
define('EMAIL_DEBUG', true);