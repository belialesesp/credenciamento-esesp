<?php
// backend/migrate_to_unified_auth.php
// Run this script to migrate existing users to the unified authentication system

require_once 'classes/database.class.php';

$connection = new Database();
$conn = $connection->connect();

echo "<h1>Migration to Unified Authentication System</h1>";
echo "<pre>";

try {
    // Start transaction
    $conn->beginTransaction();
    
    // Step 1: Create user table if it doesn't exist
    echo "Step 1: Creating unified user table...\n";
    
    $createTableSQL = file_get_contents(__DIR__ . '/../sql/create_user_table.sql');
    if (!$createTableSQL) {
        // Use embedded SQL if file not found
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS `user` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `email` varchar(255) NOT NULL,
            `cpf` varchar(20) NOT NULL,
            `password_hash` varchar(255) DEFAULT NULL,
            `user_type` enum('admin','teacher','postg_teacher','technician','interpreter') NOT NULL,
            `type_id` int(11) DEFAULT NULL,
            `password_reset_token` varchar(100) DEFAULT NULL,
            `password_reset_expires` datetime DEFAULT NULL,
            `first_login` tinyint(1) DEFAULT 1,
            `last_login` datetime DEFAULT NULL,
            `login_attempts` int(11) DEFAULT 0,
            `locked_until` datetime DEFAULT NULL,
            `active` tinyint(1) DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_cpf` (`cpf`),
            UNIQUE KEY `unique_email_type` (`email`,`user_type`),
            KEY `idx_type_id` (`user_type`,`type_id`),
            KEY `idx_reset_token` (`password_reset_token`),
            KEY `idx_last_login` (`last_login`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    }
    
    $conn->exec($createTableSQL);
    echo "✓ User table created/verified\n\n";
    
    // Step 2: Migrate users from each table
    $tables = [
        'teacher' => 'teacher',
        'postg_teacher' => 'postg_teacher',
        'interpreter' => 'interpreter',
        'technician' => 'technician'
    ];
    
    $totalMigrated = 0;
    
    foreach ($tables as $userType => $tableName) {
        echo "Migrating $tableName...\n";
        
        // Check if table has authentication columns
        $checkColsStmt = $conn->prepare("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = :table 
            AND COLUMN_NAME IN ('password_hash', 'password_reset_token', 'password_reset_expires', 'first_login', 'last_login')
        ");
        $checkColsStmt->execute([':table' => $tableName]);
        $authColumns = $checkColsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($authColumns)) {
            echo "  - Table doesn't have auth columns, skipping\n";
            continue;
        }
        
        // Build dynamic SELECT based on existing columns
        $selectCols = ['id', 'name', 'email', 'cpf'];
        if (in_array('password_hash', $authColumns)) $selectCols[] = 'password_hash';
        if (in_array('password_reset_token', $authColumns)) $selectCols[] = 'password_reset_token';
        if (in_array('password_reset_expires', $authColumns)) $selectCols[] = 'password_reset_expires';
        if (in_array('first_login', $authColumns)) $selectCols[] = 'first_login';
        if (in_array('last_login', $authColumns)) $selectCols[] = 'last_login';
        
        $selectSQL = "SELECT " . implode(', ', $selectCols) . " FROM $tableName";
        $stmt = $conn->query($selectSQL);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $migrated = 0;
        $skipped = 0;
        
        foreach ($users as $user) {
            $cleanCpf = preg_replace('/\D/', '', $user['cpf']);
            
            // Check if already migrated
            $checkStmt = $conn->prepare("SELECT id FROM user WHERE cpf = :cpf");
            $checkStmt->execute([':cpf' => $cleanCpf]);
            
            if ($checkStmt->fetch()) {
                $skipped++;
                continue;
            }
            
            // Prepare insert data
            $insertData = [
                ':name' => $user['name'],
                ':email' => $user['email'],
                ':cpf' => $cleanCpf,
                ':user_type' => $userType,
                ':type_id' => $user['id'],
                ':password_hash' => $user['password_hash'] ?? password_hash($cleanCpf, PASSWORD_DEFAULT),
                ':reset_token' => $user['password_reset_token'] ?? null,
                ':reset_expires' => $user['password_reset_expires'] ?? null,
                ':first_login' => isset($user['first_login']) ? $user['first_login'] : (empty($user['password_hash']) ? 1 : 0),
                ':last_login' => $user['last_login'] ?? null
            ];
            
            // Insert into user table
            $insertStmt = $conn->prepare("
                INSERT INTO user (
                    name, email, cpf, password_hash, user_type, type_id,
                    password_reset_token, password_reset_expires,
                    first_login, last_login
                ) VALUES (
                    :name, :email, :cpf, :password_hash, :user_type, :type_id,
                    :reset_token, :reset_expires, :first_login, :last_login
                )
            ");
            
            try {
                $insertStmt->execute($insertData);
                $migrated++;
            } catch (PDOException $e) {
                echo "  ! Error migrating user {$user['name']}: " . $e->getMessage() . "\n";
            }
        }
        
        echo "  ✓ Migrated: $migrated users\n";
        if ($skipped > 0) {
            echo "  - Skipped: $skipped (already exist)\n";
        }
        echo "  Total in $tableName: " . count($users) . "\n\n";
        
        $totalMigrated += $migrated;
    }
    
    // Step 3: Create admin user if doesn't exist
    echo "Creating admin user...\n";
    $checkAdminStmt = $conn->prepare("SELECT id FROM user WHERE user_type = 'admin'");
    $checkAdminStmt->execute();
    
    if (!$checkAdminStmt->fetch()) {
        $adminPassword = 'admin123';
        $adminPasswordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
        
        $createAdminStmt = $conn->prepare("
            INSERT INTO user (name, email, cpf, password_hash, user_type, first_login) 
            VALUES (:name, :email, :cpf, :password, 'admin', 0)
        ");
        
        $createAdminStmt->execute([
            ':name' => 'Administrador do Sistema',
            ':email' => 'admin@sistema.com',
            ':cpf' => '00000000000',
            ':password' => $adminPasswordHash
        ]);
        
        echo "  ✓ Admin user created\n";
        echo "  Username: admin or CPF: 00000000000\n";
        echo "  Password: admin123\n";
        echo "  ⚠️  CHANGE THIS PASSWORD IMMEDIATELY!\n";
    } else {
        echo "  - Admin user already exists\n";
    }
    
    // Commit transaction
    $conn->commit();
    
    echo "\n✅ MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo "Total users migrated: $totalMigrated\n";
    
    // Show summary
    echo "\nUser Summary:\n";
    $summaryStmt = $conn->query("
        SELECT user_type, COUNT(*) as count 
        FROM user 
        GROUP BY user_type
    ");
    
    foreach ($summaryStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "  {$row['user_type']}: {$row['count']} users\n";
    }
    
    echo "\nNext Steps:\n";
    echo "1. Update your code to use the new auth_unified.class.php\n";
    echo "2. Replace old process_login.php with the new version\n";
    echo "3. Test login with migrated users\n";
    echo "4. After confirming everything works, you can remove auth columns from individual tables\n";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "\n❌ MIGRATION FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "No changes were made to the database.\n";
}

echo "</pre>";