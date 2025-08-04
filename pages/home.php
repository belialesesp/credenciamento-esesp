<?php
// pages/home.php - Updated dashboard with role-based content
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
    
    <?php if ($user_type === 'admin'): ?>
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
                            $stmt = $conn->query("SELECT COUNT(*) FROM teacher");
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
                            $stmt = $conn->query("SELECT COUNT(*) FROM postg_teacher");
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
                            $stmt = $conn->query("SELECT COUNT(*) FROM technician");
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
                            $stmt = $conn->query("SELECT COUNT(*) FROM interpreter");
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
                    switch($user_type) {
                        case 'teacher':
                            echo '<a href="docente.php?id=' . $type_id . '" class="btn btn-primary">Ver Meu Perfil</a>';
                            break;
                        case 'postg_teacher':
                            echo '<a href="docente-pos.php?id=' . $type_id . '" class="btn btn-primary">Ver Meu Perfil</a>';
                            break;
                        case 'technician':
                            echo '<a href="tecnico.php?id=' . $type_id . '" class="btn btn-primary">Ver Meu Perfil</a>';
                            break;
                        case 'interpreter':
                            echo '<a href="interprete.php?id=' . $type_id . '" class="btn btn-primary">Ver Meu Perfil</a>';
                            break;
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
                    <a href="change_password.php" class="btn btn-secondary">Alterar Senha</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include_once('../components/footer.php'); ?>