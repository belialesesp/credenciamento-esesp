<?php
// pages/docente.php - Example of updated page using roles

// Include the updated init.php
require_once '../init.php';
// Include styles and header
echo '<link rel="stylesheet" href="../styles/user.css">';
include '../components/header.php';

require_once '../pdf/assets/title_case.php';
require_once '../backend/services/teacher.service.php';
// Require authentication
requireLogin();

// Require teacher role (either docente or docente_pos)
requireAnyRole(['docente', 'docente_pos']);

// Get user ID from query parameter or session
$userId = $_GET['id'] ?? getUserId();

// Verify the user can only see their own profile (unless admin)
if ($userId != getUserId() && !isAdmin()) {
    header('Location: ' . getBasePath() . '/pages/docente.php?id=' . getUserId());
    exit;
}

// Fetch user data with roles
try {
    $stmt = $conn->prepare("
        SELECT 
            u.*,
            GROUP_CONCAT(ur.role) as roles
        FROM user u
        LEFT JOIN user_roles ur ON u.id = ur.user_id
        WHERE u.id = :id
        GROUP BY u.id
    ");
    $stmt->execute(['id' => $userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        $_SESSION['error'] = 'Usuário não encontrado.';
        header('Location: ' . getBasePath() . '/pages/home.php');
        exit;
    }
    
    // Convert roles string to array
    $userRoles = $userData['roles'] ? explode(',', $userData['roles']) : [];
    
} catch (Exception $e) {
    error_log("Error fetching user data: " . $e->getMessage());
    $_SESSION['error'] = 'Erro ao carregar dados do usuário.';
    header('Location: ' . getBasePath() . '/pages/home.php');
    exit;
}

// Check if user has specific permissions
$isPostgTeacher = in_array('docente_pos', $userRoles);
$canEditAllProfiles = isAdmin();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Docente - <?php echo htmlspecialchars($userData['name']); ?></title>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container">
        <h1>Área do Docente</h1>
        
        <div class="user-info">
            <h2><?php echo htmlspecialchars($userData['name']); ?></h2>
            <p>Email: <?php echo htmlspecialchars($userData['email']); ?></p>
            <p>CPF: <?php echo htmlspecialchars($userData['cpf']); ?></p>
            
            <!-- Show user roles -->
            <div class="roles">
                <h3>Perfis:</h3>
                <ul>
                    <?php foreach ($userRoles as $role): ?>
                        <li><?php echo getRoleDisplayName($role); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Show additional info if available -->
            <?php if (!empty($userData['phone'])): ?>
                <p>Telefone: <?php echo htmlspecialchars($userData['phone']); ?></p>
            <?php endif; ?>
            
            <?php if (!empty($userData['document_number'])): ?>
                <p>Documento: <?php echo htmlspecialchars($userData['document_number']); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Show different options based on roles -->
        <div class="actions">
            <h3>Ações Disponíveis:</h3>
            
            <!-- All teachers can see these -->
            <a href="edit_profile.php" class="btn">Editar Perfil</a>
            <a href="my_courses.php" class="btn">Meus Cursos</a>
            
            <?php if ($isPostgTeacher): ?>
                <!-- Only postgraduate teachers see this -->
                <a href="postgrad_courses.png" class="btn">Cursos de Pós-Graduação</a>
            <?php endif; ?>
            
            <?php if (isAdmin()): ?>
                <!-- Admin-only options -->
                <a href="manage_teacher.php?id=<?php echo $userId; ?>" class="btn btn-admin">Gerenciar Docente</a>
                <a href="view_logs.php?user_id=<?php echo $userId; ?>" class="btn btn-admin">Ver Logs</a>
            <?php endif; ?>
        </div>
        
        <!-- First login message -->
        <?php if (isFirstLogin()): ?>
            <div class="alert alert-info">
                <h4>Bem-vindo!</h4>
                <p>Este é seu primeiro acesso. Por favor, complete seu perfil.</p>
                <a href="complete_profile.php" class="btn btn-primary">Completar Perfil</a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>