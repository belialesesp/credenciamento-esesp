<?php
// pages/home.php - Updated dashboard with role-based content
require_once '../init.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit();
}

include_once('../components/header.php');

// Get user roles from database instead of user_type
$user_roles = [];
$user_name = $_SESSION['user_name'] ?? 'Usuário';
$user_id = $_SESSION['user_id'] ?? null;

// Fetch user roles from user_roles table
if ($user_id) {
    try {
        $stmt = $conn->prepare("SELECT role FROM user_roles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user_roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch(Exception $e) {
        // Handle error or set default empty array
        $user_roles = [];
    }
}

// Check if user is admin
$is_admin = in_array('admin', $user_roles);

// Get primary role for display (or use the first role)
$primary_role = !empty($user_roles) ? $user_roles[0] : 'user';
?>

<div class="container">
    <h1 class="main-title">Painel de Controle</h1>
    
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="alert alert-info">
                <strong>Bem-vindo(a), <?= htmlspecialchars($user_name) ?>!</strong><br>
                <?php if (!empty($user_roles)): ?>
                    Tipo(s) de usuário: <?= implode(', ', array_map('translateUserType', $user_roles)) ?>
                <?php else: ?>
                    Tipo de usuário: Não definido
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if ($is_admin): ?>
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
                        try {
                            $stmt = $conn->query("SELECT COUNT(DISTINCT u.id) FROM user u INNER JOIN user_roles ur ON u.id = ur.user_id WHERE ur.role = 'docente' AND u.enabled = 1");
                            echo $stmt->fetchColumn();
                        } catch(Exception $e) {
                            echo "0";
                        }
                        ?>
                    </h3>
                    <p>Docentes Regulares</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-info">
                        <?php
                        try {
                            $stmt = $conn->query("SELECT COUNT(DISTINCT u.id) FROM user u INNER JOIN user_roles ur ON u.id = ur.user_id WHERE ur.role = 'docente_pos' AND u.enabled = 1");
                            echo $stmt->fetchColumn();
                        } catch(Exception $e) {
                            echo "0";
                        }
                        ?>
                    </h3>
                    <p>Docentes Pós-Graduação</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-success">
                        <?php
                        try {
                            $stmt = $conn->query("SELECT COUNT(DISTINCT u.id) FROM user u INNER JOIN user_roles ur ON u.id = ur.user_id WHERE ur.role = 'tecnico' AND u.enabled = 1");
                            echo $stmt->fetchColumn();
                        } catch(Exception $e) {
                            echo "0";
                        }
                        ?>
                    </h3>
                    <p>Técnicos</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-warning">
                        <?php
                        try {
                            $stmt = $conn->query("SELECT COUNT(DISTINCT u.id) FROM user u INNER JOIN user_roles ur ON u.id = ur.user_id WHERE ur.role = 'interprete' AND u.enabled = 1");
                            echo $stmt->fetchColumn();
                        } catch(Exception $e) {
                            echo "0";
                        }
                        ?>
                    </h3>
                    <p>Intérpretes</p>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Regular User Dashboard -->
    <div class="row mt-4">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Meu Perfil</h5>
                </div>
                <div class="card-body">
                    <p>Visualize e edite suas informações pessoais.</p>
                    <?php 
                    // Check user roles to determine profile link
                    if (in_array('docente', $user_roles)) {
                        echo '<a href="docente.php?id=' . $user_id . '" class="btn btn-primary">Ver Meu Perfil</a>';
                    } elseif (in_array('docente_pos', $user_roles)) {
                        echo '<a href="docente-pos.php?id=' . $user_id . '" class="btn btn-primary">Ver Meu Perfil</a>';
                    } elseif (in_array('tecnico', $user_roles)) {
                        echo '<a href="tecnico.php?id=' . $user_id . '" class="btn btn-primary">Ver Meu Perfil</a>';
                    } elseif (in_array('interprete', $user_roles)) {
                        echo '<a href="interprete.php?id=' . $user_id . '" class="btn btn-primary">Ver Meu Perfil</a>';
                    } else {
                        echo '<a href="profile.php?id=' . $user_id . '" class="btn btn-primary">Ver Meu Perfil</a>';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Configurações</h5>
                </div>
                <div class="card-body">
                    <p>Gerencie suas configurações de conta.</p>
                    <p class="text-muted">Acesse seu perfil para alterar senha e atualizar informações.</p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include_once('../components/footer.php'); ?>