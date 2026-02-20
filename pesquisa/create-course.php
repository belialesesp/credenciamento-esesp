<?php
/**
 * Create New Course
 * Simple autocomplete from existing docentes + manual entry
 */

require_once __DIR__ . '/includes/init.php';

$db = PesquisaDatabase::getInstance();
$userName = getCurrentUserName();
$userCPF = getCurrentUserCPF();

// Get list of existing docentes for autocomplete
$existingDocentes = $db->fetchAll("
    SELECT DISTINCT docente_name 
    FROM courses 
    WHERE docente_name IS NOT NULL 
    ORDER BY docente_name
");

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        $docenteName = trim($_POST['docente_name'] ?? '');
        $category = $_POST['category'] ?? 'Agenda Esesp';
        $month = (int)($_POST['month'] ?? date('n'));
        $year = (int)($_POST['year'] ?? date('Y'));
        $description = trim($_POST['description'] ?? '');
        $maxResponses = !empty($_POST['max_responses']) ? (int)$_POST['max_responses'] : null;
        
        // Validation
        if (empty($name)) {
            throw new Exception("Nome do curso é obrigatório");
        }
        
        if (empty($docenteName)) {
            throw new Exception("Nome do docente é obrigatório");
        }
        
        if ($month < 1 || $month > 12) {
            throw new Exception("Mês inválido");
        }
        
        if ($year < 2020 || $year > 2100) {
            throw new Exception("Ano inválido");
        }
        
        // Generate token
        $token = generateCourseToken($name, $month, $year);
        
        // Check if token already exists
        $existing = $db->fetchOne("SELECT id FROM courses WHERE token = ?", [$token]);
        if ($existing) {
            $token .= '-' . substr(md5(microtime()), 0, 4);
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        try {
            // Insert course
            $courseId = $db->insert('courses', [
                'token' => $token,
                'name' => $name,
                'docente_name' => $docenteName,
                'docente_cpf' => null,
                'category' => $category,
                'month' => $month,
                'year' => $year,
                'description' => $description,
                'max_responses' => $maxResponses,
                'is_active' => true,
                'created_by' => $userCPF
            ]);
            
            // Initialize analytics cache
            $db->insert('analytics_cache', [
                'course_id' => $courseId,
                'response_count' => 0,
                'overall_score' => null
            ]);
            
            // Commit the course creation first
            $db->commit();
            
            // Try to generate QR Code (but don't fail if it doesn't work)
            try {
                $qrCode = generateQRCode($courseId, $token);
                if (!$qrCode) {
                    error_log("QR Code generation failed for course $courseId");
                    setFlashMessage("Curso criado com sucesso! Token: $token (QR Code não pôde ser gerado)", 'warning');
                } else {
                    setFlashMessage("Curso criado com sucesso!", 'success');
                }
            } catch (Exception $e) {
                error_log("QR Code generation error: " . $e->getMessage());
                setFlashMessage("Curso criado com sucesso! (QR Code será gerado posteriormente)", 'success');
            }
            
            header('Location: course-details.php?id=' . $courseId);
            exit;
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        setFlashMessage("Erro ao criar curso: " . $e->getMessage(), 'danger');
    }
}

$months = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Curso - <?= PESQUISA_SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        :root {
            --primary: #1e3a5f;
            --secondary: #2c5f8d;
        }
        
        body {
            background: #f3f4f6;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .form-label {
            font-weight: 500;
            color: var(--primary);
        }

        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <div class="container">
            <h1><i class="bi bi-plus-circle me-2"></i>Criar Novo Curso</h1>
            <p class="mb-0">Preencha os dados do curso para gerar a pesquisa de satisfação</p>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container mb-5">
        <?= displayFlashMessage() ?>
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-body p-4">
                        <form method="POST" action="" id="courseForm">
                            <!-- Course Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label">
                                    <i class="bi bi-book me-1"></i>Nome do Curso *
                                </label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       placeholder="Ex: Gestão de Projetos Públicos" required>
                                <small class="text-muted">Nome completo do curso ou evento</small>
                            </div>
                            
                            <!-- Docente Name - Autocomplete with Manual Entry -->
                            <div class="mb-3">
                                <label for="docente_select" class="form-label">
                                    <i class="bi bi-person-circle me-1"></i>Nome do Docente *
                                </label>
                                <select class="form-select" id="docente_select" name="docente_name" required>
                                    <option value="">Selecione ou digite um nome...</option>
                                    <?php foreach ($existingDocentes as $docente): ?>
                                        <option value="<?= htmlspecialchars($docente['docente_name']) ?>">
                                            <?= htmlspecialchars($docente['docente_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">
                                    <i class="bi bi-lightbulb text-success me-1"></i>
                                    <strong>Dica:</strong> Digite o nome e pressione <kbd>Enter</kbd> para adicionar um novo docente.
                                </small>
                            </div>
                            
                            <!-- Category -->
                            <div class="mb-3">
                                <label for="category" class="form-label">
                                    <i class="bi bi-tag me-1"></i>Categoria *
                                </label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="Agenda Esesp">Agenda Esesp</option>
                                    <option value="Esesp na Estrada">Esesp na Estrada</option>
                                    <option value="EAD">EAD</option>
                                    <option value="Pós-Graduação">Pós-Graduação</option>
                                    <option value="Demanda Específica">Demanda Específica</option>
                                </select>
                            </div>
                            
                            <!-- Month and Year -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="month" class="form-label">
                                        <i class="bi bi-calendar-month me-1"></i>Mês *
                                    </label>
                                    <select class="form-select" id="month" name="month" required>
                                        <?php foreach ($months as $num => $name): ?>
                                            <option value="<?= $num ?>" <?= $num == date('n') ? 'selected' : '' ?>>
                                                <?= $name ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="year" class="form-label">
                                        <i class="bi bi-calendar-event me-1"></i>Ano *
                                    </label>
                                    <select class="form-select" id="year" name="year" required>
                                        <?php for ($y = date('Y'); $y <= date('Y') + 2; $y++): ?>
                                            <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>>
                                                <?= $y ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Description -->
                            <div class="mb-3">
                                <label for="description" class="form-label">
                                    <i class="bi bi-card-text me-1"></i>Descrição (Opcional)
                                </label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="3" placeholder="Breve descrição do curso"></textarea>
                            </div>
                            
                            <!-- Max Responses -->
                            <div class="mb-4">
                                <label for="max_responses" class="form-label">
                                    <i class="bi bi-people me-1"></i>Limite de Respostas (Opcional)
                                </label>
                                <input type="number" class="form-control" id="max_responses" 
                                       name="max_responses" min="1" 
                                       placeholder="Deixe em branco para ilimitado">
                                <small class="text-muted">
                                    Se definido, a pesquisa será encerrada ao atingir este número
                                </small>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Importante:</strong> Após criar o curso, um token único e QR Code 
                                serão gerados automaticamente para compartilhar a pesquisa.
                            </div>
                            
                            <!-- Actions -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle me-2"></i>Criar Curso
                                </button>
                                <a href="courses.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle me-2"></i>Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 with tags for manual entry
            $('#docente_select').select2({
                theme: 'bootstrap-5',
                tags: true,
                placeholder: 'Selecione ou digite um nome...',
                allowClear: true,
                createTag: function (params) {
                    var term = $.trim(params.term);
                    
                    // Allow any text with at least 3 characters
                    if (term.length < 3) {
                        return null;
                    }
                    
                    return {
                        id: term,
                        text: term,
                        newTag: true
                    };
                },
                templateResult: function(data) {
                    if (data.loading) {
                        return data.text;
                    }
                    
                    // Show indicator for new entries
                    if (data.newTag) {
                        return $('<div><strong>' + data.text + '</strong> <span class="badge bg-success ms-2">Novo</span></div>');
                    }
                    
                    return data.text;
                }
            });

            // Form validation
            $('#courseForm').on('submit', function(e) {
                const docenteName = $('#docente_select').val();
                if (!docenteName || docenteName.trim().length < 3) {
                    e.preventDefault();
                    alert('Por favor, selecione ou digite o nome do docente (mínimo 3 caracteres)');
                    return false;
                }
            });
        });
    </script>
</body>
</html>