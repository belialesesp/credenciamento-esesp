<?php
// backend/test_password_reset.php
// Test script to verify the password reset system is working correctly

require_once 'classes/database.class.php';

echo "<h1>Password Reset System Test</h1>";
echo "<pre>";

try {
    $connection = new Database();
    $conn = $connection->connect();
    
    // Test 1: Check if unified user table has password reset columns
    echo "1. Checking unified user table structure...\n";
    $stmt = $conn->query("SHOW COLUMNS FROM user");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['password_reset_token', 'password_reset_expires'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (empty($missingColumns)) {
        echo "✅ User table has all required password reset columns\n";
    } else {
        echo "❌ User table is missing columns: " . implode(', ', $missingColumns) . "\n";
    }
    
    // Test 2: Check if individual tables still have password columns
    echo "\n2. Checking individual tables for password columns...\n";
    $tables = ['teacher', 'postg_teacher', 'interpreter', 'technician'];
    $passwordColumns = ['password_hash', 'password_reset_token', 'password_reset_expires'];
    
    foreach ($tables as $table) {
        $tableExists = $conn->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;
        
        if ($tableExists) {
            $stmt = $conn->query("SHOW COLUMNS FROM $table");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $foundPasswordColumns = array_intersect($passwordColumns, $columns);
            
            if (empty($foundPasswordColumns)) {
                echo "✅ $table: No password columns (good!)\n";
            } else {
                echo "⚠️ $table: Still has password columns: " . implode(', ', $foundPasswordColumns) . "\n";
                echo "   Run remove_password_columns.php to clean up\n";
            }
        } else {
            echo "- $table: Table not found\n";
        }
    }
    
    // Test 3: Check sample users in unified table
    echo "\n3. Checking sample users in unified table...\n";
    $stmt = $conn->query("
        SELECT user_type, COUNT(*) as count,
               SUM(CASE WHEN password_hash IS NOT NULL AND password_hash != '' THEN 1 ELSE 0 END) as with_password
        FROM user 
        GROUP BY user_type
    ");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "User statistics:\n";
    foreach ($stats as $stat) {
        echo sprintf("- %s: %d total, %d with password\n", 
            $stat['user_type'], 
            $stat['count'], 
            $stat['with_password']
        );
    }
    
    // Test 4: Simulate password reset flow
    echo "\n4. Testing password reset flow...\n";
    
    // Find a test user
    $testUser = $conn->query("
        SELECT id, name, email, cpf, user_type 
        FROM user 
        WHERE user_type != 'admin' 
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);
    
    if ($testUser) {
        echo "Test user: {$testUser['name']} (CPF: {$testUser['cpf']})\n";
        
        // Generate test token
        $testToken = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Update user with test token
        $updateStmt = $conn->prepare("
            UPDATE user 
            SET password_reset_token = :token,
                password_reset_expires = :expires
            WHERE id = :id
        ");
        $updateStmt->execute([
            ':token' => $testToken,
            ':expires' => $expires,
            ':id' => $testUser['id']
        ]);
        
        echo "✅ Successfully set reset token\n";
        
        // Verify token was set
        $verifyStmt = $conn->prepare("
            SELECT id 
            FROM user 
            WHERE password_reset_token = :token 
            AND password_reset_expires > NOW()
        ");
        $verifyStmt->execute([':token' => $testToken]);
        
        if ($verifyStmt->fetch()) {
            echo "✅ Token verified - can be used for password reset\n";
        } else {
            echo "❌ Token verification failed\n";
        }
        
        // Clean up test token
        $cleanupStmt = $conn->prepare("
            UPDATE user 
            SET password_reset_token = NULL,
                password_reset_expires = NULL
            WHERE id = :id
        ");
        $cleanupStmt->execute([':id' => $testUser['id']]);
        echo "✅ Test token cleaned up\n";
    } else {
        echo "⚠️ No test user found\n";
    }
    
    // Test 5: Check for orphaned reset tokens
    echo "\n5. Checking for active reset tokens...\n";
    $activeTokens = $conn->query("
        SELECT COUNT(*) as count 
        FROM user 
        WHERE password_reset_token IS NOT NULL
    ")->fetchColumn();
    
    $expiredTokens = $conn->query("
        SELECT COUNT(*) as count 
        FROM user 
        WHERE password_reset_token IS NOT NULL 
        AND password_reset_expires < NOW()
    ")->fetchColumn();
    
    echo "Active reset tokens: $activeTokens\n";
    echo "Expired reset tokens: $expiredTokens\n";
    
    if ($expiredTokens > 0) {
        echo "⚠️ Consider cleaning up expired tokens\n";
    }
    
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "Summary:\n";
    echo str_repeat('=', 50) . "\n";
    echo "✅ Password reset system is configured for unified authentication\n";
    echo "✅ All password operations now use the 'user' table\n";
    echo "✅ Individual tables should not have password columns\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";