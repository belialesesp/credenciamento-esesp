<?php
/**
 * Bulk Import Courses from PDF
 * Extracts course names from PDF and creates courses for selected docentes
 */

require_once __DIR__ . '/includes/init.php';

$db = PesquisaDatabase::getInstance();
$userName = getCurrentUserName();
$userCPF = getCurrentUserCPF();
$isAdmin = isPesquisaAdmin();

if (!$isAdmin) {
    header('Location: index.php');
    exit;
}

// Process PDF upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_file'])) {
    try {
        if (empty($_FILES['pdf_file']['tmp_name'])) {
            throw new Exception("Nenhum arquivo enviado");
        }
        
        $pdfPath = $_FILES['pdf_file']['tmp_name'];
        $category = $_POST['category'] ?? 'Agenda Esesp';
        $month = (int)($_POST['month'] ?? date('n'));
        $year = (int)($_POST['year'] ?? date('Y'));
        
        // Extract text from PDF
        $pdfText = extractTextFromPDF($pdfPath);
        
        // Parse course names (one per line or separated by common delimiters)
        $courseNames = parseCoursesFromText($pdfText);
        
        if (empty($courseNames)) {
            throw new Exception("Nenhum curso encontrado no PDF. Verifique o formato do arquivo.");
        }
        
        // Store in session for next step
        $_SESSION['bulk_import'] = [
            'courses' => $courseNames,
            'category' => $category,
            'month' => $month,
            'year' => $year,
            'pdf_filename' => $_FILES['pdf_file']['name']
        ];
        
        setFlashMessage(count($courseNames) . " curso(s) extraído(s) do PDF. Agora selecione os docentes.", 'success');
        header('Location: bulk-import.php?step=2');
        exit;
        
    } catch (Exception $e) {
        setFlashMessage("Erro ao processar PDF: " . $e->getMessage(), 'danger');
    }
}

