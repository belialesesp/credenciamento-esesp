<?php
// pages/tecnico.php - Complete version with dual approval system
require_once '../init.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
        echo '<div class="alert alert-danger">Sessão expirada. Por favor, faça login novamente.</div>';
        exit();
    } else {
        header('Location: login.php');
        exit();
    }
}

$is_ajax_request = isset($_GET['ajax']) && $_GET['ajax'] == 1;

if (!$is_ajax_request) {
    echo '<link rel="stylesheet" href="../styles/user.css">';
    include '../components/header.php';
}

require_once '../pdf/assets/title_case.php';
require_once '../backend/services/technician.service.php';

$connection = new Database();
$conn = $connection->connect();

$isAdmin = false;
if (isset($_SESSION['user_roles']) && is_array($_SESSION['user_roles'])) {
    $is_admin = isAdministrativeRole();
}

$requested_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $conn->prepare("
    SELECT u.id 
    FROM user u
    INNER JOIN user_roles ur ON ur.user_id = u.id
    WHERE u.id = ? AND ur.role = 'tecnico'
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

$is_own_profile = false;
if ($is_admin) {
    $is_own_profile = false;
} elseif (hasRole('tecnico') && $_SESSION['user_id'] == $requested_id) {
    $is_own_profile = true;
} elseif ($_SESSION['user_id'] == $requested_id) {
    $is_own_profile = true;
} else {
    if ($is_ajax_request) {
        echo '<div class="alert alert-danger">Acesso não autorizado.</div>';
        exit();
    } else {
        header('Location: home.php');
        exit();
    }
}

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

    $addressObj = $technician->address;
    $addressStr = '';

    if ($addressObj) {
        if (method_exists($addressObj, '__toString')) {
            $addressStr = (string) $addressObj;
            if ($addressObj->city && $addressObj->state) {
                $addressStr .= ', ' . titleCase($addressObj->city) . ' - ' . strtoupper($addressObj->state);
            }
            if ($addressObj->zip) {
                $addressStr .= ', CEP: ' . $addressObj->zip;
            }
        } else {
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

    if (empty($addressStr)) {
        $addressStr = 'Não informado';
    }

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

    // Get evaluation data from technician table
    $eval_sql = "SELECT gese_evaluation, pedagogico_evaluation FROM technician WHERE id = (
        SELECT type_id FROM user WHERE id = ? AND user_type = 'technician'
    )";
    $eval_stmt = $conn->prepare($eval_sql);
    $eval_stmt->execute([$requested_id]);
    $eval_data = $eval_stmt->fetch(PDO::FETCH_ASSOC);

    $gese_eval = $eval_data['gese_evaluation'] ?? null;
    $ped_eval = $eval_data['pedagogico_evaluation'] ?? null;
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

if ($is_ajax_request) {
    ob_start();
}
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
            <h3>Avaliação</h3>
            <div class="row">
                <div class="col-12">
                    <p><strong>Status Atual:</strong>
                        <span class="user-status <?= $statusClass ?>"><?= $statusText ?></span>
                    </p>
                </div>
            </div>

            <?php
            $show_gese = (isGESE() || (isAdmin() && !isGEDTH())) && !isGEDTH();
            $show_ped = (isPedagogico() || (isAdmin() && !isGEDTH())) && !isGEDTH();
            ?>

            <!-- Show evaluation status badges -->
            <div class="evaluation-status mb-3">
                <span class="badge <?= $gese_eval === 1 ? 'bg-success' : ($gese_eval === 0 ? 'bg-danger' : 'bg-warning') ?>">
                    Avaliação Documental: <?= $gese_eval === 1 ? 'Aprovado' : ($gese_eval === 0 ? 'Reprovado' : 'Pendente') ?>
                </span>
                <span class="badge <?= $ped_eval === 1 ? 'bg-success' : ($ped_eval === 0 ? 'bg-danger' : 'bg-warning') ?> ms-2">
                    Avaliação Pedagógica: <?= $ped_eval === 1 ? 'Aprovado' : ($ped_eval === 0 ? 'Reprovado' : 'Pendente') ?>
                </span>
            </div>

            <!-- GESE Evaluation Buttons -->
            <?php if ($show_gese): ?>
                <div class="mb-2">
                    <label class="form-label fw-bold">Avaliação Documental (GESE):</label>
                    <button class="btn btn-success btn-sm me-2"
                        onclick="updateEvaluation(<?= $requested_id ?>, 'gese', 1)"
                        <?= $gese_eval === 1 ? 'disabled' : '' ?>>
                        <i class="fas fa-check"></i> Aprovar Documentação
                    </button>
                    <button class="btn btn-danger btn-sm me-2"
                        onclick="updateEvaluation(<?= $requested_id ?>, 'gese', 0)"
                        <?= $gese_eval === 0 ? 'disabled' : '' ?>>
                        <i class="fas fa-times"></i> Reprovar Documentação
                    </button>
                    <button class="btn btn-secondary btn-sm"
                        onclick="updateEvaluation(<?= $requested_id ?>, 'gese', null)"
                        <?= $gese_eval === null ? 'disabled' : '' ?>>
                        <i class="fas fa-undo"></i> Resetar
                    </button>
                </div>
            <?php endif; ?>

            <!-- Pedagogico Evaluation Buttons -->
            <?php if ($show_ped): ?>
                <div>
                    <label class="form-label fw-bold">Avaliação Pedagógica (Pedagógico):</label>
                    <button class="btn btn-success btn-sm me-2"
                        onclick="updateEvaluation(<?= $requested_id ?>, 'pedagogico', 1)"
                        <?= $ped_eval === 1 ? 'disabled' : '' ?>>
                        <i class="fas fa-check"></i> Aprovar Pedagogia
                    </button>
                    <button class="btn btn-danger btn-sm me-2"
                        onclick="updateEvaluation(<?= $requested_id ?>, 'pedagogico', 0)"
                        <?= $ped_eval === 0 ? 'disabled' : '' ?>>
                        <i class="fas fa-times"></i> Reprovar Pedagogia
                    </button>
                    <button class="btn btn-secondary btn-sm"
                        onclick="updateEvaluation(<?= $requested_id ?>, 'pedagogico', null)"
                        <?= $ped_eval === null ? 'disabled' : '' ?>>
                        <i class="fas fa-undo"></i> Resetar
                    </button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    // Always add these at the top:
    const userRoles = <?php echo json_encode($_SESSION['user_roles'] ?? []); ?>;

    function canSendInvites() {
        return userRoles.includes('admin') || userRoles.includes('gedth');
    }

    function canViewContractInfo() {
        return userRoles.includes('admin') || userRoles.includes('gese');
    }

    // For page access check:
    const isAdmin = <?= json_encode(isAdministrativeRole()) ?>;

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

        function updateEvaluation(userId, evaluationType, status) {
            const evaluationLabels = {
                'gese': 'Avaliação Documental',
                'pedagogico': 'Avaliação Pedagógica'
            };

            const statusText = status === 1 ? 'aprovar' : (status === 0 ? 'reprovar' : 'resetar');
            const evaluationLabel = evaluationLabels[evaluationType] || evaluationType;

            if (!confirm(`Tem certeza que deseja ${statusText} a ${evaluationLabel}?`)) {
                return;
            }

            const clickedButton = event.target;
            const buttonGroup = clickedButton.closest('.btn-group');
            const allButtons = buttonGroup.querySelectorAll('button');

            allButtons.forEach(btn => {
                btn.disabled = true;
                if (btn === clickedButton) {
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
                }
            });

            fetch('../backend/api/update_staff_evaluation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        user_type: 'technician',
                        evaluation_type: evaluationType,
                        status: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(`${evaluationLabel} atualizada com sucesso!`, 'success');

                        updateEvaluationBadges(evaluationType, status, data.verification);
                        updateButtonStates(buttonGroup, status);
                    } else {
                        showNotification('Erro: ' + (data.message || 'Erro desconhecido'), 'danger');
                        allButtons.forEach(btn => {
                            btn.disabled = false;
                            restoreButtonText(btn, evaluationType, status);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Erro ao processar solicitação', 'danger');
                    allButtons.forEach(btn => {
                        btn.disabled = false;
                        restoreButtonText(btn, evaluationType, status);
                    });
                });
        }

        function updateEvaluationBadges(evaluationType, status, verification) {
            const badges = document.querySelector('.evaluation-status');

            if (evaluationType === 'gese') {
                const geseBadge = badges.querySelector('span:first-child');
                if (status === 1) {
                    geseBadge.className = 'badge bg-success';
                    geseBadge.textContent = 'Avaliação Documental: Aprovado';
                } else if (status === 0) {
                    geseBadge.className = 'badge bg-danger';
                    geseBadge.textContent = 'Avaliação Documental: Reprovado';
                } else {
                    geseBadge.className = 'badge bg-warning';
                    geseBadge.textContent = 'Avaliação Documental: Pendente';
                }
            } else if (evaluationType === 'pedagogico') {
                const pedBadge = badges.querySelector('span:last-child');
                if (status === 1) {
                    pedBadge.className = 'badge bg-success ms-2';
                    pedBadge.textContent = 'Avaliação Pedagógica: Aprovado';
                } else if (status === 0) {
                    pedBadge.className = 'badge bg-danger ms-2';
                    pedBadge.textContent = 'Avaliação Pedagógica: Reprovado';
                } else {
                    pedBadge.className = 'badge bg-warning ms-2';
                    pedBadge.textContent = 'Avaliação Pedagógica: Pendente';
                }
            }

            if (verification) {
                updateMainStatusBadge(verification);
            }
        }

        function updateMainStatusBadge(verification) {
            const mainStatusBadge = document.querySelector('.user-status');
            const geseEval = verification.gese_evaluation;
            const pedEval = verification.pedagogico_evaluation;

            let statusText = 'Aguardando aprovação';
            let statusClass = 'status-pending';

            if (geseEval !== null && pedEval !== null) {
                if (geseEval === 1 && pedEval === 1) {
                    statusText = 'Apto';
                    statusClass = 'status-approved';
                } else if (geseEval === 0 || pedEval === 0) {
                    statusText = 'Inapto';
                    statusClass = 'status-not-approved';
                }
            }

            mainStatusBadge.className = 'user-status ' + statusClass;
            mainStatusBadge.textContent = statusText;
        }

        function updateButtonStates(buttonGroup, newStatus) {
            const buttons = buttonGroup.querySelectorAll('button');

            buttons.forEach(btn => {
                const btnStatus = btn.onclick.toString().match(/,\s*(\d+|null)\s*\)/);
                if (btnStatus) {
                    const btnStatusValue = btnStatus[1] === 'null' ? null : parseInt(btnStatus[1]);
                    btn.disabled = (btnStatusValue === newStatus);
                    restoreButtonText(btn, null, btnStatusValue);
                }
            });
        }

        function restoreButtonText(btn, evaluationType, status) {
            const icons = {
                1: '<i class="fas fa-check"></i>',
                0: '<i class="fas fa-times"></i>',
                null: '<i class="fas fa-undo"></i>'
            };

            const texts = {
                1: ['Aprovar Documentação', 'Aprovar Pedagogia'],
                0: ['Reprovar Documentação', 'Reprovar Pedagogia'],
                null: ['Resetar', 'Resetar']
            };

            const icon = icons[status] || icons[null];
            const isGese = btn.onclick.toString().includes("'gese'");
            const textIndex = isGese ? 0 : 1;
            const text = texts[status]?.[textIndex] || 'Resetar';

            btn.innerHTML = `${icon} ${text}`;
        }

        function showNotification(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
            document.body.appendChild(alertDiv);
            setTimeout(() => alertDiv.remove(), 5000);
        }
    <?php endif; ?>
</script>

<?php
if ($is_ajax_request) {
    $content = ob_get_clean();
    echo $content;
    exit();
} else {
    include '../components/footer.php';
}
?>