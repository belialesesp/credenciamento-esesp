<?php
/**
 * Bulk Import Courses from Excel (Agenda Esesp)
 * Extracts course names from monthly planning spreadsheet and creates courses
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

// Process Excel upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    try {
        if (empty($_FILES['excel_file']['tmp_name'])) {
            throw new Exception("Nenhum arquivo enviado");
        }
        
        $excelPath = $_FILES['excel_file']['tmp_name'];
        $fileName = $_FILES['excel_file']['name'];
        
        // Extract month and year from filename (format: XX_PLANEJAMENTO_DE_CURSOS_MES_YEAR.xlsx)
        $month = null;
        $year = null;
        
        if (preg_match('/(\w+)_(\d{4})/i', $fileName, $matches)) {
            $monthName = strtoupper($matches[1]);
            $year = (int)$matches[2];
            
            $monthMap = [
                'JANEIRO' => 1, 'FEVEREIRO' => 2, 'MARCO' => 3, 'MARÇO' => 3,
                'ABRIL' => 4, 'MAIO' => 5, 'JUNHO' => 6,
                'JULHO' => 7, 'AGOSTO' => 8, 'SETEMBRO' => 9,
                'OUTUBRO' => 10, 'NOVEMBRO' => 11, 'DEZEMBRO' => 12
            ];
            
            $month = $monthMap[$monthName] ?? null;
        }
        
        // Fallback to manual selection if not detected
        if (!$month) {
            $month = (int)($_POST['month'] ?? date('n'));
        }
        if (!$year) {
            $year = (int)($_POST['year'] ?? date('Y'));
        }
        
        // Extract courses from Excel
        $courses = extractCoursesFromExcel($excelPath);
        
        if (empty($courses)) {
            throw new Exception("Nenhum curso encontrado no arquivo Excel. Verifique o formato.");
        }
        
        // Store in session for next step
        $_SESSION['bulk_import'] = [
            'courses' => $courses,
            'category' => 'Agenda Esesp',
            'month' => $month,
            'year' => $year,
            'excel_filename' => $fileName
        ];
        
        setFlashMessage(count($courses) . " curso(s) extraído(s) do Excel. Agora selecione os docentes.", 'success');
        header('Location: bulk-import-excel.php?step=2');
        exit;
        
    } catch (Exception $e) {
        setFlashMessage("Erro ao processar Excel: " . $e->getMessage(), 'danger');
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
                    'description' => "Curso Agenda Esesp - Importado de {$importData['excel_filename']}",
                    'max_responses' => null,
                    'is_active' => true,
                    'created_by' => $userCPF,
                    'sync_source' => 'excel'
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
            $message .= " Alguns erros ocorreram: " . implode('; ', array_slice($errors, 0, 3));
        }
        
        setFlashMessage($message, 'success');
        header('Location: courses.php?category=Agenda+Esesp&month=' . $month . '&year=' . $year);
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
    <title>Importação Agenda Esesp - <?= PESQUISA_SITE_NAME ?></title>
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
            border-left: 4px solid var(--primary);
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

        .upload-zone {
            border: 3px dashed #cbd5e1;
            border-radius: 12px;
            padding: 3rem;
            text-align: center;
            background: #f8fafc;
            transition: all 0.3s;
        }

        .upload-zone:hover {
            border-color: var(--primary);
            background: #f1f5f9;
        }

        .upload-zone i {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <div class="container">
            <h1><i class="bi bi-file-earmark-spreadsheet me-2"></i>Importação em Massa - Agenda Esesp</h1>
            <p class="mb-0">Importar cursos do planejamento mensal (Excel)</p>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container mb-5">
        <?= displayFlashMessage() ?>
        
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'completed' : '' ?>">
                <div class="step-number">1</div>
                <div>Upload Excel</div>
            </div>
            <div style="width: 100px; height: 2px; background: #e5e7eb; margin: 0 1rem; align-self: center;"></div>
            <div class="step <?= $step >= 2 ? 'active' : '' ?>">
                <div class="step-number">2</div>
                <div>Atribuir Docentes</div>
            </div>
        </div>
        
        <?php if ($step === 1): ?>
            <!-- Step 1: Upload Excel -->
            <div class="card">
                <div class="card-body p-4">
                    <h3 class="mb-4">Passo 1: Fazer Upload do Planejamento Mensal</h3>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Formato esperado:</strong> Arquivo Excel (.xlsx) com o planejamento mensal de cursos da Agenda Esesp. 
                        O sistema extrairá automaticamente os nomes dos cursos da coluna correta.
                    </div>

                    <div class="alert alert-warning">
                        <i class="bi bi-lightbulb me-2"></i>
                        <strong>Nomenclatura do arquivo:</strong> Use o formato 
                        <code>XX_PLANEJAMENTO_DE_CURSOS_MES_ANO.xlsx</code> para detecção automática do período.
                        <br>Exemplo: <code>02_PLANEJAMENTO_DE_CURSOS_FEVEREIRO_2026.xlsx</code>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="upload-zone mb-4">
                            <i class="bi bi-cloud-upload"></i>
                            <h4>Arraste o arquivo aqui ou clique para selecionar</h4>
                            <p class="text-muted mb-3">Somente arquivos .xlsx (Excel 2007+)</p>
                            <input type="file" name="excel_file" id="excel_file" 
                                   class="form-control d-none" accept=".xlsx" required 
                                   onchange="updateFileName(this)">
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('excel_file').click()">
                                <i class="bi bi-folder2-open me-2"></i>Selecionar Arquivo
                            </button>
                            <div id="file-name" class="mt-3 text-muted"></div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mês (se não detectado automaticamente)</label>
                                <select name="month" class="form-select">
                                    <?php foreach ($months as $num => $name): ?>
                                        <option value="<?= $num ?>" <?= $num == date('n') ? 'selected' : '' ?>>
                                            <?= $name ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Será sobrescrito se o arquivo tiver o mês no nome</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ano (se não detectado automaticamente)</label>
                                <select name="year" class="form-select">
                                    <?php for ($y = date('Y'); $y <= date('Y') + 2; $y++): ?>
                                        <option value="<?= $y ?>"><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                                <small class="text-muted">Será sobrescrito se o arquivo tiver o ano no nome</small>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-arrow-right me-2"></i>Processar Excel
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
                        <code><?= htmlspecialchars($importData['excel_filename']) ?></code>
                        <br>
                        <small>
                            <strong>Categoria:</strong> <?= $importData['category'] ?> | 
                            <strong>Período:</strong> <?= $months[$importData['month']] ?>/<?= $importData['year'] ?>
                        </small>
                    </div>
                    
                    <form method="POST" id="assignForm">
                        <input type="hidden" name="create_courses" value="1">
                        
                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyFirstDocenteToAll()">
                                <i class="bi bi-copy me-1"></i>Copiar primeiro docente para todos
                            </button>
                            <small class="text-muted ms-2">Útil quando todos os cursos são do mesmo docente</small>
                        </div>

                        <?php foreach ($importData['courses'] as $index => $courseName): ?>
                            <div class="course-item">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="course-number"><?= $index + 1 ?></span>
                                    <strong><?= htmlspecialchars($courseName) ?></strong>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="form-label small">Docente Responsável</label>
                                        <select class="form-select docente-select" 
                                                name="docente_name[]" 
                                                data-index="<?= $index ?>"
                                                required>
                                            <option value="">Digite ou selecione...</option>
                                            <?php 
                                            // Get existing docentes for autocomplete
                                            $existingDocentes = $db->fetchAll("
                                                SELECT DISTINCT docente_name 
                                                FROM courses 
                                                WHERE docente_name IS NOT NULL 
                                                ORDER BY docente_name
                                            ");
                                            foreach ($existingDocentes as $doc): 
                                            ?>
                                                <option value="<?= htmlspecialchars($doc['docente_name']) ?>">
                                                    <?= htmlspecialchars($doc['docente_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Digite e pressione Enter para adicionar novo
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-check-circle me-2"></i>Criar <?= count($importData['courses']) ?> Curso(s)
                            </button>
                            <a href="bulk-import-excel.php" class="btn btn-secondary">
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
                Dados de importação não encontrados. Por favor, comece pelo upload do Excel.
            </div>
            <a href="bulk-import-excel.php" class="btn btn-primary">
                <i class="bi bi-arrow-left me-2"></i>Voltar ao Início
            </a>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        function updateFileName(input) {
            const fileName = input.files[0]?.name || '';
            const fileNameDiv = document.getElementById('file-name');
            if (fileName) {
                fileNameDiv.innerHTML = '<i class="bi bi-file-earmark-spreadsheet me-2"></i><strong>Arquivo selecionado:</strong> ' + fileName;
                fileNameDiv.classList.add('alert', 'alert-info');
            }
        }

        function copyFirstDocenteToAll() {
            const firstSelect = $('.docente-select').first();
            const firstValue = firstSelect.val();
            const firstName = firstSelect.find('option:selected').text();
            
            if (!firstValue) {
                alert('Por favor, selecione um docente no primeiro curso primeiro.');
                return;
            }
            
            if (!confirm('Copiar "' + firstName + '" para todos os cursos?')) {
                return;
            }
            
            $('.docente-select').each(function(index) {
                // Add option if doesn't exist
                if ($(this).find(`option[value="${firstValue}"]`).length === 0) {
                    $(this).append(new Option(firstName, firstValue, true, true));
                } else {
                    $(this).val(firstValue);
                }
                $(this).trigger('change');
                $('#docente_name_' + index).val(firstName);
            });
        }

        $(document).ready(function() {
            // Initialize all docente selects with simple autocomplete
            $('.docente-select').each(function() {
                $(this).select2({
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
            });
        });
    </script>
</body>
</html>
<?php
/**
 * Extract course names from Excel file
 */
