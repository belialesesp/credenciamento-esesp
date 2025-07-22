<?php
// backend/config/email.config.php

/**
 * Email configuration for the password reset system
 * 
 * IMPORTANT: Update these settings with your actual email credentials
 */

// Email settings
define('EMAIL_FROM_ADDRESS', 'credenciamento@esesp.es.gov.br'); // Change to your domain
define('EMAIL_FROM_NAME', 'Sistema de Credenciamento da ESESP');
define('EMAIL_REPLY_TO', 'credenciamento@esesp.es.gov.br'); // Change to your support email

// SMTP settings - Choose one of the configurations below:

// === OPTION 1: Gmail SMTP (Recommended for testing) ===
// 1. Enable 2-factor authentication on your Gmail account
// 2. Generate an App Password: https://myaccount.google.com/apppasswords
// 3. Use the app password below, NOT your regular Gmail password
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_AUTH', true);
define('SMTP_USERNAME', 'ead.esesp@gmail.com'); // Your Gmail address
define('SMTP_PASSWORD', 'aolr hmhs xwpn ljqy'); // Your Gmail App Password (NOT regular password)

// === OPTION 2: Outlook/Hotmail SMTP ===
// define('SMTP_HOST', 'smtp-mail.outlook.com');
// define('SMTP_PORT', 587);
// define('SMTP_SECURE', 'tls');
// define('SMTP_AUTH', true);
// define('SMTP_USERNAME', 'your-email@outlook.com');
// define('SMTP_PASSWORD', 'your-password');

// === OPTION 3: Local SMTP (Hosting Provider) ===
// define('SMTP_HOST', 'localhost');
// define('SMTP_PORT', 25);
// define('SMTP_SECURE', false); // or 'tls'/'ssl' if supported
// define('SMTP_AUTH', false); // Usually false for localhost
// define('SMTP_USERNAME', '');
// define('SMTP_PASSWORD', '');

// === OPTION 4: SendGrid SMTP ===
// define('SMTP_HOST', 'smtp.sendgrid.net');
// define('SMTP_PORT', 587);
// define('SMTP_SECURE', 'tls');
// define('SMTP_AUTH', true);
// define('SMTP_USERNAME', 'apikey'); // Always 'apikey' for SendGrid
// define('SMTP_PASSWORD', 'your-sendgrid-api-key');

// === OPTION 5: Amazon SES SMTP ===
// define('SMTP_HOST', 'email-smtp.us-east-1.amazonaws.com'); // Change region as needed
// define('SMTP_PORT', 587);
// define('SMTP_SECURE', 'tls');
// define('SMTP_AUTH', true);
// define('SMTP_USERNAME', 'your-ses-smtp-username');
// define('SMTP_PASSWORD', 'your-ses-smtp-password');

// Reset token settings
define('RESET_TOKEN_EXPIRY_HOURS', 1); // How long reset tokens are valid
define('RESET_TOKEN_LENGTH', 32); // Length of reset token in bytes

// Debug mode (set to false in production)
define('EMAIL_DEBUG', true); // Set to true to see detailed SMTP logs