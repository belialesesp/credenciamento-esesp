<?php
// backend/migrate_passwords.php
// Run this script once to set initial passwords for existing users

require_once 'classes/database.class.php';

$connection = new Database();
$conn = $connection->connect();

// Tables to migrate
$tables = ['teacher', 'postg_teacher', 'interpreter', 'technician'];

echo "<h2>Password Migration Script</h2>";
echo "<p>This script will set initial passwords for all users based on their CPF.</p>";

$totalMigrated = 0;
$errors = 0;

foreach ($tables as $table) {
    echo "<h3>Processing table: $table</h3>";
    
    try {
        // First, check if password columns exist
        $checkColumns = $conn->query("SHOW COLUMNS FROM $table LIKE 'password_hash'");
        if ($checkColumns->rowCount() == 0) {
            echo "<p style='color: orange;'>⚠️ Table $table doesn't have password_hash column. Run the SQL migration first!</p>";
            continue;
        }
        
        // Get all users without passwords
        $stmt = $conn->prepare("
            SELECT id, name, cpf 
            FROM $table 
            WHERE password_hash IS NULL OR password_hash = ''
        ");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($users) == 0) {
            echo "<p>✓ No users need password migration in this table.</p>";
            continue;
        }
        
        echo "<p>Found " . count($users) . " users to migrate...</p>";
        
        // Prepare update statement
        $updateStmt = $conn->prepare("
            UPDATE $table 
            SET password_hash = :hash,
                first_login = TRUE
            WHERE id = :id
        ");
        
        $migrated = 0;
        foreach ($users as $user) {
            // Clean CPF (remove dots and dashes)
            $cleanCpf = preg_replace('/\D/', '', $user['cpf']);
            
            if (empty($cleanCpf)) {
                echo "<p style='color: red;'>✗ User {$user['name']} (ID: {$user['id']}) has no CPF!</p>";
                $errors++;
                continue;
            }
            
            // Create password hash from CPF
            $passwordHash = password_hash($cleanCpf, PASSWORD_DEFAULT);
            
            // Update user
            $updateStmt->bindParam(':hash', $passwordHash);
            $updateStmt->bindParam(':id', $user['id']);
            
            if ($updateStmt->execute()) {
                $migrated++;
            } else {
                echo "<p style='color: red;'>✗ Failed to update user {$user['name']} (ID: {$user['id']})</p>";
                $errors++;
            }
        }
        
        echo "<p style='color: green;'>✓ Migrated $migrated users in $table</p>";
        $totalMigrated += $migrated;
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ Error processing table $table: " . $e->getMessage() . "</p>";
        $errors++;
    }
}

echo "<hr>";
echo "<h3>Migration Complete!</h3>";
echo "<p>Total users migrated: $totalMigrated</p>";
if ($errors > 0) {
    echo "<p style='color: orange;'>⚠️ There were $errors errors during migration.</p>";
}

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Test login with a user's CPF as both username and password</li>";
echo "<li>Users will be prompted to change their password on first login</li>";
echo "<li>Make sure email configuration is set up for password reset functionality</li>";
echo "</ol>";

echo "<h3>Default Login Credentials:</h3>";
echo "<p>All users can now login with:</p>";
echo "<ul>";
echo "<li><strong>Username:</strong> Their CPF (numbers only, no dots or dashes)</li>";
echo "<li><strong>Password:</strong> Same as username (their CPF)</li>";
echo "</ul>";
echo "<p style='color: orange;'>⚠️ Users will be required to change their password on first login!</p>";