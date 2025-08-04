<?php
// pages/home.php - Updated dashboard with role-based content
function translateUserType($user_type) {
    $types = [
        'admin' => 'Administrador',
        'teacher' => 'Docente',
        'postg_teacher' => 'Docente Pós-Graduação',
        'technician' => 'Técnico',
        'interpreter' => 'Intérprete'
    ];
    return $types[$user_type] ?? $user_type;
}
require_once '../init.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit();
}

include_once('../components/header.php');

$user_type = $_SESSION['user_type'] ?? '';
$user_name = $_SESSION['user_name'] ?? 'Usuário';
$type_id = $_SESSION['type_id'] ?? null;
?>

<div class="container">
    <h1 class="main-title">Painel de Controle</h1>
    
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="alert alert-info">
                <strong>Bem-vindo(a), <?= htmlspecialchars($user_name) ?>!</strong><br>
                Tipo de usuário: <?= translateUserType($user_type) ?>
            </div>
        </div>
    </div>
    
    <?php if (isAdmin()): ?>
    <!-- Admin Dashboard -->
    <div class="row mt-4">
        <!-- Docentes Management -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Gerenciar Docentes</h5>
                </div>
                <div class="card-body">
                    <p>Visualize e gerencie todos os docentes cadastrados.</p>
                    <a href="docentes.php" class="btn btn-primary">Ver Docentes Regulares</a>
                    <a href="docentes-pos.php" class="btn btn-info">Ver Docentes Pós-Graduação</a>
                </div>
            </div>
        </div>
        
        <!-- Other Management -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Outras Gestões</h5>
                </div>
                <div class="card-body">
                    <p>Gerencie outros tipos de usuários.</p>
                    <a href="tecnicos.php" class="btn btn-success mb-2">Ver Técnicos</a>
                    <a href="interpretes.php" class="btn btn-warning mb-2">Ver Intérpretes</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistics for Admin -->
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-primary">
                        <?php
                        $stmt = $conn->query("SELECT COUNT(*) FROM user WHERE user_type = 'teacher'");
                        echo $stmt->fetchColumn();
                        ?>
                    </h3>
                    <p class="mb-0">Docentes Regulares</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-info">
                        <?php
                        $stmt = $conn->query("SELECT COUNT(*) FROM user WHERE user_type = 'postg_teacher'");
                        echo $stmt->fetchColumn();
                        ?>
                    </h3>
                    <p class="mb-0">Docentes Pós-Graduação</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-success">
                        <?php
                        $stmt = $conn->query("SELECT COUNT(*) FROM user WHERE user_type = 'technician'");
                        echo $stmt->fetchColumn();
                        ?>
                    </h3>
                    <p class="mb-0">Técnicos</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-warning">
                        <?php
                        $stmt = $conn->query("SELECT COUNT(*) FROM user WHERE user_type = 'interpreter'");
                        echo $stmt->fetchColumn();
                        ?>
                    </h3>
                    <p class="mb-0">Intérpretes</p>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Non-admin users -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Suas Opções</h5>
                </div>
                <div class="card-body">
                    <?php if ($user_type === 'teacher'): ?>
                        <p>Acesse seu perfil para visualizar suas informações e documentos.</p>
                        <a href="docente.php?id=<?= $type_id ?>" class="btn btn-primary">Meu Perfil</a>
                    <?php elseif ($user_type === 'postg_teacher'): ?>
                        <p>Acesse seu perfil para visualizar suas informações e documentos.</p>
                        <a href="docente-pos.php?id=<?= $type_id ?>" class="btn btn-primary">Meu Perfil</a>
                    <?php elseif ($user_type === 'technician'): ?>
                        <p>Acesse seu perfil para visualizar suas informações.</p>
                        <?php if (file_exists('tecnico.php')): ?>
                            <a href="tecnico.php?id=<?= $type_id ?>" class="btn btn-primary">Meu Perfil</a>
                        <?php else: ?>
                            <p class="text-muted">Página de perfil em desenvolvimento.</p>
                        <?php endif; ?>
                    <?php elseif ($user_type === 'interpreter'): ?>
                        <p>Acesse seu perfil para visualizar suas informações.</p>
                        <?php if (file_exists('interprete.php')): ?>
                            <a href="interprete.php?id=<?= $type_id ?>" class="btn btn-primary">Meu Perfil</a>
                        <?php else: ?>
                            <p class="text-muted">Página de perfil em desenvolvimento.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if ($_SESSION['first_login'] ?? false): ?>
                    <div class="alert alert-warning mt-3">
                        <strong>Primeiro Acesso Detectado!</strong><br>
                        Por segurança, recomendamos que você altere sua senha no seu perfil.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Links for non-admin -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Links Úteis</h5>
                </div>
                <div class="card-body">
                    <ul>
                        <li><a href="https://esesp.es.gov.br/">Portal ESESP</a></li>
                        <li><a href="../auth/logout.php">Sair do Sistema</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
include_once('../components/footer.php');
?>