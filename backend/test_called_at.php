<?php
// test_called_at.php - Place this in your backend folder and run once for testing

require_once 'classes/database.class.php';

$connection = new Database();
$conn = $connection->connect();

try {
    // Set called_at for some random technicians (50% of them)
    $sql = "UPDATE technician 
            SET called_at = DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 30) DAY)
            WHERE id % 2 = 0";
    $conn->exec($sql);
    echo "Updated technicians with random called_at dates.<br>";
    
    // Set called_at for some random interpreters (50% of them)
    $sql = "UPDATE interpreter 
            SET called_at = DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 30) DAY)
            WHERE id % 2 = 0";
    $conn->exec($sql);
    echo "Updated interpreters with random called_at dates.<br>";
    
    // Show sample data
    echo "<h3>Sample Technicians:</h3>";
    $stmt = $conn->query("SELECT name, called_at FROM technician LIMIT 10");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Name</th><th>Called At</th></tr>";
    foreach ($results as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . ($row['called_at'] ?? 'Not called') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Sample Interpreters:</h3>";
    $stmt = $conn->query("SELECT name, called_at FROM interpreter LIMIT 10");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Name</th><th>Called At</th></tr>";
    foreach ($results as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . ($row['called_at'] ?? 'Not called') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}