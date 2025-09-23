<?php
// pages/tecnico.php - Complete version with AJAX support
require_once '../init.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    // For AJAX requests, return error message
    if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
        echo '<div class="alert alert-danger">Sessão expirada. Por favor, faça login novamente.</div>';
        exit();
    } else {
        header('Location: login.php');
        exit();
    }
}

// Check if this is an AJAX request
$is_ajax_request = isset($_GET['ajax']) && $_GET['ajax'] == 1;

// Include styles and header only if not AJAX request
if (!$is_ajax_request) {
    echo '<link rel="stylesheet" href="../styles/user.css">';
    include '../components/header.php';
}

require_once '../pdf/assets/title_case.php';
require_once '../backend/services/technician.service.php';

// Initialize database connection
$connection = new Database();
$conn = $connection->connect();

// Check if user is admin
$is_admin = false;
if (isset($_SESSION['user_id'])) {
    $admin_check = $conn->prepare("
        SELECT COUNT(*) 
        FROM user_roles 
        WHERE user_id = ? AND role = 'admin'
    ");
    $admin_check->execute([$_SESSION['user_id']]);
    $is_admin = ($admin_check->fetchColumn() > 0);
}

// Get requested ID from URL
$requested_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verify the requested user exists and is a technician
$stmt = $conn->prepare("
    SELECT u.id 
    FROM user u
    INNER JOIN user_roles ur ON ur.user_id = u.id
    WHERE u.id = ? AND ur.role = 'tecnico' AND u.enabled = 1
");
$stmt->execute([$requested_id]);

if (!$stmt->fetch()) {
    if ($is_ajax_request) {
        echo '<div class="alert alert-danger">Técnico não encontrado.</div>';
        exit();
    } else {
        header('Location: tecnicos.php');
        exit();
    }
}

// Check access permissions - FIXED to use role system
$is_own_profile = false;
if ($is_admin) {
    // Admin can access any profile
    $is_own_profile = false;
} elseif (hasRole('tecnico') && $_SESSION['user_id'] == $requested_id) {
    // User with 'tecnico' role viewing their own profile
    $is_own_profile = true;
} elseif ($_SESSION['user_id'] == $requested_id) {
    // Any user viewing their own profile (backward compatibility)
    $is_own_profile = true;
} else {
    // Not authorized
    if ($is_ajax_request) {
        echo '<div class="alert alert-danger">Acesso não autorizado.</div>';
        exit();
    } else {
        header('Location: home.php');
        exit();
    }
}

// Use the TechnicianService
$technicianService = new TechnicianService($conn);

try {
    $technician = $technicianService->getTechnician($requested_id);

    if (!$technician) {
        if ($is_ajax_request) {
            echo '<div class="alert alert-danger">Técnico não encontrado.</div>';
            exit();
        } else {
            echo '<div class="container">
                    <h1 class="main-title">Erro</h1>
                    <p>Técnico não encontrado.</p>
                    <a href="tecnicos.php" class="btn btn-primary">Voltar para lista de técnicos</a>
                  </div>';
            include '../components/footer.php';
            exit();
        }
    }

    // Extract technician data - using public properties
    $name = $technician->name;
    $cpf = $technician->cpf;
    $email = $technician->email;
    $phone = $technician->phone;
    $document_number = $technician->document_number;
    $document_emissor = $technician->document_emissor;
    $document_uf = $technician->document_uf;
    $scholarship = $technician->scholarship;
    $special_needs = $technician->special_needs;
    $enabled = $technician->enabled;
    $file_path = $technician->file_path;

    $createdAt = $technician->created_at;
    $calledAt = $technician->called_at;

    $dateF = $createdAt ? date('d/m/Y', strtotime($createdAt)) : '—';
    $calledDateF = $calledAt ? date('d/m/Y', strtotime($calledAt)) : '—';

    // Handle address - now stored directly in user table
    $addressObj = $technician->address;
    $addressStr = '';
    
    if ($addressObj) {
        // Use the Address class's __toString() method if available
        if (method_exists($addressObj, '__toString')) {
            $addressStr = (string) $addressObj;
            // Add city, state, and ZIP if not already included
            if ($addressObj->city && $addressObj->state) {
                $addressStr .= ', ' . titleCase($addressObj->city) . ' - ' . strtoupper($addressObj->state);
            }
            if ($addressObj->zip) {
                $addressStr .= ', CEP: ' . $addressObj->zip;
            }
        } else {
            // Build formatted address string manually
            $parts = [];
            
            if ($addressObj->street) {
                $streetPart = titleCase($addressObj->street);
                if ($addressObj->number) {
                    $streetPart .= ', ' . $addressObj->number;
                }
                if ($addressObj->complement) {
                    $streetPart .= ', ' . $addressObj->complement;
                }
                $parts[] = $streetPart;
            }
            
            if ($addressObj->neighborhood) {
                $parts[] = titleCase($addressObj->neighborhood);
            }
            
            if ($addressObj->city && $addressObj->state) {
                $parts[] = titleCase($addressObj->city) . ' - ' . strtoupper($addressObj->state);
            }
            
            if ($addressObj->zip) {
                $parts[] = 'CEP: ' . $addressObj->zip;
            }
            
            $addressStr = implode(', ', $parts);
        }
    }
    
    // If no formatted address, display as "Não informado"
    if (empty($addressStr)) {
        $addressStr = 'Não informado';
    }

    // Status text and class
    $statusText = match ($enabled) {
        1 => 'Apto',
        0 => 'Inapto',
        default => 'Aguardando aprovação',
    };

    $statusClass = match ($enabled) {
        1 => 'status-approved',
        0 => 'status-not-approved',
        default => 'status-pending',
    };

} catch (Exception $e) {
    if ($is_ajax_request) {
        echo '<div class="alert alert-danger">Erro ao carregar dados do técnico: ' . htmlspecialchars($e->getMessage()) . '</div>';
        exit();
    } else {
        ob_start();
        include '../components/header.php';
        echo '<div class="container">
                <h1 class="main-title">Erro</h1>
                <p>Erro ao carregar dados do técnico: ' . htmlspecialchars($e->getMessage()) . '</p>
                <a href="tecnicos.php" class="btn btn-primary">Voltar para lista de técnicos</a>
              </div>';
        include '../components/footer.php';
        ob_end_flush();
        exit();
    }
}

// For AJAX requests, output only the content without container
if ($is_ajax_request) {
    ob_start();
?>

    <?php if ($is_own_profile): ?>
        <div class="alert alert-info d-flex justify-content-between align-items-center mb-3">
            <span>Bem-vindo(a) ao seu perfil, <?= titleCase($name) ?>!</span>
            <a href="../auth/logout.php" class="btn btn-danger btn-sm">Sair</a>
        </div>
    <?php endif; ?>

    <?php if ($is_admin): ?>
        <a href="tecnicos.php" class="back-link">Voltar</a>
    <?php endif; ?>

    <h1 class="main-title">Dados do Técnico</h1>

    <div class="info-section">
        <h3>Dados pessoais</h3>
        <div class="row">
            <div class="col-9">
                <p class="col-12"><strong>Nome</strong></p>
                <p class="col-12"><?= titleCase($name) ?></p>
            </div>
            <div class="col-3">
                <p class="col-12"><strong>Data de Inscrição</strong></p>
                <p class="col-12"><?= $dateF ?></p>
            </div>
        </div>
        <?php if ($calledDateF != '—'): ?>
            <div class="row">
                <div class="col-3">
                    <p class="col-12"><strong>Data de Convocação</strong></p>
                    <p class="col-12"><?= $calledDateF ?></p>
                </div>
            </div>
        <?php endif; ?>
        <div class="row">
            <p class="col-12"><strong>Telefone</strong></p>
            <p class="col-12"><?= $phone ?></p>
        </div>
        <div class="row">
            <div class="col-6">
                <p><strong>Documento de Identidade</strong></p>
                <p><?= $document_number ?></p>
            </div>
            <div class="col-4">
                <p><strong>Órgão Emissor</strong></p>
                <p><?= $document_emissor ?></p>
            </div>
            <div class="col-2">
                <p><strong>UF</strong></p>
                <p><?= strtoupper($document_uf) ?></p>
            </div>
        </div>
        <div class="row">
            <p class="col-12"><strong>CPF</strong></p>
            <p class="col-12"><?= $cpf ?></p>
        </div>
        <div class="row">
            <p class="col-12"><strong>Email</strong></p>
            <p class="col-12"><?= $email ?></p>
        </div>
        <div class="row">
            <p class="col-12"><strong>Endereço</strong></p>
            <p class="col-12"><?= htmlspecialchars($addressStr) ?></p>
        </div>
        <div class="row">
            <p class="col-12"><strong>Escolaridade</strong></p>
            <p class="col-12"><?= $scholarship ?></p>
        </div>
        <?php if ($special_needs && $special_needs != 'Não'): ?>
            <div class="row">
                <p class="col-12"><strong>Necessidades Especiais</strong></p>
                <p class="col-12"><?= $special_needs ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="info-section">
        <h3>Documentos</h3>
        <?php if (!empty($file_path)): ?>
            <a href="../backend/documentos/tecnicos/<?= $file_path ?>" target="_blank">Download</a>
        <?php else: ?>
            <p>Nenhum documento disponível.</p>
        <?php endif; ?>
    </div>

    <?php if ($is_own_profile || isFirstLogin()): ?>
        <div class="info-section">
            <h3>Alterar Senha</h3>

            <?php if (isset($_SESSION['password_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['password_error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['password_error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['password_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['password_success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['password_success']); ?>
            <?php endif; ?>

            <?php if (isFirstLogin()): ?>
                <div class="alert alert-warning">
                    <strong>Primeiro acesso!</strong> Por segurança, recomendamos que você altere sua senha.
                </div>
            <?php endif; ?>

            <form method="post" action="../auth/process_change_password.php" class="needs-validation" novalidate>
                <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="current_password">Senha Atual</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password"
                                   name="current_password" required>
                            <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePassword('current_password')" tabindex="-1">
                                <i class="fas fa-eye" id="current_password_icon"></i>
                            </button>
                        </div>
                        <small class="form-text text-muted">
                            Se é seu primeiro acesso, use seu CPF (apenas números)
                        </small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="new_password">Nova Senha</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password"
                                   name="new_password" required minlength="8">
                            <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePassword('new_password')" tabindex="-1">
                                <i class="fas fa-eye" id="new_password_icon"></i>
                            </button>
                        </div>
                        <small class="form-text text-muted">
                            Mínimo 8 caracteres, com letras maiúsculas, minúsculas, números e símbolos (@$!%*?&)
                        </small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="confirm_password">Confirmar Nova Senha</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password"
                                   name="confirm_password" required>
                            <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePassword('confirm_password')" tabindex="-1">
                                <i class="fas fa-eye" id="confirm_password_icon"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Alterar Senha</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($is_admin): ?>
        <div class="info-section">
            <h3>Status do Técnico</h3>
            <div class="row mb-3">
                <div class="col-3">
                    <strong>Status:</strong>
                    <span class="user-status <?= $statusClass ?>"><?= $statusText ?></span>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <button type="button"
                            class="btn btn-success mr-2"
                            onclick="updateTechnicianStatus(<?= $requested_id ?>, 1)"
                        <?= $enabled == 1 ? 'disabled' : '' ?>>
                        <i class="fas fa-check"></i> Aprovar
                    </button>
                    <button type="button"
                            class="btn btn-danger"
                            onclick="updateTechnicianStatus(<?= $requested_id ?>, 0)"
                        <?= $enabled == 0 ? 'disabled' : '' ?>>
                        <i class="fas fa-times"></i> Reprovar
                    </button>
                    <button type="button"
                            class="btn btn-secondary"
                            onclick="updateTechnicianStatus(<?= $requested_id ?>, null)"
                        <?= $enabled === null ? 'disabled' : '' ?>>
                        <i class="fas fa-undo"></i> Resetar Status
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '_icon');

            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        <?php if ($is_admin): ?>
        function updateTechnicianStatus(userId, status) {
            const statusText = status === 1 ? 'aprovar' : (status === 0 ? 'reprovar' : 'resetar o status');
            
            if (confirm('Tem certeza que deseja ' + statusText + ' este técnico?')) {
                fetch('../backend/api/update_technician_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        status: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Status atualizado com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao atualizar status: ' + (data.message || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    alert('Erro ao processar requisição');
                    console.error('Error:', error);
                });
            }
        }
        <?php endif; ?>
    </script>

<?php
    // For AJAX requests, output the buffered content and exit
    $content = ob_get_clean();
    echo $content;
    exit();
} else {
    // For non-AJAX requests, output the full page with container and footer
?>

<div class="container container-user">
    <?php if ($is_own_profile): ?>
        <div class="alert alert-info d-flex justify-content-between align-items-center mb-3">
            <span>Bem-vindo(a) ao seu perfil, <?= titleCase($name) ?>!</span>
            <a href="../auth/logout.php" class="btn btn-danger btn-sm">Sair</a>
        </div>
    <?php endif; ?>

    <?php if ($is_admin): ?>
        <a href="tecnicos.php" class="back-link">Voltar</a>
    <?php endif; ?>

    <h1 class="main-title">Dados do Técnico</h1>

    <div class="info-section">
        <h3>Dados pessoais</h3>
        <div class="row">
            <div class="col-9">
                <p class="col-12"><strong>Nome</strong></p>
                <p class="col-12"><?= titleCase($name) ?></p>
            </div>
            <div class="col-3">
                <p class="col-12"><strong>Data de Inscrição</strong></p>
                <p class="col-12"><?= $dateF ?></p>
            </div>
        </div>
        <?php if ($calledDateF != '—'): ?>
            <div class="row">
                <div class="col-3">
                    <p class="col-12"><strong>Data de Convocação</strong></p>
                    <p class="col-12"><?= $calledDateF ?></p>
                </div>
            </div>
        <?php endif; ?>
        <div class="row">
            <p class="col-12"><strong>Telefone</strong></p>
            <p class="col-12"><?= $phone ?></p>
        </div>
        <div class="row">
            <div class="col-6">
                <p><strong>Documento de Identidade</strong></p>
                <p><?= $document_number ?></p>
            </div>
            <div class="col-4">
                <p><strong>Órgão Emissor</strong></p>
                <p><?= $document_emissor ?></p>
            </div>
            <div class="col-2">
                <p><strong>UF</strong></p>
                <p><?= strtoupper($document_uf) ?></p>
            </div>
        </div>
        <div class="row">
            <p class="col-12"><strong>CPF</strong></p>
            <p class="col-12"><?= $cpf ?></p>
        </div>
        <div class="row">
            <p class="col-12"><strong>Email</strong></p>
            <p class="col-12"><?= $email ?></p>
        </div>
        <div class="row">
            <p class="col-12"><strong>Endereço</strong></p>
            <p class="col-12"><?= htmlspecialchars($addressStr) ?></p>
        </div>
        <div class="row">
            <p class="col-12"><strong>Escolaridade</strong></p>
            <p class="col-12"><?= $scholarship ?></p>
        </div>
        <?php if ($special_needs && $special_needs != 'Não'): ?>
            <div class="row">
                <p class="col-12"><strong>Necessidades Especiais</strong></p>
                <p class="col-12"><?= $special_needs ?></p>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($is_admin): ?>
        <div class="info-section">
            <h3>Status do Técnico</h3>
            <div class="row mb-3">
                <div class="col-3">
                    <strong>Status:</strong>
                    <span class="user-status <?= $statusClass ?>"><?= $statusText ?></span>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <button type="button"
                            class="btn btn-success mr-2"
                            onclick="updateTechnicianStatus(<?= $requested_id ?>, 1)"
                        <?= $enabled == 1 ? 'disabled' : '' ?>>
                        <i class="fas fa-check"></i> Aprovar
                    </button>
                    <button type="button"
                            class="btn btn-danger"
                            onclick="updateTechnicianStatus(<?= $requested_id ?>, 0)"
                        <?= $enabled == 0 ? 'disabled' : '' ?>>
                        <i class="fas fa-times"></i> Reprovar
                    </button>
                    <button type="button"
                            class="btn btn-secondary"
                            onclick="updateTechnicianStatus(<?= $requested_id ?>, null)"
                        <?= $enabled === null ? 'disabled' : '' ?>>
                        <i class="fas fa-undo"></i> Resetar Status
                    </button>
                </div>
            </div>
        </div>

        <script>
            function updateTechnicianStatus(userId, status) {
                const statusText = status === 1 ? 'aprovar' : (status === 0 ? 'reprovar' : 'resetar o status');
                
                if (confirm('Tem certeza que deseja ' + statusText + ' este técnico?')) {
                    fetch('../backend/api/update_technician_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            user_id: userId,
                            status: status
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Status atualizado com sucesso!');
                            location.reload();
                        } else {
                            alert('Erro ao atualizar status: ' + (data.message || 'Erro desconhecido'));
                        }
                    })
                    .catch(error => {
                        alert('Erro ao processar requisição');
                        console.error('Error:', error);
                    });
                }
            }
        </script>
    <?php endif; ?>
</div>

<script>
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = document.getElementById(fieldId + '_icon');

        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>

<?php
    include '../components/footer.php';
}
?>