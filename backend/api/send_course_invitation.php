<?php
// backend/api/send_course_invitation.php
session_start();
require_once '../classes/database.class.php';
require_once '../helpers/email.helper.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

// Validate input
$teacherId = filter_input(INPUT_POST, 'teacher_id', FILTER_VALIDATE_INT);
$courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$teacherEmail = filter_input(INPUT_POST, 'teacher_email', FILTER_VALIDATE_EMAIL);
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (!$teacherId || !$courseId || !$teacherEmail || empty($subject) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

try {
    $connection = new Database();
    $conn = $connection->connect();
    
    // Generate unique invitation token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
    
    // Check if it's postgraduate or regular course
    $isPostgraduate = strpos($_SERVER['HTTP_REFERER'], 'docentes-pos') !== false;
    
    // Get teacher and course details
    if ($isPostgraduate) {
        $teacherTable = 'postg_teacher';
        $courseTable = 'postg_disciplinas';
    } else {
        $teacherTable = 'teacher';
        $courseTable = 'disciplinas';
    }
    
    // Get teacher name
    $stmt = $conn->prepare("SELECT name FROM $teacherTable WHERE id = :id");
    $stmt->bindParam(':id', $teacherId);
    $stmt->execute();
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get course name
    $stmt = $conn->prepare("SELECT name FROM $courseTable WHERE id = :id");
    $stmt->bindParam(':id', $courseId);
    $stmt->execute();
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher || !$course) {
        throw new Exception('Professor ou curso não encontrado');
    }
    
    // Store invitation in database
    $stmt = $conn->prepare("
        INSERT INTO course_invitations 
        (teacher_id, course_id, teacher_type, token, expires_at, status, created_at) 
        VALUES (:teacher_id, :course_id, :teacher_type, :token, :expires, 'pending', NOW())
    ");
    
    $teacherType = $isPostgraduate ? 'postgraduate' : 'regular';
    $stmt->bindParam(':teacher_id', $teacherId);
    $stmt->bindParam(':course_id', $courseId);
    $stmt->bindParam(':teacher_type', $teacherType);
    $stmt->bindParam(':token', $token);
    $stmt->bindParam(':expires', $expires);
    $stmt->execute();
    
    // Prepare email content
    $acceptLink = getInvitationLink($token, 'accept');
    $rejectLink = getInvitationLink($token, 'reject');
    
    $emailBody = createInvitationEmailTemplate(
        $teacher['name'],
        $course['name'],
        $message,
        $acceptLink,
        $rejectLink,
        $expires
    );
    
    // Send email
    $emailSent = sendEmail($teacherEmail, $teacher['name'], $subject, $emailBody);
    
    if ($emailSent) {
        echo json_encode(['success' => true, 'message' => 'Convite enviado com sucesso']);
        
        // Log the invitation
        error_log("Course invitation sent - Teacher: {$teacher['name']}, Course: {$course['name']}");
    } else {
        // Remove invitation record if email failed
        $stmt = $conn->prepare("DELETE FROM course_invitations WHERE token = :token");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        echo json_encode(['success' => false, 'message' => 'Erro ao enviar email']);
    }
    
} catch (Exception $e) {
    error_log("Course invitation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro no sistema']);
}

/**
 * Generate invitation action link
 */
function getInvitationLink($token, $action) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $basePath = dirname(dirname(dirname($_SERVER['REQUEST_URI'])));
    
    return $protocol . '://' . $host . $basePath . '/pages/course-invitation-response.php?token=' . $token . '&action=' . $action;
}

/**
 * Create invitation email template
 */
function createInvitationEmailTemplate($teacherName, $courseName, $message, $acceptLink, $rejectLink, $expires) {
    $expiresFormatted = date('d/m/Y \à\s H:i', strtotime($expires));
    
    return "
    <html>
    <head>
        <title>Convite para Curso</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border: 1px solid #dee2e6; border-radius: 0 0 5px 5px; }
            .course-info { background: #fff; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #e9ecef; }
            .message-box { background: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .buttons { text-align: center; margin: 30px 0; }
            .button { display: inline-block; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 0 10px; font-weight: bold; }
            .accept { background: #28a745; color: white; }
            .reject { background: #dc3545; color: white; }
            .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #6c757d; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Convite para Lecionar</h2>
            </div>
            <div class='content'>
                <p>Prezado(a) <strong>{$teacherName}</strong>,</p>
                
                <div class='course-info'>
                    <h3 style='margin-top: 0;'>Detalhes do Curso:</h3>
                    <p><strong>Curso:</strong> {$courseName}</p>
                </div>
                
                <div class='message-box'>
                    <p>" . nl2br(htmlspecialchars($message)) . "</p>
                </div>
                
                <p>Por favor, clique em um dos botões abaixo para responder a este convite:</p>
                
                <div class='buttons'>
                    <a href='{$acceptLink}' class='button accept'>✓ Aceitar Convite</a>
                    <a href='{$rejectLink}' class='button reject'>✗ Recusar Convite</a>
                </div>
                
                <p><strong>Este convite expira em {$expiresFormatted}.</strong></p>
                
                <div class='footer'>
                    <p>Este é um email automático. Por favor, não responda.</p>
                    <p>Sistema de Credenciamento ESESP © " . date('Y') . "</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
}