function extractCoursesFromExcel($filePath) {
    // Check if we can use a PHP library or need external tool
    if (!class_exists('ZipArchive')) {
        throw new Exception("A extensão ZipArchive não está disponível. Necessária para ler arquivos Excel.");
    }
    
    // Try to use PhpSpreadsheet if available
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
        
        if (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            return extractWithPhpSpreadsheet($filePath);
        }
    }
    
    // Fallback: use Python if available
    if (function_exists('shell_exec')) {
        $pythonScript = __DIR__ . '/scripts/extract_excel_courses.py';
        if (file_exists($pythonScript)) {
            $output = shell_exec("python3 '$pythonScript' '$filePath' 2>&1");
            if ($output) {
                $courses = json_decode($output, true);
                if (is_array($courses)) {
                    return $courses;
                }
            }
        }
    }
    
    throw new Exception("Nenhum método disponível para processar Excel. Instale PhpSpreadsheet ou Python.");
}

/**
 * Extract courses using PhpSpreadsheet library
 */
function extractWithPhpSpreadsheet($filePath) {
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        $courses = [];
        $maxRow = $worksheet->getHighestRow();
        
        // Start from row 4 (after headers), column D (4)
        for ($row = 4; $row <= $maxRow; $row++) {
            $courseName = $worksheet->getCell([4, $row])->getValue(); // Column D
            
            if ($courseName && is_string($courseName)) {
                $courseName = trim($courseName);
                
                // Skip if too short or contains skip keywords
                if (strlen($courseName) < 5) {
                    continue;
                }
                
                $skipKeywords = ['CUSTO', 'HORAS AULA', 'TOTAL', 'SUBTOTAL', 'SOMA'];
                $shouldSkip = false;
                foreach ($skipKeywords as $keyword) {
                    if (stripos($courseName, $keyword) !== false) {
                        $shouldSkip = true;
                        break;
                    }
                }
                
                if (!$shouldSkip) {
                    $courses[] = $courseName;
                }
            }
        }
        
        return $courses;
    } catch (Exception $e) {
        error_log("PhpSpreadsheet error: " . $e->getMessage());
        throw new Exception("Erro ao processar Excel: " . $e->getMessage());
    }
}
?>