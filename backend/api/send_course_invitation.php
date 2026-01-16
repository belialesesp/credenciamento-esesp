<?php
// backend/api/send_course_invitation.php
session_start();
require_once '../classes/database.class.php';
require_once '../helpers/email.helper.php';

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$isAdmin = false;
if (isset($_SESSION['user_roles']) && is_array($_SESSION['user_roles'])) {
  $isAdmin = in_array('admin', $_SESSION['user_roles']);
}

// Validate input
$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$userEmail = filter_input(INPUT_POST, 'teacher_email', FILTER_VALIDATE_EMAIL);
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// New fields for staff invitations
$userType = trim($_POST['user_type'] ?? ''); // 'technician', 'interpreter', or empty for teachers
$courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT); // Only for teachers
$teacherType = trim($_POST['teacher_type'] ?? 'regular'); // 'regular' or 'postgraduate'

// Debug: Log all extracted values INCLUDING the raw POST data
error_log("DEBUG: Raw POST data: " . print_r($_POST, true));
error_log("DEBUG: Extracted values - user_id: $userId, teacher_email: $userEmail, user_type: '$userType', course_id: $courseId, teacher_type: '$teacherType'");

// For teachers, course ID is required
if (empty($userType) && !$courseId) {
    error_log("DEBUG: Course ID is required for teachers but not provided");
    echo json_encode(['success' => false, 'message' => 'ID do curso é obrigatório para professores']);
    exit;
}

if (!$userId) {
    error_log("DEBUG: Invalid user_id: " . ($_POST['user_id'] ?? 'NULL'));
    echo json_encode(['success' => false, 'message' => 'ID do usuário inválido']);
    exit;
}

if (!$userEmail) {
    error_log("DEBUG: Invalid teacher_email: " . ($_POST['teacher_email'] ?? 'NULL'));
    // We'll continue anyway since we'll get the email from the database
    error_log("DEBUG: Will try to get email from database");
}

