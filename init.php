<?php
// init.php - Core initialization file with GEDTH role support
session_start();
require_once __DIR__ . '/backend/classes/database.class.php';

// Initialize database connection
$database = new Database();
$conn = $database->connect();

// Initialize variables from session
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? 'Usuário';
$user_type = $_SESSION['user_type'] ?? null;
$navbar = true; // Set to false on pages where navbar shouldn't appear

// User authentication check
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

// Role-based permission checks
function hasRole($role) {
    $userRoles = $_SESSION['user_roles'] ?? [];
    return in_array($role, $userRoles);
}

function hasAnyRole($roles) {
    $userRoles = $_SESSION['user_roles'] ?? [];
    return !empty(array_intersect($userRoles, $roles));
}

// UPDATED: Separate admin and administrative role checks
function isMainAdmin() {
    // Only the main admin role
    return hasRole('admin');
}

function isAdmin() {
    // ONLY checks for the main admin role
    // Use this for general admin access (dashboard, lists, stats)
    return hasRole('admin') || ($_SESSION['user_type'] === 'admin');
}

function isAdministrativeRole() {
    // Check if user has ANY administrative role (admin, gese, gedth, pedagogico)
    // Use this when you need to give access to all administrative staff
    return hasAnyRole(['admin', 'gese', 'gedth', 'pedagogico']);
}

// GEDTH role function
function isGEDTH() {
    return hasRole('gedth');
}

function isGESE() {
    return hasRole('gese');
}

function isPedagogico() {
    return hasRole('pedagogico');
}

function canEvaluateDocuments() {
    return hasAnyRole(['admin', 'gese']);
}

function canEvaluatePedagogy() {
    return hasAnyRole(['admin', 'pedagogico']);
}

// NEW: Permission functions for GEDTH role
function canSendInvites() {
    // Only admin and GEDTH can send invites
    return hasAnyRole(['admin', 'gedth']);
}

function canViewContractInfo() {
    // Only admin and GESE can view/edit contract information
    return hasAnyRole(['admin', 'gese']);
}

// Get user roles from database
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
    if (in_array('gedth', $roles)) return 'gedth';
    if (in_array('gese', $roles)) return 'gese';
    if (in_array('pedagogico', $roles)) return 'pedagogico';
    if (in_array('docente_pos', $roles)) return 'postg_teacher';
    if (in_array('docente', $roles)) return 'teacher';
    if (in_array('tecnico', $roles)) return 'technician';
    if (in_array('interprete', $roles)) return 'interpreter';
    
    return $_SESSION['user_type'] ?? null;
}

// Helper function for user type translation
function translateUserType($user_type) {
    $types = [
        'admin' => 'Administrador',
        'gedth' => 'GEDTH',
        'gese' => 'GESE',
        'pedagogico' => 'Pedagógico',
        'teacher' => 'Docente',
        'postg_teacher' => 'Docente Pós-Graduação',
        'technician' => 'Técnico',
        'interpreter' => 'Intérprete',
        'docente' => 'Docente',
        'docente_pos' => 'Docente Pós-Graduação',
        'tecnico' => 'Técnico',
        'interprete' => 'Intérprete'
    ];
    return $types[$user_type] ?? $user_type;
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

// Role-specific check functions
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

// Require specific role
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: ' . getBasePath() . '/pages/home.php');
        exit();
    }
}

// Require any of the specified roles
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
    return '/credenciamento-esesp';
}

// Get primary role for display/routing
function getPrimaryRole() {
    $roles = $_SESSION['user_roles'] ?? [];

    // Priority order
    if (in_array('admin', $roles)) return 'admin';
    if (in_array('gedth', $roles)) return 'gedth';
    if (in_array('gese', $roles)) return 'gese';
    if (in_array('pedagogico', $roles)) return 'pedagogico';
    if (in_array('docente_pos', $roles)) return 'docente_pos';
    if (in_array('docente', $roles)) return 'docente';
    if (in_array('tecnico', $roles)) return 'tecnico';
    if (in_array('interprete', $roles)) return 'interprete';

    return null;
}

// Get role display name
function getRoleDisplayName($role) {
    $roleNames = [
        'admin' => 'Administrador',
        'gedth' => 'GEDTH',
        'gese' => 'GESE',
        'pedagogico' => 'Pedagógico',
        'docente' => 'Docente',
        'docente_pos' => 'Docente Pós-Graduação',
        'tecnico' => 'Técnico',
        'interprete' => 'Intérprete'
    ];

    return $roleNames[$role] ?? ucfirst($role);
}
?>