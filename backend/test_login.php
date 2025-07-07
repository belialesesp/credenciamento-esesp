<?php
// backend/test_login.php - Test script to verify the login system
require_once 'classes/database.class.php';

echo "<h2>Login System Test</h2>";

try {
    $connection = new Database();
    $conn = $connection->connect();
    
    // Test 1: Check if user table exists
    echo "<h3>1. Checking user table...</h3>";
    $result = $conn->query("SHOW TABLES LIKE 'user'");
    if ($result->rowCount() > 0) {
        echo "✓ User table exists<br>";
        
        // Show table structure
        $columns = $conn->query("SHOW COLUMNS FROM user")->fetchAll(PDO::FETCH_COLUMN);
        echo "Columns: " . implode(', ', $columns) . "<br>";
    } else {
        echo "✗ User table not found!<br>";
    }
    
    // Test 2: Check admin user
    echo "<h3>2. Checking admin user...</h3>";
    $stmt = $conn->prepare("SELECT * FROM user WHERE email = 'credenciamento'");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "✓ Admin user found<br>";
        echo "- Name: " . $admin['name'] . "<br>";
        echo "- User type: " . $admin['user_type'] . "<br>";
        
        // Test admin password
        if (password_verify('admin123', $admin['password_hash'])) {
            echo "✓ Admin password is still default (admin123) - CHANGE THIS!<br>";
        } else {
            echo "✓ Admin password has been changed from default<br>";
        }
    } else {
        echo "✗ Admin user not found!<br>";
    }
    
    // Test 3: Check some migrated users
    echo "<h3>3. Sample migrated users...</h3>";
    $stmt = $conn->query("SELECT name, email, cpf, user_type FROM user WHERE user_type != 'admin' LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Name</th><th>Email</th><th>CPF</th><th>Type</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['cpf']) . "</td>";
            echo "<td>" . htmlspecialchars($user['user_type']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No migrated users found<br>";
    }
    
    // Test 4: Check user type counts
    echo "<h3>4. User counts by type...</h3>";
    $stats = $conn->query("SELECT user_type, COUNT(*) as count FROM user GROUP BY user_type")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($stats as $stat) {
        echo "- " . $stat['user_type'] . ": " . $stat['count'] . " users<br>";
    }
    
    // Test 5: Test a specific login
    echo "<h3>5. Test login functionality...</h3>";
    
    // Get a sample teacher to test
    $stmt = $conn->query("SELECT * FROM user WHERE user_type = 'teacher' LIMIT 1");
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testUser) {
        echo "Testing with user: " . $testUser['name'] . "<br>";
        echo "- CPF: " . $testUser['cpf'] . "<br>";
        
        // Test if password matches CPF
        if (password_verify($testUser['cpf'], $testUser['password_hash'])) {
            echo "✓ Password is still the default (CPF)<br>";
        } else {
            echo "- Password has been changed from default<br>";
        }
    }
    
    // Show login instructions
    echo "<h3>How to Login:</h3>";
    echo "<ol>";
    echo "<li><strong>Admin:</strong> Username: <code>credenciamento</code>, Password: <code>admin123</code></li>";
    echo "<li><strong>Teachers/Others:</strong> Username: <code>[CPF without dots/dashes]</code>, Password: <code>[same as username]</code></li>";
    echo "</ol>";
    
    echo "<h3>Important Files:</h3>";
    echo "<ul>";
    echo "<li>Login page: <code>/pages/login.php</code></li>";
    echo "<li>Login processor: <code>/auth/process_login.php</code></li>";
    echo "<li>Password change: <code>/auth/process_change_password.php</code></li>";
    echo "<li>Logout: <code>/auth/logout.php</code></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}