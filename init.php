<?php

// For development - show all errors except deprecation warnings
error_reporting(E_ALL & ~E_DEPRECATED);

// For production - hide all errors
// error_reporting(0);
// ini_set('display_errors', 0);

// To log errors instead of displaying them
// ini_set('log_errors', 1);
// ini_set('error_log', '/path/to/error.log');
  require_once(__DIR__ . "/backend/classes/database.class.php");


    session_start();

    $conection = new Database();
    $conn = $conection->connect();
    $user_name = '';
    $admin = false;
    $navbar = false;

    if(isset($_SESSION['user_id'])) {
      $navbar = true;

      $sql = "SELECT id, name, access_level FROM user";
      $stmt = $conn->prepare($sql);
      $stmt->execute();

      $user = $stmt->fetch(PDO::FETCH_ASSOC);
      $user_name = $user['name'];
      $admin = $user['access_level'] == 1 ? true : false;

    }


?>