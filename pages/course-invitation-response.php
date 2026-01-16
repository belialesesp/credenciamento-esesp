<?php
// pages/course-invitation-response.php - FIXED FOR UNIFIED USER SYSTEM
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

    // FIXED: Get invitation details using user table (not old teacher/postg_teacher tables)
    $stmt = $conn->prepare("
        SELECT ci.*, 
               u.name as teacher_name,
               u.email as teacher_email,
               CASE 
                   WHEN ci.teacher_type = 'postgraduate' THEN pd.name 
                   ELSE d.name 
               END as course_name
        FROM course_invitations ci
        JOIN user u ON ci.user_id = u.id
        LEFT JOIN disciplinas d ON ci.course_id = d.id AND ci.teacher_type = 'regular'
        LEFT JOIN postg_disciplinas pd ON ci.course_id = pd.id AND ci.teacher_type = 'postgraduate'
        WHERE ci.token = :token
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
        $updateStmt = $conn->prepare("UPDATE course_invitations SET status = 'expired' WHERE id = :id");
        $updateStmt->bindParam(':id', $invitation['id']);
        $updateStmt->execute();
    } else {
        // Process the response
        $newStatus = $action === 'accept' ? 'accepted' : 'rejected';

        // Update invitation status
        $updateStmt = $conn->prepare("
            UPDATE course_invitations 
            SET status = :status, responded_at = NOW() 
            WHERE id = :id
        ");
        $updateStmt->bindParam(':status', $newStatus);
        $updateStmt->bindParam(':id', $invitation['id']);
        $updateStmt->execute();

        // Determine the table based on teacher type
        if ($invitation['teacher_type'] === 'postgraduate') {
            $table = 'postg_teacher_disciplines';
        } else {
            $table = 'teacher_disciplines';
        }

        // FIXED: Use user_id instead of teacher_id
        $user_id = $invitation['user_id'];
        $course_id = $invitation['course_id'];

        // If accepted, update teacher's discipline status
        if ($action === 'accept') {
            // Check if the relationship already exists
            $checkStmt = $conn->prepare("
                SELECT * FROM $table 
                WHERE user_id = :user_id AND discipline_id = :discipline_id
            ");
            $checkStmt->bindParam(':user_id', $user_id);
            $checkStmt->bindParam(':discipline_id', $course_id);
            $checkStmt->execute();

            if ($checkStmt->fetch()) {
                // Update existing record
                $updateStmt = $conn->prepare("
                    UPDATE $table 
                    SET enabled = 1, called_at = NOW() 
                    WHERE user_id = :user_id AND discipline_id = :discipline_id
                ");
            } else {
                // Insert new record
                $updateStmt = $conn->prepare("
                    INSERT INTO $table (user_id, discipline_id, enabled, called_at, created_at) 
                    VALUES (:user_id, :discipline_id, 1, NOW(), NOW())
                ");
            }

            $updateStmt->bindParam(':user_id', $user_id);
            $updateStmt->bindParam(':discipline_id', $course_id);
            $updateStmt->execute();
            
            error_log("Accepted invitation - Updated $table for user_id: $user_id, discipline_id: $course_id");
        } else {
            // For rejected invitations, still update called_at
            // Check if the relationship exists
            $checkStmt = $conn->prepare("
                SELECT * FROM $table 
                WHERE user_id = :user_id AND discipline_id = :discipline_id
            ");
            $checkStmt->bindParam(':user_id', $user_id);
            $checkStmt->bindParam(':discipline_id', $course_id);
            $checkStmt->execute();

            if ($checkStmt->fetch()) {
                // Update existing record with called_at
                $updateCalledAt = $conn->prepare("
                    UPDATE $table 
                    SET called_at = NOW() 
                    WHERE user_id = :user_id AND discipline_id = :discipline_id
                ");
            } else {
                // Insert new record with called_at (disabled)
                $updateCalledAt = $conn->prepare("
                    INSERT INTO $table (user_id, discipline_id, enabled, called_at, created_at) 
                    VALUES (:user_id, :discipline_id, 0, NOW(), NOW())
                ");
            }

            $updateCalledAt->bindParam(':user_id', $user_id);
            $updateCalledAt->bindParam(':discipline_id', $course_id);
            $updateCalledAt->execute();
            
            error_log("Rejected invitation - Updated $table for user_id: $user_id, discipline_id: $course_id");
        }

        error_log("Updated called_at for user $user_id course $course_id - Response: {$action}");

        $success = true;
    }
} catch (Exception $e) {
    error_log("Course invitation response error: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    $error = 'Erro ao processar resposta';
}

// Display the response page
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
        }

        .icon.success {
            background-color: #d4edda;
            color: #28a745;
        }

        .icon.error {
            background-color: #f8d7da;
            color: #dc3545;
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }

        p {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #0066cc;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .button:hover {
            background-color: #0052a3;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php if (isset($success) && $success): ?>
            <div class="icon success">✓</div>
            <h1><?= $action === 'accept' ? 'Convite Aceito!' : 'Convite Recusado' ?></h1>
            <p>
                <?php if ($action === 'accept'): ?>
                    Obrigado por aceitar o convite para lecionar <strong><?= htmlspecialchars($invitation['course_name']) ?></strong>.<br>
                    Em breve você receberá mais informações sobre o curso.
                <?php else: ?>
                    Sua recusa foi registrada. Agradecemos por responder ao convite.
                <?php endif; ?>
            </p>
            <a href="../index.php" class="button">Ir para o Sistema</a>
        <?php else: ?>
            <div class="icon error">✗</div>
            <h1>Erro</h1>
            <p><?= htmlspecialchars($error ?? 'Erro ao processar resposta') ?></p>
            <a href="../index.php" class="button">Voltar ao Início</a>
        <?php endif; ?>

        <div class="footer">
            Sistema de Credenciamento ESESP © <?= date('Y') ?>
        </div>
    </div>
</body>

</html>