<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    $userId = $_POST['user_id'] ?? $_SESSION['user_id'] ?? null;
    $selectedRoles = $_POST['roles'] ?? [];

    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
        exit;
    }

    if (empty($selectedRoles)) {
        echo json_encode(['success' => false, 'message' => 'Nenhuma função selecionada']);
        exit;
    }

    // Add roles to user_roles table
    $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role, created_at) VALUES (?, ?, NOW())");
    
    foreach ($selectedRoles as $role) {
        // Check if user already has this role
        $checkStmt = $pdo->prepare("SELECT id FROM user_roles WHERE user_id = ? AND role = ?");
        $checkStmt->execute([$userId, $role]);
        
        if (!$checkStmt->fetch()) {
            $stmt->execute([$userId, $role]);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Funções adicionadas com sucesso!',
        'redirect_url' => '../pages/home.php'
    ]);

} catch (Exception $e) {
    error_log("Role application error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao processar solicitação']);
}