// Process course creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_courses'])) {
    try {
        if (!isset($_SESSION['bulk_import'])) {
            throw new Exception("Dados de importação não encontrados. Por favor, faça o upload novamente.");
        }
        
        $importData = $_SESSION['bulk_import'];
        $courses = $importData['courses'];
        $category = $importData['category'];
        $month = $importData['month'];
        $year = $importData['year'];
        
        // Get selected docentes (CPF and names)
        $docentesCPF = $_POST['docente_cpf'] ?? [];
        $docentesNames = $_POST['docente_name'] ?? [];
        
        if (count($docentesCPF) !== count($courses)) {
            throw new Exception("Número de docentes não corresponde ao número de cursos");
        }
        
        $db->beginTransaction();
        $createdCount = 0;
        $errors = [];
        
        for ($i = 0; $i < count($courses); $i++) {
            try {
                $courseName = trim($courses[$i]);
                $docenteCPF = !empty($docentesCPF[$i]) ? preg_replace('/[^0-9]/', '', $docentesCPF[$i]) : null;
                $docenteName = trim($docentesNames[$i] ?? '');
                
                if (empty($courseName) || empty($docenteName)) {
                    continue;
                }
                
                // Generate token
                $token = generateCourseToken($courseName, $month, $year);
                
                // Check if exists
                $existing = $db->fetchOne("SELECT id FROM courses WHERE token = ?", [$token]);
                if ($existing) {
                    $token .= '-' . substr(md5(microtime() . $i), 0, 4);
                }
                
                // Insert course
                $courseId = $db->insert('courses', [
                    'token' => $token,
                    'name' => $courseName,
                    'docente_name' => $docenteName,
                    'docente_cpf' => $docenteCPF,
                    'category' => $category,
                    'month' => $month,
                    'year' => $year,
                    'description' => "Curso importado de PDF - {$importData['pdf_filename']}",
                    'max_responses' => null,
                    'is_active' => true,
                    'created_by' => $userCPF
                ]);
                
                // Initialize analytics
                $db->insert('analytics_cache', [
                    'course_id' => $courseId,
                    'response_count' => 0
                ]);
                
                // Generate QR Code
                generateQRCode($courseId, $token);
                
                $createdCount++;
                
            } catch (Exception $e) {
                $errors[] = "Erro no curso '$courseName': " . $e->getMessage();
            }
        }
        
        $db->commit();
        
        // Clear session data
        unset($_SESSION['bulk_import']);
        
        $message = "$createdCount curso(s) criado(s) com sucesso!";
        if (!empty($errors)) {
            $message .= " Alguns erros ocorreram: " . implode('; ', $errors);
        }
        
        setFlashMessage($message, 'success');
        header('Location: courses.php');
        exit;
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollback();
        }
        setFlashMessage("Erro ao criar cursos: " . $e->getMessage(), 'danger');
    }
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$importData = $_SESSION['bulk_import'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importação em Massa - <?= PESQUISA_SITE_NAME ?></title>
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
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .step {
            display: flex;
            align-items: center;
            padding: 0 2rem;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 1rem;
        }
        
        .step.active .step-number {
            background: var(--primary);
            color: white;
        }
        
        .step.completed .step-number {
            background: #10b981;
            color: white;
        }
        
        .course-item {
            background: #f9fafb;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .course-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            text-align: center;
            line-height: 30px;
            font-weight: 600;
            margin-right: 1rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <div class="container">
            <h1><i class="bi bi-file-earmark-arrow-up me-2"></i>Importação em Massa de Cursos</h1>
            <p class="mb-0">Extraia cursos de PDF e atribua docentes</p>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container mb-5">
        <?= displayFlashMessage() ?>
        
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'completed' : '' ?>">
                <div class="step-number">1</div>
                <div>Upload PDF</div>
            </div>
            <div style="width: 100px; height: 2px; background: #e5e7eb; margin: 0 1rem; align-self: center;"></div>
            <div class="step <?= $step >= 2 ? 'active' : '' ?>">
                <div class="step-number">2</div>
                <div>Atribuir Docentes</div>
            </div>
        </div>
        
        <?php if ($step === 1): ?>
            <!-- Step 1: Upload PDF -->
            <div class="card">
                <div class="card-body p-4">
                    <h3 class="mb-4">Passo 1: Fazer Upload do PDF</h3>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Arquivo PDF *</label>
                                <input type="file" name="pdf_file" class="form-control" accept=".pdf" required>
                                <small class="text-muted">
                                    O PDF deve conter uma lista de cursos, um por linha.
                                </small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Categoria *</label>
                                <select name="category" class="form-select" required>
                                    <option value="Agenda Esesp">Agenda Esesp</option>
                                    <option value="Esesp na Estrada">Esesp na Estrada</option>
                                    <option value="Pós-Graduação">Pós-Graduação</option>
                                    <option value="Demanda Específica">Demanda Específica</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mês *</label>
                                <select name="month" class="form-select" required>
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>>
                                            <?= ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                                                 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'][$m-1] ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ano *</label>
                                <select name="year" class="form-select" required>
                                    <?php for ($y = date('Y'); $y <= date('Y') + 2; $y++): ?>
                                        <option value="<?= $y ?>"><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Formato esperado:</strong> O PDF deve conter os nomes dos cursos, 
                            preferencialmente um por linha. O sistema tentará extrair automaticamente.
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-arrow-right me-2"></i>Processar PDF
                            </button>
                            <a href="courses.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-2"></i>Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
        <?php elseif ($step === 2 && $importData): ?>
            <!-- Step 2: Assign Docentes -->
            <div class="card">
                <div class="card-body p-4">
                    <h3 class="mb-4">Passo 2: Atribuir Docentes aos Cursos</h3>
                    
                    <div class="alert alert-success mb-4">
                        <i class="bi bi-check-circle me-2"></i>
                        <strong><?= count($importData['courses']) ?> curso(s) extraído(s)</strong> de 
                        <code><?= htmlspecialchars($importData['pdf_filename']) ?></code>
                        <br>
                        <small>
                            Categoria: <?= $importData['category'] ?> | 
                            Período: <?= ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                                         'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'][$importData['month']-1] ?>/<?= $importData['year'] ?>
                        </small>
                    </div>
                    
                    <form method="POST" id="assignForm">
                        <input type="hidden" name="create_courses" value="1">
                        
                        <?php foreach ($importData['courses'] as $index => $courseName): ?>
                            <div class="course-item">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="course-number"><?= $index + 1 ?></span>
                                    <strong><?= htmlspecialchars($courseName) ?></strong>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-8">
                                        <label class="form-label small">Docente</label>
                                        <select class="form-select docente-select" 
                                                name="docente_cpf[]" 
                                                data-index="<?= $index ?>"
                                                required>
                                            <option value="">Digite o nome ou CPF...</option>
                                        </select>
                                        <input type="hidden" name="docente_name[]" id="docente_name_<?= $index ?>">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-check-circle me-2"></i>Criar <?= count($importData['courses']) ?> Curso(s)
                            </button>
                            <a href="bulk-import.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Voltar
                            </a>
                            <a href="courses.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-2"></i>Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Dados de importação não encontrados. Por favor, comece pelo upload do PDF.
            </div>
            <a href="bulk-import.php" class="btn btn-primary">
                <i class="bi bi-arrow-left me-2"></i>Voltar ao Início
            </a>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize all docente selects
            $('.docente-select').each(function() {
                const index = $(this).data('index');
                $(this).select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Digite o nome ou CPF do docente...',
                    minimumInputLength: 2,
                    ajax: {
                        url: 'api/search-docentes.php',
                        dataType: 'json',
                        delay: 300,
                        data: function (params) {
                            return {
                                q: params.term,
                                page: params.page || 1
                            };
                        },
                        processResults: function (data) {
                            return {
                                results: data.results,
                                pagination: {
                                    more: data.pagination.more
                                }
                            };
                        },
                        cache: true
                    },
                    templateResult: formatDocente,
                    templateSelection: formatDocenteSelection
                }).on('select2:select', function(e) {
                    const data = e.params.data;
                    $('#docente_name_' + index).val(data.name);
                });
            });
        });

        function formatDocente(docente) {
            if (docente.loading) {
                return docente.text;
            }
            
            return $('<div>' +
                '<div><strong>' + docente.name + '</strong></div>' +
                '<div class="text-muted small">CPF: ' + docente.cpf_formatted + '</div>' +
                '</div>');
        }

        function formatDocenteSelection(docente) {
            return docente.name || docente.text;
        }
    </script>
</body>
</html>
<?php
/**
 * Extract text from PDF using different methods
 */
function extractTextFromPDF($pdfPath) {
    // Try using pdftotext command if available
    if (function_exists('shell_exec')) {
        $output = shell_exec("pdftotext -layout '$pdfPath' -");
        if (!empty($output)) {
            return $output;
        }
    }
    
    // Try using PHP library (would need to be installed)
    // For now, return simple instruction
    throw new Exception("Não foi possível extrair texto do PDF. Por favor, instale pdftotext ou use entrada manual.");
}

/**
 * Parse course names from extracted text
 */
function parseCoursesFromText($text) {
    // Split by newlines
    $lines = explode("\n", $text);
    $courses = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip empty lines and very short lines
        if (strlen($line) < 5) {
            continue;
        }
        
        // Skip lines that look like headers or numbers only
        if (preg_match('/^[\d\.\-\s]+$/', $line)) {
            continue;
        }
        
        $courses[] = $line;
    }
    
    return array_unique($courses);
}
?>