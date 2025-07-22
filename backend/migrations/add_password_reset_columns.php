<?php
// backend/migrations/add_password_reset_columns.php
// Run this script to add password reset columns to all user tables

require_once '../classes/database.class.php';

function addPasswordResetColumns() {
    $connection = new Database();
    $conn = $connection->connect();
    
    $tables = [
        'user',
        'teacher',
        'postg_teacher',
        'interpreter',
        'technician'
    ];
    
    echo "<h2>Adding Password Reset Columns</h2>";
    echo "<pre>";
    echo "Starting migration...\n\n";
    
    foreach ($tables as $table) {
        echo "Processing table: $table\n";
        
        try {
            // Check if columns already exist
            $checkStmt = $conn->prepare("
                SELECT COLUMN_NAME 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = :table 
                AND COLUMN_NAME IN ('password_reset_token', 'password_reset_expires')
            ");
            $checkStmt->bindParam(':table', $table);
            $checkStmt->execute();
            $existingColumns = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Add password_reset_token if it doesn't exist
            if (!in_array('password_reset_token', $existingColumns)) {
                $conn->exec("
                    ALTER TABLE $table 
                    ADD COLUMN password_reset_token VARCHAR(100) DEFAULT NULL
                ");
                echo "✓ Added password_reset_token column\n";
            } else {
                echo "- password_reset_token column already exists\n";
            }
            
            // Add password_reset_expires if it doesn't exist
            if (!in_array('password_reset_expires', $existingColumns)) {
                $conn->exec("
                    ALTER TABLE $table 
                    ADD COLUMN password_reset_expires DATETIME DEFAULT NULL
                ");
                echo "✓ Added password_reset_expires column\n";
            } else {
                echo "- password_reset_expires column already exists\n";
            }
            
            // Add index on password_reset_token for better performance
            try {
                $conn->exec("
                    CREATE INDEX idx_password_reset_token 
                    ON $table (password_reset_token)
                ");
                echo "✓ Added index on password_reset_token\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    echo "- Index on password_reset_token already exists\n";
                } else {
                    throw $e;
                }
            }
            
            echo "✅ Table $table processed successfully\n\n";
            
        } catch (PDOException $e) {
            echo "❌ Error processing table $table: " . $e->getMessage() . "\n\n";
        }
    }
    
    echo "</pre>";
    echo "<h3>Migration Complete!</h3>";
    echo "<p>You can now use the password reset feature.</p>";
    echo "<p><strong>Important:</strong> Delete this migration file after running it for security.</p>";
}

// Run the migration
addPasswordResetColumns();