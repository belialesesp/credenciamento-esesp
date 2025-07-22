<?php
// backend/cron/cleanup_reset_tokens.php
// Run this script periodically (e.g., daily) to clean up expired reset tokens

require_once '../classes/database.class.php';

function cleanupExpiredTokens() {
    $connection = new Database();
    $conn = $connection->connect();
    
    $tables = [
        'user',
        'teacher',
        'postg_teacher',
        'interpreter',
        'technician'
    ];
    
    $totalCleaned = 0;
    
    echo "Starting cleanup of expired password reset tokens...\n";
    echo "Current time: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        try {
            // Clean up expired tokens
            $stmt = $conn->prepare("
                UPDATE $table 
                SET password_reset_token = NULL,
                    password_reset_expires = NULL
                WHERE password_reset_expires < NOW()
                AND password_reset_token IS NOT NULL
            ");
            
            $stmt->execute();
            $affectedRows = $stmt->rowCount();
            
            if ($affectedRows > 0) {
                echo "✓ Table '$table': Cleaned $affectedRows expired tokens\n";
                $totalCleaned += $affectedRows;
            } else {
                echo "- Table '$table': No expired tokens found\n";
            }
            
        } catch (PDOException $e) {
            echo "✗ Error cleaning table '$table': " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nCleanup complete. Total tokens cleaned: $totalCleaned\n";
    
    // Log the cleanup
    error_log("Password reset token cleanup completed. Cleaned $totalCleaned tokens.");
}

// Run the cleanup
cleanupExpiredTokens();

// Optional: You can also add this to a cron job
// Example crontab entry to run daily at 2 AM:
// 0 2 * * * /usr/bin/php /path/to/your/project/backend/cron/cleanup_reset_tokens.php