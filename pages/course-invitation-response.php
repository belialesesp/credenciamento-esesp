<?php
// pages/course-invitation-response.php
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

    // Get invitation details
    $stmt = $conn->prepare("
        SELECT ci.*, 
               CASE 
                   WHEN ci.teacher_type = 'postgraduate' THEN pt.name 
                   ELSE t.name 
               END as teacher_name,
               CASE 
                   WHEN ci.teacher_type = 'postgraduate' THEN pt.email 
                   ELSE t.email 
               END as teacher_email,
               CASE 
                   WHEN ci.teacher_type = 'postgraduate' THEN pd.name 
                   ELSE d.name 
               END as course_name
        FROM course_invitations ci
        LEFT JOIN teacher t ON ci.teacher_id = t.id AND ci.teacher_type = 'regular'
        LEFT JOIN postg_teacher pt ON ci.teacher_id = pt.id AND ci.teacher_type = 'postgraduate'
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

        // If accepted, update teacher's discipline status
        if ($action === 'accept') {
            // Check if the relationship already exists
            $checkStmt = $conn->prepare("
                SELECT * FROM $table 
                WHERE teacher_id = :teacher_id AND discipline_id = :discipline_id
            ");
            $checkStmt->bindParam(':teacher_id', $invitation['teacher_id']);
            $checkStmt->bindParam(':discipline_id', $invitation['course_id']);
            $checkStmt->execute();

            if ($checkStmt->fetch()) {
                // Update existing record
                $updateStmt = $conn->prepare("
                    UPDATE $table 
                    SET enabled = 1, called_at = NOW() 
                    WHERE teacher_id = :teacher_id AND discipline_id = :discipline_id
                ");
            } else {
                // Insert new record
                $updateStmt = $conn->prepare("
                    INSERT INTO $table (teacher_id, discipline_id, enabled, called_at, created_at) 
                    VALUES (:teacher_id, :discipline_id, 1, NOW(), NOW())
                ");
            }

            $updateStmt->bindParam(':teacher_id', $invitation['teacher_id']);
            $updateStmt->bindParam(':discipline_id', $invitation['course_id']);
            $updateStmt->execute();
        } else {
            // For rejected invitations, still update called_at
            // Check if the relationship exists
            $checkStmt = $conn->prepare("
                SELECT * FROM $table 
                WHERE teacher_id = :teacher_id AND discipline_id = :discipline_id
            ");
            $checkStmt->bindParam(':teacher_id', $invitation['teacher_id']);
            $checkStmt->bindParam(':discipline_id', $invitation['course_id']);
            $checkStmt->execute();

            if ($checkStmt->fetch()) {
                // Update existing record with called_at
                $updateCalledAt = $conn->prepare("
                    UPDATE $table 
                    SET called_at = NOW() 
                    WHERE teacher_id = :teacher_id AND discipline_id = :discipline_id
                ");
            } else {
                // Insert new record with called_at (disabled)
                $updateCalledAt = $conn->prepare("
                    INSERT INTO $table (teacher_id, discipline_id, enabled, called_at, created_at) 
                    VALUES (:teacher_id, :discipline_id, 0, NOW(), NOW())
                ");
            }

            $updateCalledAt->bindParam(':teacher_id', $invitation['teacher_id']);
            $updateCalledAt->bindParam(':discipline_id', $invitation['course_id']);
            $updateCalledAt->execute();
        }

        error_log("Updated called_at for teacher {$invitation['teacher_id']} course {$invitation['course_id']} - Response: {$action}");

        $success = true;
    }
} catch (Exception $e) {
    error_log("Course invitation response error: " . $e->getMessage());
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
    </style>
</head>

<body>
    <div class="container">
        <?php if (isset($success) && $success): ?>
            <?php if ($action === 'accept'): ?>
                <div class="icon success">✓</div>
                <h1>Convite Aceito!</h1>
                <div class="message">
                    <p>Você aceitou com sucesso o convite para lecionar.</p>
                </div>
                <div class="details">
                    <p><strong>Professor:</strong> <?= htmlspecialchars($invitation['teacher_name']) ?></p>
                    <p><strong>Curso:</strong> <?= htmlspecialchars($invitation['course_name']) ?></p>
                </div>
                <p class="message">
                    Você foi cadastrado como apto para este curso.
                    Em breve, a coordenação entrará em contato com mais informações.
                </p>
            <?php else: ?>
                <div class="icon info">!</div>
                <h1>Convite Recusado</h1>
                <div class="message">
                    <p>Você recusou o convite para lecionar.</p>
                </div>
                <div class="details">
                    <p><strong>Professor:</strong> <?= htmlspecialchars($invitation['teacher_name']) ?></p>
                    <p><strong>Curso:</strong> <?= htmlspecialchars($invitation['course_name']) ?></p>
                </div>
                <p class="message">
                    Agradecemos sua resposta. A coordenação foi notificada.
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