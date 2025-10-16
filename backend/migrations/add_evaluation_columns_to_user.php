<?php
// backend/migrations/add_evaluation_columns_to_user.php
// Run this script ONCE to add evaluation columns to the user table

require_once __DIR__ . '/../classes/database.class.php';

echo "<h2>Adding Evaluation Columns to User Table</h2>";
echo "<pre>";
echo "Starting migration...\n\n";

try {
    $connection = new Database();
    $conn = $connection->connect();
    
    // Check if columns already exist
    $checkStmt = $conn->prepare("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'user' 
        AND COLUMN_NAME IN (
            'gese_evaluation', 
            'gese_evaluated_at', 
            'gese_evaluated_by',
            'pedagogico_evaluation',
            'pedagogico_evaluated_at',
            'pedagogico_evaluated_by'
        )
    ");
    $checkStmt->execute();
    $existingColumns = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Existing evaluation columns: " . (empty($existingColumns) ? "None" : implode(', ', $existingColumns)) . "\n\n";
    
    // Add gese_evaluation column
    if (!in_array('gese_evaluation', $existingColumns)) {
        $conn->exec("
            ALTER TABLE user 
            ADD COLUMN gese_evaluation TINYINT(1) DEFAULT NULL COMMENT '0=Reprovado, 1=Aprovado, NULL=Pendente'
        ");
        echo "✓ Added gese_evaluation column\n";
    } else {
        echo "- gese_evaluation column already exists\n";
    }
    
    // Add gese_evaluated_at column
    if (!in_array('gese_evaluated_at', $existingColumns)) {
        $conn->exec("
            ALTER TABLE user 
            ADD COLUMN gese_evaluated_at DATETIME DEFAULT NULL COMMENT 'Data da avaliação GESE'
        ");
        echo "✓ Added gese_evaluated_at column\n";
    } else {
        echo "- gese_evaluated_at column already exists\n";
    }
    
    // Add gese_evaluated_by column
    if (!in_array('gese_evaluated_by', $existingColumns)) {
        $conn->exec("
            ALTER TABLE user 
            ADD COLUMN gese_evaluated_by INT(11) DEFAULT NULL COMMENT 'ID do avaliador GESE'
        ");
        echo "✓ Added gese_evaluated_by column\n";
    } else {
        echo "- gese_evaluated_by column already exists\n";
    }
    
    // Add pedagogico_evaluation column
    if (!in_array('pedagogico_evaluation', $existingColumns)) {
        $conn->exec("
            ALTER TABLE user 
            ADD COLUMN pedagogico_evaluation TINYINT(1) DEFAULT NULL COMMENT '0=Reprovado, 1=Aprovado, NULL=Pendente'
        ");
        echo "✓ Added pedagogico_evaluation column\n";
    } else {
        echo "- pedagogico_evaluation column already exists\n";
    }
    
    // Add pedagogico_evaluated_at column
    if (!in_array('pedagogico_evaluated_at', $existingColumns)) {
        $conn->exec("
            ALTER TABLE user 
            ADD COLUMN pedagogico_evaluated_at DATETIME DEFAULT NULL COMMENT 'Data da avaliação pedagógica'
        ");
        echo "✓ Added pedagogico_evaluated_at column\n";
    } else {
        echo "- pedagogico_evaluated_at column already exists\n";
    }
    
    // Add pedagogico_evaluated_by column
    if (!in_array('pedagogico_evaluated_by', $existingColumns)) {
        $conn->exec("
            ALTER TABLE user 
            ADD COLUMN pedagogico_evaluated_by INT(11) DEFAULT NULL COMMENT 'ID do avaliador pedagógico'
        ");
        echo "✓ Added pedagogico_evaluated_by column\n";
    } else {
        echo "- pedagogico_evaluated_by column already exists\n";
    }
    
    // Add indexes for better performance
    echo "\nAdding indexes for better query performance...\n";
    
    try {
        $conn->exec("CREATE INDEX idx_gese_evaluation ON user (gese_evaluation)");
        echo "✓ Added index on gese_evaluation\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "- Index on gese_evaluation already exists\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $conn->exec("CREATE INDEX idx_pedagogico_evaluation ON user (pedagogico_evaluation)");
        echo "✓ Added index on pedagogico_evaluation\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "- Index on pedagogico_evaluation already exists\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $conn->exec("CREATE INDEX idx_gese_evaluated_by ON user (gese_evaluated_by)");
        echo "✓ Added index on gese_evaluated_by\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "- Index on gese_evaluated_by already exists\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $conn->exec("CREATE INDEX idx_pedagogico_evaluated_by ON user (pedagogico_evaluated_by)");
        echo "✓ Added index on pedagogico_evaluated_by\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "- Index on pedagogico_evaluated_by already exists\n";
        } else {
            throw $e;
        }
    }
    
    echo "\n✅ Migration completed successfully!\n\n";
    
    // Verify the columns were added
    echo "Verifying columns...\n";
    $verifyStmt = $conn->prepare("
        SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'user' 
        AND COLUMN_NAME IN (
            'gese_evaluation', 
            'gese_evaluated_at', 
            'gese_evaluated_by',
            'pedagogico_evaluation',
            'pedagogico_evaluated_at',
            'pedagogico_evaluated_by'
        )
        ORDER BY ORDINAL_POSITION
    ");
    $verifyStmt->execute();
    $columns = $verifyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nColumn Details:\n";
    echo str_repeat("-", 100) . "\n";
    printf("%-30s %-20s %-15s %-20s %s\n", "Column Name", "Type", "Nullable", "Default", "Comment");
    echo str_repeat("-", 100) . "\n";
    
    foreach ($columns as $col) {
        printf(
            "%-30s %-20s %-15s %-20s %s\n",
            $col['COLUMN_NAME'],
            $col['COLUMN_TYPE'],
            $col['IS_NULLABLE'],
            $col['COLUMN_DEFAULT'] ?? 'NULL',
            $col['COLUMN_COMMENT']
        );
    }
    
    echo str_repeat("-", 100) . "\n";
    echo "\nTotal evaluation columns added: " . count($columns) . "\n";
    
} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ General Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n</pre>";
echo "<p><strong>Migration completed! You can now close this page.</strong></p>";
echo "<p><a href='../../pages/home.php'>Return to Home</a></p>";
?>