<?php
/**
 * Courses Management Page
 * List all courses with actions to view details, QR codes, etc.
 */

require_once __DIR__ . '/includes/init.php';

$db = PesquisaDatabase::getInstance();
$userName = getCurrentUserName();
$isAdmin = isPesquisaAdmin();

// Get filter parameters
$filterCategory = $_GET['category'] ?? '';
$filterStatus = $_GET['status'] ?? 'all';
$filterDocenteCPF = $_GET['docente_cpf'] ?? '';
$filterMonth = isset($_GET['month']) ? (int)$_GET['month'] : null;
$filterYear = isset($_GET['year']) ? (int)$_GET['year'] : null;
$search = $_GET['search'] ?? '';

// Build WHERE clause
$where = ['1=1'];
$params = [];

if (!empty($filterCategory)) {
    $where[] = 'c.category = ?';
    $params[] = $filterCategory;
}

if ($filterStatus === 'active') {
    $where[] = 'c.is_active = 1';
} elseif ($filterStatus === 'inactive') {
    $where[] = 'c.is_active = 0';
}

if (!empty($filterDocenteCPF)) {
    $cleanValue = preg_replace('/[^0-9]/', '', $filterDocenteCPF);
    
    // If it's numeric (CPF), search by CPF
    if (!empty($cleanValue) && strlen($cleanValue) == 11) {
        $where[] = 'c.docente_cpf = ?';
        $params[] = $cleanValue;
    } else {
        // Otherwise, search by name (manual entry or partial name)
        $where[] = 'c.docente_name LIKE ?';
        $params[] = '%' . $filterDocenteCPF . '%';
    }
}

if ($filterMonth) {
    $where[] = 'c.month = ?';
    $params[] = $filterMonth;
}

if ($filterYear) {
    $where[] = 'c.year = ?';
    $params[] = $filterYear;
}

