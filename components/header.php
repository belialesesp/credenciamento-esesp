<?php
// components/header.php - Updated version with integrated navbar
require_once __DIR__ . '/../init.php';
$is_admin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Credenciamento</title>
    <link rel="shortcut icon" href="../assets/Logo-02 esesp.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet" />
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="../styles/global.css">
    <link rel="stylesheet" href="../styles/forms.css">
    <link rel="stylesheet" href="../styles/responsivity.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- jQuery first, then Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js" defer></script>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" defer></script>
    <!-- SweetAlert2 for notifications -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <!-- Navbar - only show if user is logged in -->
    <?php if (isset($_SESSION['user_id']) && $navbar): ?>
        <nav class="navbar navbar-expand-lg navbar-dark" style="background-color:#59bcd9;">
            <div class="container">
                <a class="navbar-brand" href="/credenciamento-esesp">
                    <img src="../assets/Logo-02 esesp.png" alt="Logo" height="40"> Credenciamento ESESP
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <!-- Home link for all authenticated users -->
                        <li class="nav-item">
                            <a class="nav-link" href="../pages/home.php">
                                <i class="fas fa-home"></i> Início
                            </a>
                        </li>

                        <?php if ($is_admin): ?>
                            <!-- Admin menu -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-cog"></i> Administração
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="../pages/docentes.php">
                                            <i class="fas fa-chalkboard-teacher"></i> Docentes
                                        </a></li>
                                    <li><a class="dropdown-item" href="../pages/docentes-pos.php">
                                            <i class="fas fa-graduation-cap"></i> Docentes Pós
                                        </a></li>
                                    <li><a class="dropdown-item" href="../pages/tecnicos.php">
                                            <i class="fas fa-tools"></i> Técnicos
                                        </a></li>
                                    <li><a class="dropdown-item" href="../pages/interpretes.php">
                                            <i class="fas fa-sign-language"></i> Intérpretes
                                        </a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <!-- Regular user - show their profile link -->
                            <?php
                            $userType = $_SESSION['user_type'] ?? '';
                            $userId = $_SESSION['user_id'] ?? 0;

                            switch ($userType) {
                                case 'teacher':
                                    echo '<li class="nav-item"><a class="nav-link" href="../pages/docente.php?id=' . $userId . '"><i class="fas fa-user"></i> Meu Perfil</a></li>';
                                    break;
                                case 'postg_teacher':
                                    echo '<li class="nav-item"><a class="nav-link" href="../pages/docente-pos.php?id=' . $userId . '"><i class="fas fa-user-graduate"></i> Meu Perfil</a></li>';
                                    break;
                                case 'technician':
                                    echo '<li class="nav-item"><a class="nav-link" href="../pages/tecnico.php?id=' . $userId . '"><i class="fas fa-user-cog"></i> Meu Perfil</a></li>';
                                    break;
                                case 'interpreter':
                                    echo '<li class="nav-item"><a class="nav-link" href="../pages/interprete.php?id=' . $userId . '"><i class="fas fa-hands"></i> Meu Perfil</a></li>';
                                    break;
                            }
                            ?>
                        <?php endif; ?>

                        <!-- User menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <h6 class="dropdown-header">Tipo: <?php echo translateUserType($_SESSION['user_type'] ?? ''); ?></h6>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <?php if (isFirstLogin()): ?>
                                    <li><a class="dropdown-item text-warning" href="#">
                                            <i class="fas fa-exclamation-triangle"></i> Complete seu perfil na página de perfil
                                        </a></li>
                                <?php endif; ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="../auth/logout.php">
                                        <i class="fas fa-sign-out-alt"></i> Sair
                                    </a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <?php if (isFirstLogin()): ?>
            <!-- First login warning -->
            <div class="alert alert-warning text-center mb-0 rounded-0">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Primeiro Acesso!</strong> Por favor, acesse seu perfil para alterar sua senha e completar suas informações.
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Main content wrapper -->
    <main class="<?php echo isset($_SESSION['user_id']) ? 'py-4' : ''; ?>">