<?php
// init.php - Updated to work with roles table
// Place this file in your ROOT directory

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
$user_type = ''; // Kept for backward compatibility
$user_email = '';
$user_roles = []; // NEW: Array of user roles
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
    $user_type = $_SESSION['user_type'] ?? ''; // Kept for backward compatibility
    $first_login = $_SESSION['first_login'] ?? false;
    
    // NEW: Get user roles
    if (isset($_SESSION['user_roles'])) {
        $user_roles = $_SESSION['user_roles'];
    } else {
        // Fetch roles from database if not in session
        $user_roles = getUserRolesById($_SESSION['user_id']);
        $_SESSION['user_roles'] = $user_roles;
    }
    
    // Check if user is admin
    $is_admin = in_array('admin', $user_roles);
}

// Helper functions available throughout the application
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    $roles = $_SESSION['user_roles'] ?? [];
    return in_array('admin', $roles);
}

// NEW: Check if user has a specific role
function hasRole($role) {
    $roles = $_SESSION['user_roles'] ?? [];
    return in_array($role, $roles);
}

// NEW: Check if user has any of the specified roles
function hasAnyRole($roles) {
    $userRoles = $_SESSION['user_roles'] ?? [];
    return !empty(array_intersect($userRoles, $roles));
}

// NEW: Get user roles from database
function getUserRolesById($userId) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT role FROM user_roles WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        error_log("Error fetching user roles: " . $e->getMessage());
        return [];
    }
}

// Backward compatibility - map roles to old user types
function getUserType() {
    $roles = $_SESSION['user_roles'] ?? [];
    
    // Priority order for backward compatibility
    if (in_array('admin', $roles)) return 'admin';
    if (in_array('docente_pos', $roles)) return 'postg_teacher';
    if (in_array('docente', $roles)) return 'teacher';
    if (in_array('tecnico', $roles)) return 'technician';
    if (in_array('interprete', $roles)) return 'interpreter';
    
    return $_SESSION['user_type'] ?? null;
}

function getUserName() {
    return $_SESSION['user_name'] ?? 'Usuário';
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getTypeId() {
    // For backward compatibility with pages that expect type_id
    return $_SESSION['type_id'] ?? $_SESSION['user_id'] ?? null;
}

function isFirstLogin() {
    return $_SESSION['first_login'] ?? false;
}

// NEW: Role-specific check functions
function isTeacher() {
    return hasAnyRole(['docente', 'docente_pos']);
}

function isPostgTeacher() {
    return hasRole('docente_pos');
}

function isTechnician() {
    return hasRole('tecnico');
}

function isInterpreter() {
    return hasRole('interprete');
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

// NEW: Require specific role
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: ' . getBasePath() . '/pages/home.php');
        exit();
    }
}

// NEW: Require any of the specified roles
function requireAnyRole($roles) {
    requireLogin();
    if (!hasAnyRole($roles)) {
        header('Location: ' . getBasePath() . '/pages/home.php');
        exit();
    }
}

// Get base path for redirects
function getBasePath() {
    // Adjust this if your app is in a subdirectory
    return '/credenciamento';
}

// NEW: Get primary role for display/routing
function getPrimaryRole() {
    $roles = $_SESSION['user_roles'] ?? [];
    
    // Priority order
    if (in_array('admin', $roles)) return 'admin';
    if (in_array('docente_pos', $roles)) return 'docente_pos';
    if (in_array('docente', $roles)) return 'docente';
    if (in_array('tecnico', $roles)) return 'tecnico';
    if (in_array('interprete', $roles)) return 'interprete';
    
    return null;
}

// NEW: Get role display name
function getRoleDisplayName($role) {
    $roleNames = [
        'admin' => 'Administrador',
        'docente' => 'Docente',
        'docente_pos' => 'Docente Pós-Graduação',
        'tecnico' => 'Técnico',
        'interprete' => 'Intérprete'
    ];
    
    return $roleNames[$role] ?? $role;
}

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Set default timezone (adjust as needed)
date_default_timezone_set('America/Sao_Paulo');