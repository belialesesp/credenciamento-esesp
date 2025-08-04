<?php
// includes/navbar.php - Example navbar with role-based menu items

// This file should be included after init.php
if (!defined('getUserId') || !function_exists('getUserId')) {
    die('This file should be included after init.php');
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="<?php echo getBasePath(); ?>/">Credenciamento ESESP</a>
        
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <?php if (isAuthenticated()): ?>
                    <!-- Home link for all authenticated users -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo getBasePath(); ?>/pages/home.php">Início</a>
                    </li>
                    
                    <?php if (isAdmin()): ?>
                        <!-- Admin menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-toggle="dropdown">
                                Administração
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="<?php echo getBasePath(); ?>/pages/admin/users.php">Gerenciar Usuários</a>
                                <a class="dropdown-item" href="<?php echo getBasePath(); ?>/pages/admin/roles.php">Gerenciar Papéis</a>
                                <a class="dropdown-item" href="<?php echo getBasePath(); ?>/pages/admin/reports.php">Relatórios</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="<?php echo getBasePath(); ?>/test_roles.php">Testar Sistema de Papéis</a>
                            </div>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (hasAnyRole(['docente', 'docente_pos'])): ?>
                        <!-- Teacher menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="docenteDropdown" role="button" data-toggle="dropdown">
                                Área do Docente
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="<?php echo getBasePath(); ?>/pages/docente.php?id=<?php echo getUserId(); ?>">
                                    Meu Perfil
                                </a>
                                <a class="dropdown-item" href="<?php echo getBasePath(); ?>/pages/my_courses.php">
                                    Meus Cursos
                                </a>
                                
                                <?php if (hasRole('docente_pos')): ?>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="<?php echo getBasePath(); ?>/pages/docente-pos.php?id=<?php echo getUserId(); ?>">
                                        Área Pós-Graduação
                                    </a>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (hasRole('tecnico')): ?>
                        <!-- Technician menu -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo getBasePath(); ?>/pages/tecnico.php?id=<?php echo getUserId(); ?>">
                                Área do Técnico
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (hasRole('interprete')): ?>
                        <!-- Interpreter menu -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo getBasePath(); ?>/pages/interprete.php?id=<?php echo getUserId(); ?>">
                                Área do Intérprete
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- User menu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                            <?php echo htmlspecialchars(getUserName()); ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <h6 class="dropdown-header">Seus Papéis:</h6>
                            <?php foreach ($_SESSION['user_roles'] ?? [] as $role): ?>
                                <span class="dropdown-item-text small">
                                    • <?php echo getRoleDisplayName($role); ?>
                                </span>
                            <?php endforeach; ?>
                            
                            <div class="dropdown-divider"></div>
                            
                            <a class="dropdown-item" href="<?php echo getBasePath(); ?>/pages/profile.php">
                                <i class="fas fa-user"></i> Meu Perfil
                            </a>
                            <a class="dropdown-item" href="<?php echo getBasePath(); ?>/pages/change_password.php">
                                <i class="fas fa-key"></i> Alterar Senha
                            </a>
                            
                            <div class="dropdown-divider"></div>
                            
                            <a class="dropdown-item" href="<?php echo getBasePath(); ?>/auth/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Sair
                            </a>
                        </div>
                    </li>
                    
                <?php else: ?>
                    <!-- Not authenticated -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo getBasePath(); ?>/pages/login.php">Entrar</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php if (isFirstLogin()): ?>
    <!-- First login banner -->
    <div class="alert alert-warning text-center mb-0">
        <i class="fas fa-exclamation-triangle"></i>
        Primeiro acesso detectado! Por favor, 
        <a href="<?php echo getBasePath(); ?>/pages/complete_profile.php" class="alert-link">
            complete seu perfil
        </a> e 
        <a href="<?php echo getBasePath(); ?>/pages/change_password.php" class="alert-link">
            altere sua senha
        </a>.
    </div>
<?php endif; ?>