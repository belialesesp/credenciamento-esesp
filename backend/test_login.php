<?php
// backend/test_cpf_login.php - Test CPF login functionality
require_once 'classes/database.class.php';

echo "<h2>CPF Login Test</h2>";

try {
    $connection = new Database();
    $conn = $connection->connect();
    
    // Test 1: Check some users with CPF
    echo "<h3>1. Users with CPF in the system:</h3>";
    $stmt = $conn->query("SELECT name, cpf, user_type FROM user WHERE cpf IS NOT NULL AND cpf != '' LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Name</th><th>CPF (login)</th><th>Type</th><th>Default Password</th></tr>";
        foreach ($users as $user) {
            $cleanCpf = preg_replace('/[^0-9]/', '', $user['cpf']);
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['cpf']) . "</td>";
            echo "<td>" . htmlspecialchars($user['user_type']) . "</td>";
            echo "<td>" . $cleanCpf . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No users with CPF found<br>";
    }
    
    // Test 2: Test CPF cleaning
    echo "<h3>2. CPF Cleaning Test:</h3>";
    $testCpfs = [
        '123.456.789-01',
        '12345678901',
        '123-456-789-01',
        '123 456 789 01'
    ];
    
    foreach ($testCpfs as $cpf) {
        $clean = preg_replace('/[^0-9]/', '', $cpf);
        echo "Original: '$cpf' → Clean: '$clean'<br>";
    }
    
    // Test 3: Check if any users still have email-based passwords
    echo "<h3>3. Password Status Check:</h3>";
    $stmt = $conn->query("SELECT COUNT(*) as total FROM user WHERE user_type != 'admin'");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total non-admin users: $total<br>";
    
    // Test 4: Verify admin account
    echo "<h3>4. Admin Account Check:</h3>";
    $stmt = $conn->prepare("SELECT * FROM user WHERE email = 'credenciamento'");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "✓ Admin account exists<br>";
        echo "- Login with: 'credenciamento' (as CPF)<br>";
        echo "- User type: " . $admin['user_type'] . "<br>";
    } else {
        echo "✗ Admin account not found!<br>";
    }
    
    // Test 5: Sample login test
    echo "<h3>5. Sample Login Test (simulation):</h3>";
    $testUser = $conn->query("SELECT * FROM user WHERE user_type = 'teacher' AND cpf IS NOT NULL LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if ($testUser) {
        $cleanCpf = preg_replace('/[^0-9]/', '', $testUser['cpf']);
        echo "Test user: " . $testUser['name'] . "<br>";
        echo "CPF: " . $testUser['cpf'] . "<br>";
        echo "Clean CPF (for login): " . $cleanCpf . "<br>";
        
        // Test password
        if (password_verify($cleanCpf, $testUser['password_hash'])) {
            echo "✓ Password is still default (CPF)<br>";
        } else {
            echo "- Password has been changed from default<br>";
        }
    }
    
    echo "<h3>Login Instructions:</h3>";
    echo "<ol>";
    echo "<li><strong>Admin:</strong> CPF field: <code>credenciamento</code>, Password: <code>admin123</code></li>";
    echo "<li><strong>All Others:</strong> CPF field: <code>[Your CPF]</code>, Password: <code>[CPF numbers only]</code></li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}