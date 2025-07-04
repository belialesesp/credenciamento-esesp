<?php
// auth/check_docente_access.php
// Include this file at the top of docente.php and docente-pos.php pages

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user can access a specific docente page
 * 
 * @param int $requested_id The teacher ID being requested
 * @param string $page_type 'teacher' or 'postg_teacher'
 * @return bool
 */
function canAccessDocentePage($requested_id, $page_type) {
    // Check if internal user (admin) is logged in
    if (isset($_SESSION['user_id'])) {
        // Internal users can access all pages
        return true;
    }
    
    // Check if docente is logged in
    if (isset($_SESSION['docente_id']) && isset($_SESSION['docente_type'])) {
        // Docentes can only access their own profile
        if ($_SESSION['docente_id'] == $requested_id && $_SESSION['docente_type'] == $page_type) {
            return true;
        }
    }
    
    return false;
}

/**
 * Protect a docente page
 * 
 * @param string $page_type 'teacher' or 'postg_teacher'
 */
function protectDocentePage($page_type) {
    // Get the requested teacher ID
    $requested_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$requested_id) {
        header('Location: ../pages/docente_login.php');
        exit();
    }
    
    if (!canAccessDocentePage($requested_id, $page_type)) {
        // Not authorized - redirect to appropriate login page
        if (isset($_SESSION['user_id'])) {
            // Already logged in as admin but trying to access wrong type
            header('Location: ../pages/home.php');
        } else {
            // Not logged in or logged in as wrong docente
            header('Location: ../pages/docente_login.php');
        }
        exit();
    }
    
    return $requested_id;
}