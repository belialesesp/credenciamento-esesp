<?php
// pages/staff-invitation-response.php
session_start();
require_once '../backend/classes/database.class.php';

// Get parameters
$token = $_GET['token'] ?? '';
$action = $_GET['action'] ?? '';

// Validate parameters
if (empty($token) || !in_array($action, ['accept', 'reject'])) {
    header('Location: ../index.php');
    exit;
}

try {
    $connection = new Database();
    $conn = $connection->connect();

    // Get invitation details with user information
    $stmt = $conn->prepare("
        SELECT si.*, u.name as user_name, u.email as user_email
        FROM staff_invitations si
        JOIN user u ON si.user_id = u.id
        WHERE si.token = :token
        LIMIT 1
    ");

    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $invitation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invitation) {
        $error = 'Convite não encontrado';
    } elseif ($invitation['status'] !== 'pending') {
        $error = 'Este convite já foi respondido';
    } elseif (strtotime($invitation['expires_at']) < time()) {
        $error = 'Este convite expirou';
        // Update status to expired
        $updateStmt = $conn->prepare("UPDATE staff_invitations SET status = 'expired' WHERE id = :id");
        $updateStmt->bindParam(':id', $invitation['id']);
        $updateStmt->execute();
    } else {
        // Process the response
        $newStatus = $action === 'accept' ? 'accepted' : 'rejected';

        // Update invitation status with responded_at
        $updateStmt = $conn->prepare("
            UPDATE staff_invitations 
            SET status = :status, responded_at = NOW() 
            WHERE id = :id
        ");
        $updateStmt->bindParam(':status', $newStatus);
        $updateStmt->bindParam(':id', $invitation['id']);
        $updateStmt->execute();

        // If accepted, update user's status and called_at
        if ($action === 'accept') {
            // Don't set any default contract info - leave it empty for admin to fill

            // Update user's called_at date and enabled status
            $updateUserStmt = $conn->prepare("
                UPDATE user 
                SET called_at = NOW(), enabled = 1
                WHERE id = :user_id
            ");
            $updateUserStmt->bindParam(':user_id', $invitation['user_id']);
            $updateUserStmt->execute();

            error_log("Updated user {$invitation['user_id']} - called_at set, enabled=1 - Response: {$action}");
        } else {
            // For rejected invitations, still update user's called_at but set enabled to 0
            $updateUserStmt = $conn->prepare("
                UPDATE user 
                SET called_at = NOW(), enabled = 0
                WHERE id = :user_id
            ");
            $updateUserStmt->bindParam(':user_id', $invitation['user_id']);
            $updateUserStmt->execute();

            error_log("Updated user {$invitation['user_id']} - called_at set, enabled=0 - Response: {$action}");
        }

        $success = true;
    }
} catch (Exception $e) {
    error_log("Staff invitation response error: " . $e->getMessage());
    $error = 'Erro ao processar resposta';
}

// Get user type in Portuguese for display
$userTypePT = 'Colaborador'; // Default generic term

// Try to get actual role from database for better display
try {
    if (isset($invitation['user_id'])) {
        $connection = new Database();
        $conn = $connection->connect();

        $roleStmt = $conn->prepare("
            SELECT role FROM user_roles WHERE user_id = :user_id LIMIT 1
        ");
        $roleStmt->bindParam(':user_id', $invitation['user_id']);
        $roleStmt->execute();
        $userRole = $roleStmt->fetch(PDO::FETCH_COLUMN);

        if ($userRole === 'tecnico') {
            $userTypePT = 'Técnico';
        } elseif ($userRole === 'interprete') {
            $userTypePT = 'Intérprete';
        }
    }
} catch (Exception $e) {
    // Fallback to default if there's an error
    $userTypePT = 'Colaborador';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resposta ao Convite - Sistema de Credenciamento</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }

        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
        }

        .icon.success {
            background-color: #28a745;
        }

        .icon.error {
            background-color: #dc3545;
        }

        .icon.info {
            background-color: #17a2b8;
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .message {
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 30px;
            text-align: left;
        }

        .details p {
            margin-bottom: 10px;
            color: #555;
        }

        .details strong {
            color: #333;
        }

        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .button:hover {
            background-color: #0056b3;
        }

        .footer {
            margin-top: 40px;
            color: #999;
            font-size: 14px;
        }

        .contract-info {
            background-color: #e8f5e8;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
            border-radius: 4px;
        }

        .contract-info h3 {
            color: #28a745;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php if (isset($success) && $success): ?>
            <?php if ($action === 'accept'): ?>
                <div class="icon success">✓</div>
                <h1>Convite Aceito!</h1>
                <div class="message">
                    <p>Você aceitou com sucesso o convite para a posição de <?= $userTypePT ?>.</p>
                </div>
                <div class="details">
                    <p><strong>Nome:</strong> <?= htmlspecialchars($invitation['user_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($invitation['user_email']) ?></p>
                    <p><strong>Posição:</strong> <?= $userTypePT ?></p>
                    <p><strong>Data de resposta:</strong> <?= date('d/m/Y H:i') ?></p>
                </div>

                <div class="contract-info">
                    <h3>📋 Contrato Confirmado</h3>
                    <p>Seu contrato foi registrado no sistema. Em breve, a administração entrará em contato com os detalhes do seu contrato e próximos passos.</p>
                </div>

                <p class="message">
                    Obrigado por fazer parte da nossa equipe! Sua contribuição é muito valiosa.
                </p>
            <?php else: ?>
                <div class="icon info">!</div>
                <h1>Convite Recusado</h1>
                <div class="message">
                    <p>Você recusou o convite para a posição de <?= $userTypePT ?>.</p>
                </div>
                <div class="details">
                    <p><strong>Nome:</strong> <?= htmlspecialchars($invitation['user_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($invitation['user_email']) ?></p>
                    <p><strong>Posição:</strong> <?= $userTypePT ?></p>
                    <p><strong>Data de resposta:</strong> <?= date('d/m/Y H:i') ?></p>
                </div>
                <p class="message">
                    Agradecemos sua resposta. A administração foi notificada da sua decisão.
                </p>
            <?php endif; ?>
        <?php else: ?>
            <div class="icon error">✗</div>
            <h1>Erro</h1>
            <div class="message">
                <p><?= htmlspecialchars($error ?? 'Ocorreu um erro ao processar sua resposta.') ?></p>
            </div>
        <?php endif; ?>

        <?php
        // Determine appropriate redirect link
        if (isset($_SESSION['user_id'])) {
            // User is logged in
            $redirectLink = '../pages/home.php';
        } else {
            // User is not logged in
            $redirectLink = '../index.php';
        }
        ?>
        <a href="<?= $redirectLink ?>" class="button">Ir para o Sistema</a>

        <div class="footer">
            <p>Sistema de Credenciamento ESESP © <?= date('Y') ?></p>
        </div>
    </div>
</body>

</html>