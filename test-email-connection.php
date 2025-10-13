<?php
// test-email-connection.php
// Place this file in your project root to test email configuration

require_once 'backend/helpers/email.helper.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Email Configuration Test</h2>";
echo "<pre>";

// Display current configuration
echo "Current Email Configuration:\n";
echo "============================\n";
echo "SMTP Host: " . SMTP_HOST . "\n";
echo "SMTP Port: " . SMTP_PORT . "\n";
echo "SMTP Auth: " . (SMTP_AUTH ? 'Yes' : 'No') . "\n";
echo "SMTP Username: " . SMTP_USERNAME . "\n";
echo "From Address: " . EMAIL_FROM_ADDRESS . "\n";
echo "From Name: " . EMAIL_FROM_NAME . "\n";
echo "Debug Mode: " . (EMAIL_DEBUG ? 'Enabled' : 'Disabled') . "\n\n";

// Test SMTP connection
echo "Testing SMTP Connection:\n";
echo "========================\n";
$connectionTest = testSMTPConnection();

if ($connectionTest['success']) {
    echo "✓ " . $connectionTest['message'] . "\n\n";
} else {
    echo "✗ " . $connectionTest['message'] . "\n\n";
}

// Show connection details if available
if (!empty($connectionTest['details'])) {
    echo "Connection Details:\n";
    echo "==================\n";
    foreach ($connectionTest['details'] as $detail) {
        echo $detail . "\n";
    }
    echo "\n";
}

// Test sending an actual email (optional - uncomment to use)
/*
$testEmail = 'your-test-email@example.com'; // Change this to your email
$testName = 'Test User';
$testSubject = 'Test Email from Credenciamento System';
$testBody = '<h3>Test Email</h3><p>This is a test email from the Credenciamento system.</p>';

echo "Attempting to send test email to: $testEmail\n";
echo "================================\n";

$emailSent = sendEmail($testEmail, $testName, $testSubject, $testBody);

if ($emailSent) {
    echo "✓ Email sent successfully!\n";
} else {
    echo "✗ Failed to send email. Check the error logs for details.\n";
}
*/

echo "</pre>";

// Display recent error logs related to email
echo "<h3>Recent Email-Related Errors (from PHP error log):</h3>";
echo "<pre>";

// Try to read error log (adjust path as needed)
$errorLogPath = ini_get('error_log');
if ($errorLogPath && file_exists($errorLogPath)) {
    $lines = file($errorLogPath);
    $emailErrors = [];
    
    foreach ($lines as $line) {
        if (stripos($line, 'PHPMailer') !== false || 
            stripos($line, 'SMTP') !== false || 
            stripos($line, 'email') !== false ||
            stripos($line, 'mail') !== false) {
            // Get last 20 email-related errors
            $emailErrors[] = $line;
            if (count($emailErrors) > 20) {
                array_shift($emailErrors);
            }
        }
    }
    
    if (!empty($emailErrors)) {
        foreach ($emailErrors as $error) {
            echo htmlspecialchars($error);
        }
    } else {
        echo "No recent email-related errors found in log.\n";
    }
} else {
    echo "Could not access error log file.\n";
}

echo "</pre>";

// Common issues and solutions
echo "<h3>Common Email Issues and Solutions:</h3>";
echo "<ul>";
echo "<li><strong>Authentication failed:</strong> Check SMTP_USERNAME and SMTP_PASSWORD in email.config.php</li>";
echo "<li><strong>Connection failed:</strong> Verify SMTP_HOST and SMTP_PORT are correct</li>";
echo "<li><strong>SSL/TLS errors:</strong> Try different ports (587 for TLS, 465 for SSL, 25 for plain)</li>";
echo "<li><strong>Timeout errors:</strong> Check firewall settings and if the SMTP server is accessible</li>";
echo "<li><strong>Invalid address:</strong> Verify EMAIL_FROM_ADDRESS is a valid email</li>";
echo "</ul>";
?>