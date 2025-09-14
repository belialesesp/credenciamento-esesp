<?php
// pages/home.php - Updated dashboard with role-based content
require_once '../init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include_once('../components/header.php');

// Get user roles from database
$user_name = $_SESSION['user_name'] ?? 'Usuário';
$user_id = $_SESSION['user_id'] ?? null;

// Fetch user roles from user_roles table
$user_roles = [];
if ($user_id) {
    try {
        $stmt = $conn->prepare("SELECT role FROM user_roles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user_roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        error_log("Error fetching user roles: " . $e->getMessage());
        $user_roles = [];
    }
}

// Check if user is admin
$is_admin = in_array('admin', $user_roles);

// Get primary role for display (or use the first role)
$primary_role = !empty($user_roles) ? $user_roles[0] : 'user';

// Fetch user documents
$documents = [];
if ($user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT d.id, d.name, d.original_name, d.path, d.upload_status, d.created_at, dt.name as type_name
            FROM documents d
            LEFT JOIN document_types dt ON d.document_type_id = dt.id
            WHERE d.user_id = ?
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching documents: " . $e->getMessage());
        $documents = [];
    }
}

// Fetch all available roles
$all_roles = [];
try {
    $stmt = $conn->query("SELECT id, name FROM roles WHERE enabled = 1");
    $all_roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching roles: " . $e->getMessage());
    $all_roles = [];
}
?>