if (!empty($search)) {
    $where[] = '(c.name LIKE ? OR c.docente_name LIKE ? OR c.token LIKE ?)';
    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = implode(' AND ', $where);

// Get all courses
$courses = $db->fetchAll("
    SELECT c.*, 
           ac.overall_score, 
           ac.response_count,
           ac.pedagogical_score,
           ac.didactic_score,
           ac.infrastructure_score
    FROM courses c
    LEFT JOIN analytics_cache ac ON c.id = ac.course_id
    WHERE $whereClause
    ORDER BY c.created_at DESC
", $params);

$months = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

// Generate year options
$currentYear = (int)date('Y');
$yearOptions = range(2020, $currentYear + 1);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Cursos - <?= PESQUISA_SITE_NAME ?></title>
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
        
        .filter-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .course-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .course-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .course-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }
        
        .course-meta {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .course-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: #6b7280;
            text-transform: uppercase;
        }
        
        .course-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .badge-category {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .filter-section-title {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="bi bi-book me-2"></i>Gerenciar Cursos</h1>
                    <p class="mb-0">Visualize e gerencie todos os cursos cadastrados</p>
                </div>
                <div>
                    <a href="create-course.php" class="btn btn-light btn-lg">
                        <i class="bi bi-plus-circle me-2"></i>Novo Curso
                    </a>
                    <a href="bulk-import-excel.php" class="btn btn-outline-light">
                        <i class="bi bi-file-earmark-spreadsheet me-2"></i>Importar Excel (Agenda Esesp)
                    </a>
                    <a href="index.php" class="btn btn-outline-light">
                        <i class="bi bi-arrow-left me-2"></i>Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container mb-5">
        <?= displayFlashMessage() ?>
        
        <!-- Filters -->
        <div class="filter-card">
            <form method="GET" action="" class="row g-3">
                <!-- First Row: Basic Filters -->
                <div class="col-12">
                    <div class="filter-section-title">
                        <i class="bi bi-funnel"></i> Filtros Básicos
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Nome, docente ou token" 
                           value="<?= sanitize($search) ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Categoria</label>
                    <select name="category" class="form-select">
                        <option value="">Todas</option>
                        <option value="Agenda Esesp" <?= $filterCategory === 'Agenda Esesp' ? 'selected' : '' ?>>Agenda Esesp</option>
                        <option value="Esesp na Estrada" <?= $filterCategory === 'Esesp na Estrada' ? 'selected' : '' ?>>Esesp na Estrada</option>
                        <option value="EAD" <?= $filterCategory === 'EAD' ? 'selected' : '' ?>>EAD</option>
                        <option value="Pós-Graduação" <?= $filterCategory === 'Pós-Graduação' ? 'selected' : '' ?>>Pós-Graduação</option>
                        <option value="Demanda Específica" <?= $filterCategory === 'Demanda Específica' ? 'selected' : '' ?>>Demanda Específica</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Todos</option>
                        <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Ativos</option>
                        <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>Inativos</option>
                    </select>
                </div>

                <!-- Second Row: Advanced Filters -->
                <div class="col-12 mt-3">
                    <div class="filter-section-title">
                        <i class="bi bi-sliders"></i> Filtros Avançados
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">
                        <i class="bi bi-person-badge me-1"></i>Docente (CPF)
                    </label>
                    <select id="docente_cpf_filter" name="docente_cpf" class="form-select">
                        <?php if (!empty($filterDocenteCPF)): ?>
                            <option value="<?= sanitize($filterDocenteCPF) ?>" selected>
                                <?= formatCPF($filterDocenteCPF) ?>
                            </option>
                        <?php else: ?>
                            <option value="">Digite o nome ou CPF...</option>
                        <?php endif; ?>
                    </select>
                    <small class="text-muted">Busca docentes do e-flow</small>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">
                        <i class="bi bi-calendar-month me-1"></i>Mês
                    </label>
                    <select name="month" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($months as $num => $name): ?>
                            <option value="<?= $num ?>" <?= $filterMonth === $num ? 'selected' : '' ?>>
                                <?= $name ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">
                        <i class="bi bi-calendar-event me-1"></i>Ano
                    </label>
                    <select name="year" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($yearOptions as $yearOpt): ?>
                            <option value="<?= $yearOpt ?>" <?= $filterYear === $yearOpt ? 'selected' : '' ?>>
                                <?= $yearOpt ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-5">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel me-2"></i>Filtrar
                        </button>
                        <a href="courses.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-2"></i>Limpar
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Active Filters Display -->
        <?php 
        $activeFilters = [];
        if (!empty($filterCategory)) $activeFilters[] = "Categoria: $filterCategory";
        if ($filterStatus !== 'all') $activeFilters[] = "Status: " . ($filterStatus === 'active' ? 'Ativos' : 'Inativos');
        if (!empty($filterDocenteCPF)) $activeFilters[] = "CPF: " . formatCPF($filterDocenteCPF);
        if ($filterMonth) $activeFilters[] = "Mês: " . $months[$filterMonth];
        if ($filterYear) $activeFilters[] = "Ano: $filterYear";
        if (!empty($search)) $activeFilters[] = "Busca: $search";
        
        if (!empty($activeFilters)): ?>
            <div class="alert alert-info d-flex align-items-center">
                <i class="bi bi-info-circle me-2 fs-5"></i>
                <div>
                    <strong>Filtros ativos:</strong> <?= implode(' | ', $activeFilters) ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Courses List -->
        <div class="mb-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?= count($courses) ?> curso(s) encontrado(s)</h5>
        </div>
        
        <?php if (empty($courses)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h3>Nenhum curso encontrado</h3>
                <p>Comece criando seu primeiro curso de pesquisa de satisfação.</p>
                <a href="create-course.php" class="btn btn-primary btn-lg mt-3">
                    <i class="bi bi-plus-circle me-2"></i>Criar Primeiro Curso
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($courses as $course): ?>
                <div class="course-card">
                    <div class="course-header">
                        <div>
                            <div class="course-title"><?= sanitize($course['name']) ?></div>
                            <div class="course-meta">
                                <i class="bi bi-person me-1"></i><?= sanitize($course['docente_name']) ?>
                                <?php if (!empty($course['docente_cpf'])): ?>
                                    <span class="mx-2">•</span>
                                    <i class="bi bi-person-badge me-1"></i>CPF: <?= formatCPF($course['docente_cpf']) ?>
                                <?php endif; ?>
                                <span class="mx-2">•</span>
                                <i class="bi bi-calendar me-1"></i><?= $months[$course['month']] ?>/<?= $course['year'] ?>
                                <span class="mx-2">•</span>
                                <i class="bi bi-tag me-1"></i><code><?= sanitize($course['token']) ?></code>
                            </div>
                        </div>
                        <div>
                            <span class="badge-category bg-<?= $course['is_active'] ? 'success' : 'secondary' ?>">
                                <?= $course['is_active'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($course['response_count'] > 0): ?>
                        <div class="course-stats">
                            <div class="stat-item">
                                <div class="stat-value"><?= number_format($course['overall_score'], 1) ?>%</div>
                                <div class="stat-label">Score Geral</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?= $course['response_count'] ?></div>
                                <div class="stat-label">Respostas</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?= number_format($course['pedagogical_score'], 1) ?>%</div>
                                <div class="stat-label">Pedagógico</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?= number_format($course['didactic_score'], 1) ?>%</div>
                                <div class="stat-label">Didático</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?= number_format($course['infrastructure_score'], 1) ?>%</div>
                                <div class="stat-label">Infraestrutura</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-info-circle me-2"></i>Ainda não há respostas para este curso.
                        </div>
                    <?php endif; ?>
                    
                    <div class="course-actions">
                        <a href="course-details.php?id=<?= $course['id'] ?>" class="btn btn-primary btn-sm">
                            <i class="bi bi-eye me-1"></i>Ver Detalhes
                        </a>
                        
                        <?php if ($course['qr_code_path'] && file_exists(__DIR__ . '/' . $course['qr_code_path'])): ?>
                            <a href="<?= sanitize($course['qr_code_path']) ?>" download class="btn btn-success btn-sm">
                                <i class="bi bi-qr-code me-1"></i>Download QR Code
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($course['survey_url']): ?>
                            <button class="btn btn-info btn-sm" onclick="copyLink('<?= sanitize($course['survey_url']) ?>')">
                                <i class="bi bi-link-45deg me-1"></i>Copiar Link
                            </button>
                            <a href="<?= sanitize($course['survey_url']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Abrir Pesquisa
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($course['response_count'] > 0): ?>
                            <a href="analytics.php?course_id=<?= $course['id'] ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-graph-up me-1"></i>Ver Analytics
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        function copyLink(url) {
            navigator.clipboard.writeText(url).then(() => {
                alert('Link copiado para a área de transferência!\n\n' + url);
            }).catch(err => {
                prompt('Copie o link:', url);
            });
        }

        // Initialize Select2 for docente CPF filter with AJAX
        $(document).ready(function() {
            $('#docente_cpf_filter').select2({
                theme: 'bootstrap-5',
                placeholder: 'Digite o nome ou CPF do docente...',
                allowClear: true,
                minimumInputLength: 2,
                tags: true, // Allow manual entry for filtering
                createTag: function (params) {
                    var term = $.trim(params.term);
                    
                    if (term.length < 3) {
                        return null;
                    }
                    
                    return {
                        id: term, // Use the text as ID for filtering
                        text: term + ' (buscar por nome)',
                        name: term,
                        cpf: term,
                        cpf_formatted: term,
                        source: 'manual'
                    };
                },
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
            });
        });

        function formatDocente(docente) {
            if (docente.loading) {
                return docente.text;
            }
            
            return $('<div class="select2-result-docente">' +
                '<div class="select2-result-docente__title">' + docente.name + '</div>' +
                '<div class="select2-result-docente__cpf text-muted small">CPF: ' + docente.cpf_formatted + '</div>' +
                '</div>');
        }

        function formatDocenteSelection(docente) {
            return docente.name || docente.text;
        }
    </script>
</body>
</html>
<?php
// Helper function to format CPF
function formatCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) == 11) {
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }
    return $cpf;
}
?>