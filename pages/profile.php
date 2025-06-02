<?php
require_once '../init.php';

if(!isset( $_SESSION['user_id'])) {
  header('Location: login.php');
  exit();
}

include_once '../components/header.php';

$user_id = $_SESSION['user_id'];

