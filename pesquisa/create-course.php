<?php
/**
 * Create New Course
 * Admin creates course → System generates token → QR Code created automatically
 */

require_once __DIR__ . '/includes/init.php';

$db = PesquisaDatabase::getInstance();
$userName = getCurrentUserName();
$userCPF = getCurrentUserCPF();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        $docente = trim($_POST['docente_name'] ?? '');
        $category = $_POST['category'] ?? 'Agenda Esesp';
        $month = (int)($_POST['month'] ?? date('n'));
        $year = (int)($_POST['year'] ?? date('Y'));
        $description = trim($_POST['description'] ?? '');
        $maxResponses = !empty($_POST['max_responses']) ? (int)$_POST['max_responses'] : null;
        
        // Validation
        if (empty($name) || empty($docente)) {
            throw new Exception("Nome do curso e docente são obrigatórios");
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
            // Add random suffix
            $token .= '-' . substr(md5(microtime()), 0, 4);
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        try {
            // Insert course
            $courseId = $db->insert('courses', [
                'token' => $token,
                'name' => $name,
                'docente_name' => $docente,
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
                    error_log("QR Code generation failed for course $courseId, but course was saved");
                    setFlashMessage("Curso criado com sucesso! Token: $token (QR Code não pôde ser gerado, mas o link funciona)", 'warning');
                } else {
                    setFlashMessage("Curso criado com sucesso! Token: $token", 'success');
                }
            } catch (Exception $qrError) {
                error_log("QR Code generation error: " . $qrError->getMessage());
                setFlashMessage("Curso criado com sucesso! Token: $token (QR Code não pôde ser gerado, mas o link funciona)", 'warning');
            }
            
            header("Location: course-details.php?id=$courseId");
            exit;
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Course creation error: " . $error);
    }
}

// Get current month/year for defaults
$currentMonth = date('n');
$currentYear = date('Y');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Curso - <?= PESQUISA_SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1e3a5f;
            --secondary: #2c5f8d;
        }
        
        body {
            background: #f3f4f6;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 2rem;
        }
        
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .form-section:last-child {
            border-bottom: none;
        }
        
        .form-section h3 {
            font-size: 1.25rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
        }
        
        .info-box i {
            color: #3b82f6;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="bi bi-plus-circle me-2"></i>Criar Novo Curso</h1>
                    <p class="mb-0">Sistema gerará automaticamente token e QR Code</p>
                </div>
                <a href="index.php" class="btn btn-light">
                    <i class="bi bi-arrow-left me-2"></i>Voltar
                </a>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container mb-5">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i><?= sanitize($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="form-card">
                    <form method="POST" action="">
                        
                        <!-- Information Box -->
                        <div class="info-box">
                            <i class="bi bi-info-circle"></i>
                            <strong>Como funciona:</strong>
                            <ol class="mb-0 mt-2">
                                <li>Preencha os dados do curso abaixo</li>
                                <li>Sistema gera automaticamente um token (ex: gestao-publica-out-2024)</li>
                                <li>QR Code é criado automaticamente</li>
                                <li>Compartilhe o QR Code com os alunos</li>
                            </ol>
                        </div>
                        
                        <!-- Basic Information -->
                        <div class="form-section">
                            <h3><i class="bi bi-book me-2"></i>Informações Básicas</h3>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Nome do Curso *</label>
                                <input type="text" class="form-control" id="name" name="name" required
                                       placeholder="Ex: Gestão Pública" maxlength="255">
                                <small class="text-muted">O sistema gerará automaticamente o token a partir deste nome</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="docente_name" class="form-label">Nome do Docente *</label>
                                <input type="text" class="form-control" id="docente_name" name="docente_name" required
                                       placeholder="Ex: Prof. João Silva" maxlength="255">
                            </div>
                            
                            <div class="mb-3">
                                <label for="category" class="form-label">Categoria *</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="Agenda Esesp">Agenda Esesp</option>
                                    <option value="Esesp na Estrada">Esesp na Estrada</option>
                                    <option value="EAD">EAD</option>
                                    <option value="Pós-Graduação">Pós-Graduação</option>
                                    <option value="Demanda Específica">Demanda Específica</option>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="month" class="form-label">Mês *</label>
                                    <select class="form-select" id="month" name="month" required>
                                        <?php
                                        $months = [
                                            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                                            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                                            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                                        ];
                                        foreach ($months as $num => $name) {
                                            $selected = $num == $currentMonth ? 'selected' : '';
                                            echo "<option value='$num' $selected>$name</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="year" class="form-label">Ano *</label>
                                    <input type="number" class="form-control" id="year" name="year" required
                                           value="<?= $currentYear ?>" min="2020" max="2100">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Descrição</label>
                                <textarea class="form-control" id="description" name="description" rows="3"
                                          placeholder="Descrição opcional do curso"></textarea>
                            </div>
                        </div>
                        
                        <!-- Advanced Options -->
                        <div class="form-section">
                            <h3><i class="bi bi-gear me-2"></i>Opções Avançadas</h3>
                            
                            <div class="mb-3">
                                <label for="max_responses" class="form-label">Limite de Respostas</label>
                                <input type="number" class="form-control" id="max_responses" name="max_responses"
                                       min="1" placeholder="Deixe em branco para ilimitado">
                                <small class="text-muted">Número máximo de respostas aceitas (opcional)</small>
                            </div>
                        </div>
                        
                        <!-- Submit Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle me-2"></i>Criar Curso e Gerar QR Code
                            </button>
                            <a href="index.php" class="btn btn-secondary btn-lg">
                                <i class="bi bi-x-circle me-2"></i>Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview token generation
        document.getElementById('name').addEventListener('input', function() {
            const name = this.value.toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9\s]/g, '')
                .replace(/\s+/g, '-');
            
            const month = document.getElementById('month').value;
            const year = document.getElementById('year').value;
            
            const months = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'];
            const monthAbbr = months[parseInt(month) - 1] || 'xxx';
            
            const token = name.substring(0, 50) + '-' + monthAbbr + '-' + year;
            
            // Show preview (you can add a preview element to the HTML)
            console.log('Token preview:', token);
        });
    </script>
</body>
</html>