<?php
// backend/remove_password_columns.php
// Run this script to remove password-related columns from individual tables
// since we're now using the unified user table

require_once 'classes/database.class.php';

$connection = new Database();
$conn = $connection->connect();

echo "<h1>Removing Password Columns from Individual Tables</h1>";
echo "<pre>";

// Tables to update
$tables = ['teacher', 'postg_teacher', 'interpreter', 'technician'];

// Columns to remove
$columnsToRemove = [
    'password_hash',
    'password_reset_token', 
    'password_reset_expires',
    'first_login',
    'last_login'
];

$totalColumnsRemoved = 0;
$errors = 0;

try {
    // Start transaction
    $conn->beginTransaction();
    
    foreach ($tables as $table) {
        echo "\nProcessing table: $table\n";
        echo str_repeat('-', 40) . "\n";
        
        // Check if table exists
        $tableExists = $conn->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;
        
        if (!$tableExists) {
            echo "⚠️ Table $table not found, skipping...\n";
            continue;
        }
        
        // Get existing columns
        $stmt = $conn->query("SHOW COLUMNS FROM $table");
        $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $removedCount = 0;
        
        foreach ($columnsToRemove as $column) {
            if (in_array($column, $existingColumns)) {
                try {
                    // First, drop any indexes on this column
                    $indexStmt = $conn->prepare("
                        SELECT INDEX_NAME 
                        FROM INFORMATION_SCHEMA.STATISTICS 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = :table 
                        AND COLUMN_NAME = :column
                    ");
                    $indexStmt->execute([':table' => $table, ':column' => $column]);
                    $indexes = $indexStmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    foreach ($indexes as $index) {
                        if ($index !== 'PRIMARY') {
                            $conn->exec("DROP INDEX `$index` ON `$table`");
                            echo "  ✓ Dropped index $index\n";
                        }
                    }
                    
                    // Now drop the column
                    $sql = "ALTER TABLE `$table` DROP COLUMN `$column`";
                    $conn->exec($sql);
                    echo "  ✓ Removed column: $column\n";
                    $removedCount++;
                    $totalColumnsRemoved++;
                } catch (PDOException $e) {
                    echo "  ✗ Error removing column $column: " . $e->getMessage() . "\n";
                    $errors++;
                }
            } else {
                echo "  - Column $column not found (already removed?)\n";
            }
        }
        
        if ($removedCount > 0) {
            echo "✅ Removed $removedCount columns from $table\n";
        } else {
            echo "ℹ️ No columns to remove from $table\n";
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "Migration Summary:\n";
    echo str_repeat('=', 50) . "\n";
    echo "✓ Total columns removed: $totalColumnsRemoved\n";
    
    if ($errors > 0) {
        echo "⚠️ Errors encountered: $errors\n";
    }
    
    // Verify the unified user table has all necessary columns
    echo "\nVerifying unified user table structure...\n";
    $userColumns = $conn->query("SHOW COLUMNS FROM user")->fetchAll(PDO::FETCH_COLUMN);
    $requiredColumns = ['id', 'name', 'email', 'cpf', 'password_hash', 'user_type', 
                       'password_reset_token', 'password_reset_expires', 'first_login'];
    
    $missingColumns = array_diff($requiredColumns, $userColumns);
    
    if (empty($missingColumns)) {
        echo "✅ Unified user table has all required columns\n";
    } else {
        echo "⚠️ Unified user table is missing columns: " . implode(', ', $missingColumns) . "\n";
    }
    
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "IMPORTANT NOTES:\n";
    echo str_repeat('=', 50) . "\n";
    echo "1. Password management is now handled by the unified 'user' table\n";
    echo "2. Make sure all authentication routes use the new unified system\n";
    echo "3. The forgot password and reset password processes have been updated\n";
    echo "4. Individual tables no longer store password information\n";
    
} catch (Exception $e) {
    // Rollback on error
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "ERROR: " . $e->getMessage() . "\n";
    echo str_repeat('=', 50) . "\n";
    echo "Migration was rolled back due to errors.\n";
}

echo "</pre>";