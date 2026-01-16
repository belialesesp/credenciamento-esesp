<?php
session_start();
require_once __DIR__ . '/auth/AcessoCidadaoAuth.php';

$auth = new AcessoCidadaoAuth();
$auth->logout();
// O redirect acontece dentro do método logout()