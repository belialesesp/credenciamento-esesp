<?php
// pages/home.php - Updated version with proper navigation
require_once '../init.php';

// This page is only for internal users (admin/staff)
if(!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit();
}

include_once('../components/header.php');
?>

<div class="container">
    <h1 class="main-title">Painel Administrativo</h1>
    
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="alert alert-info">
                <strong>Bem-vindo!</strong> Você está logado como administrador/staff interno.
            </div>
        </div>
    </div>
    
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
        
        <!-- Quick Search -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Busca Rápida</h5>
                </div>
                <div class="card-body">
                    <form action="search_docente.php" method="GET">
                        <div class="form-group">
                            <label for="search_cpf">Buscar Docente por CPF:</label>
                            <input type="text" class="form-control" id="search_cpf" name="cpf" 
                                   placeholder="000.000.000-00" maxlength="14">
                        </div>
                        <button type="submit" class="btn btn-success">Buscar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Statistics -->
        <div class="col-md-4 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-primary">
                        <?php
                        // Count regular teachers
                        $conection = new Database();
                        $conn = $conection->connect();
                        $stmt = $conn->query("SELECT COUNT(*) FROM teacher");
                        echo $stmt->fetchColumn();
                        ?>
                    </h3>
                    <p class="mb-0">Docentes Regulares</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-info">
                        <?php
                        $stmt = $conn->query("SELECT COUNT(*) FROM postg_teacher");
                        echo $stmt->fetchColumn();
                        ?>
                    </h3>
                    <p class="mb-0">Docentes Pós-Graduação</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-success">
                        <?php
                        $stmt = $conn->query("SELECT COUNT(*) FROM teacher WHERE id IN (SELECT DISTINCT teacher_id FROM teacher_disciplines WHERE enabled = 1)");
                        echo $stmt->fetchColumn();
                        ?>
                    </h3>
                    <p class="mb-0">Docentes Aprovados</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Ações Rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="btn-group" role="group">
                        <a href="register.php" class="btn btn-outline-primary">Cadastrar Novo Usuário</a>
                        <a href="palestrantes.php" class="btn btn-outline-success">Gerenciar Palestrantes</a>
                        <a href="interpretes.php" class="btn btn-outline-info">Gerenciar Intérpretes</a>
                        <a href="relatorios.php" class="btn btn-outline-warning">Relatórios</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// CPF Mask for search
document.getElementById('search_cpf').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 11) {
        value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    }
    e.target.value = value;
});
</script>

<?php
include_once('../components/footer.php');
?>