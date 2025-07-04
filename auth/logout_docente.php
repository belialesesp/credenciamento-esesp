<?php
session_start();

// Clear docente-specific session variables
unset($_SESSION['docente_id']);
unset($_SESSION['docente_name']);
unset($_SESSION['docente_cpf']);
unset($_SESSION['docente_type']);

// Redirect to docente login page
header('Location: ../pages/login_docente.php');
exit;