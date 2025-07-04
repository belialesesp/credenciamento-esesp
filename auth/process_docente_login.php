<?php
session_start();
require_once '../backend/classes/database.class.php';

function processDocenteLogin($cpf, $docenteType) {
    $response = [
        'success' => false,
        'message' => ''
    ];

    try {
        $connection = new Database();
        $conn = $connection->connect();

        // Clean CPF (remove formatting)
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        // Determine which table to check based on docente type
        if ($docenteType === 'regular') {
            $table = 'teacher';
            $profilePage = 'docente.php';
        } else {
            $table = 'postg_teacher';
            $profilePage = 'docente-pos.php';
        }

        // Query to find teacher by CPF
        $sql = "SELECT id, name, cpf FROM $table WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = :cpf LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':cpf', $cpf);
        $stmt->execute();

        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($teacher) {
            // Set session variables
            $_SESSION['docente_id'] = $teacher['id'];
            $_SESSION['docente_name'] = $teacher['name'];
            $_SESSION['docente_cpf'] = $teacher['cpf'];
            $_SESSION['docente_type'] = $docenteType === 'regular' ? 'regular' : 'postgraduate';
            
            $response['success'] = true;
            $response['message'] = 'Login realizado com sucesso!';
            $response['redirect'] = '../pages/' . $profilePage . '?id=' . $teacher['id'];
        } else {
            $response['message'] = 'CPF não encontrado no sistema de docentes.';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Erro ao processar login: ' . $e->getMessage();
    }

    return $response;
}

// Process POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cpf = $_POST['cpf'] ?? '';
    $docenteType = $_POST['docente_type'] ?? 'regular';
    
    // Validate CPF format
    if (empty($cpf)) {
        $_SESSION['login_error'] = 'Por favor, informe o CPF.';
        header('Location: ../pages/login_docente.php');
        exit;
    }
    
    $result = processDocenteLogin($cpf, $docenteType);
    
    if ($result['success']) {
        header('Location: ' . $result['redirect']);
        exit;
    } else {
        $_SESSION['login_error'] = $result['message'];
        header('Location: ../pages/login_docente.php');
        exit;
    }
} else {
    // Redirect if accessed directly
    header('Location: ../pages/login_docente.php');
    exit;
}