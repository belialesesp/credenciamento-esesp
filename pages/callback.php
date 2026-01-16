<?php
session_start();

// O processamento já foi feito no login.php
// Esta página só recebe o POST do Acesso Cidadão

if (!isset($_SESSION['user'])) {
    header("Location: /login.php");
    exit;
}

$returnUrl = $_SESSION['return_url'] ?? '/index.php';
unset($_SESSION['return_url']);

header("Location: " . $returnUrl);
exit;