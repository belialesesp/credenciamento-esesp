<?php
require_once '../init.php';

if(!isset( $_SESSION['user_id'])) {
  header('Location: login.php');
  exit();
}

include_once('../components/header.php');

var_dump($_SESSION);

include_once('../components/footer.php');