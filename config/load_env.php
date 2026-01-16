<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Carregar variáveis do .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();