<div class="container">
    <h1 class="main-title">Painel de Controle</h1>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="alert alert-info">
                <strong>Bem-vindo(a), <?= htmlspecialchars($user_name) ?>!</strong><br>
                <?php if (!empty($user_roles)): ?>
                    Suas funções: 
                    <?php 
                    $roleNames = [
                        'admin' => 'Administrador',
                        'docente' => 'Docente',
                        'docente-pos' => 'Docente Pós-Graduação',
                        'tecnico' => 'Técnico',
                        'interprete' => 'Intérprete'
                    ];
                    $displayRoles = array_map(function($role) use ($roleNames) {
                        return $roleNames[$role] ?? $role;
                    }, $user_roles);
                    echo implode(', ', $displayRoles);
                    ?>
                <?php else: ?>
                    Você ainda não possui funções atribuídas.
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
                            } catch (Exception $e) {
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
                            } catch (Exception $e) {
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
                            } catch (Exception $e) {
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
                            } catch (Exception $e) {
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

        <!-- Role Profile Tabs -->
        <?php if (!empty($user_roles)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Meus Perfis</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                        <?php foreach ($user_roles as $index => $role): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?= $index === 0 ? 'active' : '' ?>" id="<?= $role ?>-tab" data-bs-toggle="tab" data-bs-target="#<?= $role ?>" type="button" role="tab" aria-controls="<?= $role ?>" aria-selected="<?= $index === 0 ? 'true' : 'false' ?>">
                                    <?= translateUserType($role) ?>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="tab-content p-3 border border-top-0 rounded-bottom" id="profileTabsContent">
                        <?php foreach ($user_roles as $index => $role): ?>
                            <div class="tab-pane fade <?= $index === 0 ? 'show active' : '' ?>" id="<?= $role ?>" role="tabpanel" aria-labelledby="<?= $role ?>-tab">
                                <div class="d-flex justify-content-between align-items-center">
                                    <p>Visualize e edite suas informações específicas para a função de <?= translateUserType($role) ?>.</p>
                                    <?php
                                    $profile_link = '';
                                    switch ($role) {
                                        case 'docente':
                                            $profile_link = 'docente.php?id=' . $user_id;
                                            break;
                                        case 'docente_pos':
                                            $profile_link = 'docente-pos.php?id=' . $user_id;
                                            break;
                                        case 'tecnico':
                                            $profile_link = 'tecnico.php?id=' . $user_id;
                                            break;
                                        case 'interprete':
                                            $profile_link = 'interprete.php?id=' . $user_id;
                                            break;
                                        default:
                                            $profile_link = 'profile.php?id=' . $user_id;
                                    }
                                    ?>
                                    <a href="<?= $profile_link ?>" class="btn btn-primary">Acessar Perfil Completo</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Document Management Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Gerenciar Documentos</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($documents)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nome do Documento</th>
                                    <th>Tipo</th>
                                    <th>Status</th>
                                    <th>Data de Envio</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($doc['original_name']) ?></td>
                                        <td><?= htmlspecialchars($doc['type_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $doc['upload_status'] == 'uploaded' ? 'success' : 'warning' ?>">
                                                <?= $doc['upload_status'] == 'uploaded' ? 'Enviado' : 'Pendente' ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?></td>
                                        <td>
                                            <?php if ($doc['upload_status'] == 'uploaded' && !empty($doc['path'])): ?>
                                                <a href="../<?= $doc['path'] ?>" target="_blank" class="btn btn-sm btn-info">Visualizar</a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#replaceDocumentModal" data-doc-id="<?= $doc['id'] ?>" data-doc-name="<?= htmlspecialchars($doc['original_name']) ?>">
                                                Substituir
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">Nenhum documento enviado.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Password Change Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Alterar Senha</h5>
            </div>
            <div class="card-body">
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
        </div>

        <!-- Apply for New Roles Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Solicitar Novas Funções</h5>
            </div>
            <div class="card-body">
                <p>Selecione as funções adicionais para as quais você gostaria de se candidatar:</p>

                <form id="applyRolesForm" method="post" action="../process/apply_roles.php">
                    <input type="hidden" name="user_id" value="<?= $user_id ?>">

                    <div class="role-selector">
                        <?php
                        $available_roles = [
                            'docente' => 'Docente (Instrutor para cursos e capacitações)',
                            'docente-pos' => 'Docente Pós-Graduação (Instrutor para cursos de pós-graduação)',
                            'interprete' => 'Intérprete de Libras (Intérprete de língua brasileira de sinais)',
                            'tecnico' => 'Apoio Técnico (Suporte técnico para eventos e capacitações)'
                        ];

                        foreach ($available_roles as $role_value => $role_description):
                            if (in_array($role_value, $user_roles)) continue;
                        ?>
                            <div class="role-checkbox" data-role="<?= $role_value ?>">
                                <input type="checkbox" id="role-<?= $role_value ?>" name="roles[]" value="<?= $role_value ?>">
                                <div class="role-info">
                                    <div class="role-title"><?= explode(' (', $role_description)[0] ?></div>
                                    <div class="role-description"><?= str_replace(['(', ')'], '', explode(' (', $role_description)[1]) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($available_roles) === count($user_roles)): ?>
                        <div class="alert alert-info mt-3">
                            Você já possui todas as funções disponíveis.
                        </div>
                    <?php else: ?>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Enviar Solicitação</button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Replace Document Modal -->
<div class="modal fade" id="replaceDocumentModal" tabindex="-1" aria-labelledby="replaceDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="replaceDocumentModalLabel">Substituir Documento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="replaceDocumentForm" method="post" action="../process/replace_document.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="document_id" id="replace_doc_id">
                    <input type="hidden" name="user_id" value="<?= $user_id ?>">

                    <div class="mb-3">
                        <label for="document_name" class="form-label">Nome do Documento</label>
                        <input type="text" class="form-control" id="document_name" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="new_document" class="form-label">Novo Documento (PDF)</label>
                        <input type="file" class="form-control" id="new_document" name="new_document" accept="application/pdf" required>
                        <div class="form-text">Apenas arquivos PDF são aceitos. Tamanho máximo: 10MB.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Substituir Documento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Role Selection Styles from cadastros.php */
    .role-selector {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 8px;
        margin-bottom: 1rem;
    }

    .role-checkbox {
        display: flex;
        align-items: center;
        padding: 1rem;
        margin-bottom: 0.5rem;
        background: #fff;
        border: 2px solid #dee2e6;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .role-checkbox:hover {
        border-color: #1e4c82;
        box-shadow: 0 2px 8px rgba(30, 76, 130, 0.1);
    }

    .role-checkbox.active {
        border-color: #1e4c82;
        background: linear-gradient(135deg, rgba(30, 76, 130, 0.05) 0%, rgba(252, 169, 52, 0.05) 100%);
    }

    .role-checkbox input[type="checkbox"] {
        width: 20px;
        height: 20px;
        margin-right: 1rem;
        cursor: pointer;
    }

    .role-info {
        flex: 1;
    }

    .role-title {
        font-weight: 600;
        color: #1e4c82;
        margin-bottom: 0.25rem;
    }

    .role-description {
        font-size: 0.875rem;
        color: #6c757d;
    }
</style>

<script>
    // Function to toggle password visibility
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

    // Handle document replacement modal
    const replaceDocumentModal = document.getElementById('replaceDocumentModal');
    if (replaceDocumentModal) {
        replaceDocumentModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const docId = button.getAttribute('data-doc-id');
            const docName = button.getAttribute('data-doc-name');

            const modal = this;
            modal.querySelector('#replace_doc_id').value = docId;
            modal.querySelector('#document_name').value = docName;
        });
    }

    // Handle role checkbox changes
    document.addEventListener('DOMContentLoaded', function() {
        const roleCheckboxes = document.querySelectorAll('#applyRolesForm input[type="checkbox"]');

        roleCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const roleCard = this.closest('.role-checkbox');

                // Toggle active class on card
                if (this.checked) {
                    roleCard.classList.add('active');
                } else {
                    roleCard.classList.remove('active');
                }
            });
        });

        // Handle role application form
        const applyRolesForm = document.getElementById('applyRolesForm');

        if (applyRolesForm) {
            applyRolesForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const selectedRoles = Array.from(this.querySelectorAll('input[name="roles[]"]:checked')).map(cb => cb.value);

                if (selectedRoles.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Atenção',
                        text: 'Por favor, selecione pelo menos uma função.',
                        confirmButtonColor: '#1e4c82'
                    });
                    return;
                }

                // Show loading state
                const submitButton = this.querySelector('button[type="submit"]');
                const originalText = submitButton.innerHTML;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
                submitButton.disabled = true;

                try {
                    const formData = new FormData(this);

                    const response = await fetch('../process/apply_roles.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso!',
                            text: result.message,
                            confirmButtonColor: '#1e4c82'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: result.message,
                            confirmButtonColor: '#dc3545'
                        });
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro de Conexão',
                        text: 'Não foi possível conectar ao servidor. Tente novamente.',
                        confirmButtonColor: '#dc3545'
                    });
                } finally {
                    submitButton.innerHTML = originalText;
                    submitButton.disabled = false;
                }
            });
        }
    });
    // Initialize role application form handler
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof handleRoleApplication === 'function') {
            handleRoleApplication();
        }
    });
</script>
<!-- Your form handler script -->
<script src="../scripts/form-handler.js"></script>
<?php include_once('../components/footer.php'); ?>