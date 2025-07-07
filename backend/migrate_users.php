<?php
// backend/migrate_users.php
// Run this script once to set up the unified authentication system

require_once 'classes/database.class.php';

$connection = new Database();
$conn = $connection->connect();

try {
    echo "<h2>User Authentication Migration</h2>";
    
    // Step 1: Create user table
    echo "<h3>Step 1: Creating user table...</h3>";
    
    // First, drop the table if it exists (BE CAREFUL - this will delete all existing user data)
    // Comment out this line if you want to preserve existing user table
    // $conn->exec("DROP TABLE IF EXISTS user");
    
    $createTableQuery = "
    CREATE TABLE IF NOT EXISTS user (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        cpf VARCHAR(20) UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        user_type ENUM('admin', 'teacher', 'postg_teacher', 'technician', 'interpreter') DEFAULT 'admin',
        type_id INT DEFAULT NULL COMMENT 'References the ID in the corresponding table',
        password_reset_token VARCHAR(100) DEFAULT NULL,
        password_reset_expires DATETIME DEFAULT NULL,
        first_login BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_email_type (email, user_type),
        INDEX idx_cpf (cpf),
        INDEX idx_reset_token (password_reset_token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    try {
        $conn->exec($createTableQuery);
        echo "✓ User table created successfully<br>";
    } catch (PDOException $e) {
        echo "✗ Error creating user table: " . $e->getMessage() . "<br>";
        throw $e;
    }
    
    // Step 2: Create admin user
    echo "<h3>Step 2: Creating admin user...</h3>";
    
    $adminPassword = 'admin123'; // CHANGE THIS!
    $adminPasswordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
    
    try {
        $stmt = $conn->prepare("INSERT INTO user (name, email, password_hash, user_type, first_login) VALUES (?, ?, ?, 'admin', FALSE)");
        $stmt->execute(['Credenciamento Admin', 'credenciamento', $adminPasswordHash]);
        echo "✓ Admin user 'credenciamento' created with password: $adminPassword<br>";
        echo "<strong>⚠️ IMPORTANT: Change this password immediately after first login!</strong><br>";
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            echo "- Admin user already exists<br>";
        } else {
            echo "✗ Error creating admin: " . $e->getMessage() . "<br>";
        }
    }
    
    // Step 3: Migrate existing users
    echo "<h3>Step 3: Migrating existing registrations to user accounts...</h3>";
    
    // Check which tables exist
    $existingTables = [];
    $checkTables = ['teacher', 'postg_teacher', 'technician', 'interpreter'];
    
    foreach ($checkTables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            $existingTables[$table] = $table; // table name maps to user_type
        }
    }
    
    if (empty($existingTables)) {
        echo "⚠️ No existing tables found to migrate from.<br>";
    } else {
        foreach ($existingTables as $table => $userType) {
            echo "<h4>Migrating $table...</h4>";
            
            // Check if required columns exist
            $columns = $conn->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
            $requiredColumns = ['id', 'name', 'email', 'cpf'];
            $missingColumns = array_diff($requiredColumns, $columns);
            
            if (!empty($missingColumns)) {
                echo "⚠️ Table $table is missing columns: " . implode(', ', $missingColumns) . ". Skipping...<br>";
                continue;
            }
            
            $query = "SELECT id, name, email, cpf FROM $table";
            $stmt = $conn->query($query);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $count = 0;
            $skipped = 0;
            foreach ($records as $record) {
                // Clean CPF - remove dots and dashes
                $cleanCpf = preg_replace('/[^0-9]/', '', $record['cpf']);
                
                if (empty($cleanCpf)) {
                    echo "- Skipping {$record['name']} - no CPF<br>";
                    $skipped++;
                    continue;
                }
                
                // Check if user already exists
                $checkStmt = $conn->prepare("SELECT id FROM user WHERE cpf = ? OR (email = ? AND user_type = ?)");
                $checkStmt->execute([$cleanCpf, $record['email'], $userType]);
                
                if (!$checkStmt->fetch()) {
                    // Create user with CPF as password
                    $passwordHash = password_hash($cleanCpf, PASSWORD_DEFAULT);
                    
                    $insertStmt = $conn->prepare("
                        INSERT INTO user (name, email, cpf, password_hash, user_type, type_id, first_login) 
                        VALUES (?, ?, ?, ?, ?, ?, TRUE)
                    ");
                    
                    try {
                        $insertStmt->execute([
                            $record['name'],
                            $record['email'],
                            $cleanCpf,
                            $passwordHash,
                            $userType,
                            $record['id']
                        ]);
                        $count++;
                    } catch (PDOException $e) {
                        echo "- Error migrating {$record['name']}: " . $e->getMessage() . "<br>";
                    }
                }
            }
            
            echo "✓ Migrated $count new users from $table";
            if ($skipped > 0) {
                echo " (skipped $skipped records)";
            }
            echo "<br>";
        }
    }
    
    // Step 4: Create triggers for future registrations
    echo "<h3>Step 4: Creating triggers for automatic user creation...</h3>";
    
    // Note: Triggers will need to be updated with proper password hashing in the application
    $triggerTemplate = "
        CREATE TRIGGER after_%s_insert
        AFTER INSERT ON %s
        FOR EACH ROW
        BEGIN
            DECLARE clean_cpf VARCHAR(20);
            SET clean_cpf = REPLACE(REPLACE(NEW.cpf, '.', ''), '-', '');
            
            -- Only insert if CPF is not empty
            IF clean_cpf != '' THEN
                INSERT IGNORE INTO user (name, email, cpf, password_hash, user_type, type_id, first_login)
                VALUES (
                    NEW.name, 
                    NEW.email, 
                    clean_cpf,
                    '', -- Password will be set by the application
                    '%s', 
                    NEW.id,
                    TRUE
                );
            END IF;
        END";
    
    foreach ($existingTables as $table => $userType) {
        try {
            // Drop existing trigger if exists
            $conn->exec("DROP TRIGGER IF EXISTS after_{$table}_insert");
            
            $triggerSql = sprintf($triggerTemplate, $table, $table, $userType);
            $conn->exec($triggerSql);
            echo "✓ Created trigger for $table<br>";
        } catch (PDOException $e) {
            echo "✗ Error creating trigger for $table: " . $e->getMessage() . "<br>";
            // Triggers might not be supported or user might not have privileges
            echo "  Note: You may need SUPER or TRIGGER privileges to create triggers.<br>";
        }
    }
    
    // Step 5: Summary
    echo "<h3>Migration Complete!</h3>";
    
    // Show user statistics
    $stats = $conn->query("SELECT user_type, COUNT(*) as count FROM user GROUP BY user_type")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h4>User Statistics:</h4>";
    echo "<ul>";
    foreach ($stats as $stat) {
        echo "<li>{$stat['user_type']}: {$stat['count']} users</li>";
    }
    echo "</ul>";
    
    echo "<h4>Next steps:</h4>";
    echo "<ol>";
    echo "<li><strong>Important:</strong> Update your registration scripts to properly create users when new people register</li>";
    echo "<li>Test login with admin user: <code>credenciamento / admin123</code></li>";
    echo "<li><strong>Change the admin password immediately after first login!</strong></li>";
    echo "<li>Test login with a migrated user (use their CPF as password)</li>";
    echo "<li>Implement password reset functionality for users to change their CPF passwords</li>";
    echo "</ol>";
    
    echo "<h4>Default Passwords:</h4>";
    echo "<ul>";
    echo "<li>Admin user 'credenciamento': <code>admin123</code></li>";
    echo "<li>All migrated users: Their CPF (numbers only, no dots or dashes)</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3>Fatal Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}