try {
    $connection = new Database();
    $conn = $connection->connect();

    // Generate unique invitation token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+7 days'));

    $userName = '';
    $invitationDetails = '';

    // Get user details
    $stmt = $conn->prepare("
        SELECT u.name, u.email 
        FROM user u 
        WHERE u.id = :id
    ");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("DEBUG: User query result: " . print_r($user, true));

    if (!$user) {
        throw new Exception('Usuário não encontrado no banco de dados');
    }

    $userName = $user['name'];
    $userEmail = $user['email']; // Use email from database
    
    error_log("DEBUG: Using email from database: $userEmail");

    if (empty($userType)) {
        // Handle teacher invitation
        // FIXED: Check if user has the appropriate role
        // The issue was that teacher_type might be 'postgraduate' but we need 'docente_pos'
        $requiredRole = ($teacherType === 'postgraduate') ? 'docente_pos' : 'docente';
        
        error_log("DEBUG: Checking for role '$requiredRole' for user_id $userId (teacher_type: '$teacherType')");
        
        // First, let's check what roles the user actually has
        $debugStmt = $conn->prepare("
            SELECT role 
            FROM user_roles 
            WHERE user_id = :user_id
        ");
        $debugStmt->bindParam(':user_id', $userId);
        $debugStmt->execute();
        $userRoles = $debugStmt->fetchAll(PDO::FETCH_COLUMN);
        error_log("DEBUG: User $userId has these roles: " . implode(', ', $userRoles));
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) as has_role 
            FROM user_roles 
            WHERE user_id = :user_id AND role = :role
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':role', $requiredRole);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("DEBUG: Role check result for '$requiredRole': " . print_r($result, true));
        
        if (!$result || $result['has_role'] == 0) {
            throw new Exception("Usuário não tem a função de professor necessária (esperado: '$requiredRole', usuário tem: " . implode(', ', $userRoles) . ")");
        }

        // Get course name
        $courseTable = ($teacherType === 'postgraduate') ? 'postg_disciplinas' : 'disciplinas';
        error_log("DEBUG: Looking for course in table: $courseTable with id: $courseId");
        
        $stmt = $conn->prepare("SELECT name FROM $courseTable WHERE id = :id");
        $stmt->bindParam(':id', $courseId);
        $stmt->execute();
        $course = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$course) {
            throw new Exception('Curso não encontrado');
        }
        
        $invitationDetails = $course['name'];

        // Store invitation in database
        $stmt = $conn->prepare("
            INSERT INTO course_invitations 
            (user_id, course_id, teacher_type, token, expires_at, status, created_at) 
            VALUES (:user_id, :course_id, :teacher_type, :token, :expires, 'pending', NOW())
        ");

        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':course_id', $courseId);
        $stmt->bindParam(':teacher_type', $teacherType);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires', $expires);
        $stmt->execute();
        
        error_log("DEBUG: Course invitation stored successfully");
    } else if ($userType === 'technician') {
        // Check if user has tecnico role
        $stmt = $conn->prepare("
            SELECT COUNT(*) as has_role 
            FROM user_roles 
            WHERE user_id = :user_id AND role = 'tecnico'
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("DEBUG: Technician role check result: " . print_r($result, true));
        
        if (!$result || $result['has_role'] == 0) {
            throw new Exception('Usuário não tem a função de técnico');
        }

        $invitationDetails = 'Técnico';

        // Store invitation in staff_invitations table
        $stmt = $conn->prepare("
            INSERT INTO staff_invitations (user_id, token, status, expires_at) 
            VALUES (:user_id, :token, 'pending', :expires_at)
        ");

        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires_at', $expires);
        $stmt->execute();

        $invitationId = $conn->lastInsertId();
        error_log("DEBUG: Created staff invitation with ID: $invitationId for user_id: $userId, user_type: $userType");
    } else if ($userType === 'interpreter') {
        // Check if user has interprete role
        $stmt = $conn->prepare("
            SELECT COUNT(*) as has_role 
            FROM user_roles 
            WHERE user_id = :user_id AND role = 'interprete'
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("DEBUG: Interpreter role check result: " . print_r($result, true));
        
        if (!$result || $result['has_role'] == 0) {
            throw new Exception('Usuário não tem a função de intérprete');
        }

        $invitationDetails = 'Intérprete';

        // Store invitation in staff_invitations table
        $stmt = $conn->prepare("
            INSERT INTO staff_invitations (user_id, token, status, expires_at) 
            VALUES (:user_id, :token, 'pending', :expires_at)
        ");

        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires_at', $expires);
        $stmt->execute();
        
        error_log("DEBUG: Interpreter invitation stored successfully");
    }

    // Prepare email content
    $acceptLink = getInvitationLink($token, 'accept', $userType);
    $rejectLink = getInvitationLink($token, 'reject', $userType);

    $emailBody = createInvitationEmailTemplate(
        $userName,
        $invitationDetails,
        $message,
        $acceptLink,
        $rejectLink,
        $expires,
        !empty($userType) // isStaff flag
    );

    // Send email
    error_log("DEBUG: Attempting to send email to: $userEmail");
    $emailSent = sendEmail($userEmail, $userName, $subject, $emailBody);

    // After sending the email successfully, update sent_at
    if ($emailSent) {
        // Update sent_at timestamp
        $table = empty($userType) ? 'course_invitations' : 'staff_invitations';
        $updateStmt = $conn->prepare("
            UPDATE $table 
            SET sent_at = NOW() 
            WHERE token = :token
        ");
        $updateStmt->bindParam(':token', $token);
        $updateStmt->execute();

        $logMessage = empty($userType)
            ? "Course invitation sent - Teacher: {$userName}, Course: {$invitationDetails}, Type: {$teacherType}"
            : "Staff invitation sent - {$userType}: {$userName}";

        error_log($logMessage);
        echo json_encode(['success' => true, 'message' => 'Convite enviado com sucesso']);
    } else {
        // Remove invitation record if email failed
        $table = empty($userType) ? 'course_invitations' : 'staff_invitations';
        $stmt = $conn->prepare("DELETE FROM $table WHERE token = :token");
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        error_log("DEBUG: Email sending failed");
        echo json_encode(['success' => false, 'message' => 'Erro ao enviar email']);
    }
} catch (Exception $e) {
    error_log("DEBUG: Exception caught: " . $e->getMessage());
    error_log("DEBUG: Exception trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'Erro no sistema: ' . $e->getMessage()]);
}

/**
 * Generate invitation action link
 */
function getInvitationLink($token, $action, $userType = '')
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $basePath = dirname(dirname(dirname($_SERVER['REQUEST_URI'])));

    $page = empty($userType)
        ? 'course-invitation-response.php'
        : 'staff-invitation-response.php';

    return $protocol . '://' . $host . $basePath . '/pages/' . $page . '?token=' . $token . '&action=' . $action;
}

/**
 * Create invitation email template
 */
function createInvitationEmailTemplate($userName, $details, $message, $acceptLink, $rejectLink, $expires, $isStaff = false)
{
    $expiresFormatted = date('d/m/Y \à\s H:i', strtotime($expires));
    $invitationType = $isStaff ? 'trabalho' : 'lecionar no curso';

    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #0066cc; color: white; padding: 20px; text-align: center; }
            .content { background-color: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
            .message { background-color: white; padding: 20px; margin: 20px 0; border-left: 4px solid #0066cc; }
            .button-container { text-align: center; margin: 30px 0; }
            .button { display: inline-block; padding: 12px 30px; margin: 0 10px; text-decoration: none; border-radius: 5px; font-weight: bold; }
            .accept { background-color: #28a745; color: white; }
            .reject { background-color: #dc3545; color: white; }
            .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; }
            .warning { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Convite para {$invitationType}</h1>
            </div>
            <div class='content'>
                <p>Prezado(a) <strong>{$userName}</strong>,</p>
                
                <div class='message'>
                    " . nl2br(htmlspecialchars($message)) . "
                </div>
                
                <p><strong>Detalhes:</strong> {$details}</p>
                
                <div class='warning'>
                    <strong>⚠️ Importante:</strong> Este convite expira em <strong>{$expiresFormatted}</strong>
                </div>
                
                <div class='button-container'>
                    <a href='{$acceptLink}' class='button accept'>✓ Aceitar Convite</a>
                    <a href='{$rejectLink}' class='button reject'>✗ Recusar Convite</a>
                </div>
                
                <p style='text-align: center; color: #666; font-size: 14px;'>
                    Por favor, clique em um dos botões acima para responder a este convite.
                </p>
            </div>
            <div class='footer'>
                <p>Esta é uma mensagem automática, por favor não responda.</p>
                <p>Sistema de Credenciamento ESESP © " . date('Y') . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
}