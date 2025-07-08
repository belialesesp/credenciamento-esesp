<?php
// init.php - Place this file in your ROOT directory
// This file initializes the application and sets up common variables

// Error reporting settings
error_reporting(E_ALL & ~E_DEPRECATED);
// For production, use:
// error_reporting(0);
// ini_set('display_errors', 0);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database class
require_once(__DIR__ . "/backend/classes/database.class.php");

// Create database connection
try {
    $conection = new Database();
    $conn = $conection->connect();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize user variables
$user_name = '';
$user_type = '';
$user_email = '';
$is_admin = false;
$is_authenticated = false;
$navbar = false;
$first_login = false;

// Check if user is authenticated
if(isset($_SESSION['user_id'])) {
    $navbar = true;
    $is_authenticated = true;
    
    // Get user details from session
    $user_name = $_SESSION['user_name'] ?? '';
    $user_email = $_SESSION['user_email'] ?? '';
    $user_type = $_SESSION['user_type'] ?? '';
    $first_login = $_SESSION['first_login'] ?? false;
    
    // Check if user is admin
    $is_admin = ($user_type === 'admin');
}

// Helper functions available throughout the application
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function getUserType() {
    return $_SESSION['user_type'] ?? null;
}

function getUserName() {
    return $_SESSION['user_name'] ?? 'Usuário';
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getTypeId() {
    return $_SESSION['type_id'] ?? null;
}

function isFirstLogin() {
    return $_SESSION['first_login'] ?? false;
}

// Redirect helper functions
function requireLogin() {
    if (!isAuthenticated()) {
        header('Location: ' . getBasePath() . '/pages/login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . getBasePath() . '/pages/home.php');
        exit();
    }
}

// Get base path for redirects
function getBasePath() {
    // Adjust this if your app is in a subdirectory
    return '/credenciamento';
}

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Set default timezone (adjust as needed)
date_default_timezone_set('America/Sao_Paulo');