<?php
/**
 * Analytics Dashboard
 * Admin filters by category and month → Sees real-time results
 * Can generate visual diagrams
 */

require_once __DIR__ . '/includes/init.php';

$db = PesquisaDatabase::getInstance();
$userName = getCurrentUserName();
$isAdmin = isPesquisaAdmin();

// Get filter parameters
$filterCategory = $_GET['category'] ?? '';
$filterMonth = isset($_GET['month']) ? (int)$_GET['month'] : null;
$filterYear = isset($_GET['year']) ? (int)$_GET['year'] : 2024;
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : null;
$filterLowScores = isset($_GET['low_scores']) && $_GET['low_scores'] === 'true';

// Build WHERE clause
$where = ['c.is_active = 1'];
$params = [];

if (!empty($filterCategory)) {
    $where[] = 'c.category = ?';
    $params[] = $filterCategory;
}

if ($filterMonth) {
    $where[] = 'c.month = ?';
    $params[] = $filterMonth;
}

if ($filterYear) {
    $where[] = 'c.year = ?';
    $params[] = $filterYear;
}

if ($courseId) {
    $where[] = 'c.id = ?';
    $params[] = $courseId;
}

// Low scores filter - show only courses with score < 80%
if ($filterLowScores) {
    $where[] = '(ac.overall_score < 80 OR ac.pedagogical_score < 80 OR ac.didactic_score < 80 OR ac.infrastructure_score < 80)';
}

$whereClause = implode(' AND ', $where);

// Get courses with analytics
$courses = $db->fetchAll("
    SELECT c.*, ac.overall_score, ac.pedagogical_score, ac.didactic_score, 
           ac.infrastructure_score, ac.response_count
    FROM courses c
    LEFT JOIN analytics_cache ac ON c.id = ac.course_id
    WHERE $whereClause
    ORDER BY c.year DESC, c.month DESC, c.name
", $params);

// Get summary statistics
$stats = $db->fetchOne("
    SELECT 
        COUNT(DISTINCT c.id) as total_courses,
        SUM(ac.response_count) as total_responses,
        AVG(ac.overall_score) as avg_score,
        AVG(ac.pedagogical_score) as avg_pedagogical,
        AVG(ac.didactic_score) as avg_didactic,
        AVG(ac.infrastructure_score) as avg_infrastructure
    FROM courses c
    LEFT JOIN analytics_cache ac ON c.id = ac.course_id
    WHERE $whereClause
", $params);

// Handle diagram generation
if (isset($_POST['generate_diagram']) && isset($_POST['course_id'])) {
    $courseIdForDiagram = (int)$_POST['course_id'];
    
    // Get course analytics to check scores
    $analytics = $db->fetchOne("SELECT * FROM analytics_cache WHERE course_id = ?", [$courseIdForDiagram]);
    
    $diagramFile = generateDiagram($courseIdForDiagram);
    
    if ($diagramFile) {
        // Format message with proper HTML
        $result = formatDiagramMessage($analytics);
        setFlashMessage($result['message'], $result['type'], true); // Allow HTML
    } else {
        setFlashMessage("Erro ao gerar diagrama. Verifique os logs.", 'error');
    }
    
    header("Location: analytics.php?course_id=$courseIdForDiagram");
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - <?= PESQUISA_SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .stat-card .number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
        }
        
        .stat-card .label {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .course-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .course-card-clickable {
            cursor: pointer;
        }
        
        .course-card-clickable:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            border: 2px solid #1e3a5f;
        }
        
        .course-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .score-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 1.25rem;
        }
        
        .score-badge.excellence { background: #d1fae5; color: #059669; }
        .score-badge.very-good { background: #dbeafe; color: #2563eb; }
        .score-badge.adequate { background: #fef3c7; color: #d97806; }
        .score-badge.intervention { background: #fee2e2; color: #dc2626; }
        
        .category-score {
            margin: 0.5rem 0;
        }
        
        .category-score .progress {
            height: 8px;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="bi bi-graph-up me-2"></i>Analytics Dashboard</h1>
                    <p class="mb-0">Análises em tempo real das pesquisas de satisfação</p>
                </div>
                <a href="index.php" class="btn btn-light">
                    <i class="bi bi-arrow-left me-2"></i>Voltar
                </a>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container mb-5">
        <?= displayFlashMessage() ?>
        
        <!-- Active Filter Indicator -->
        <?php if ($filterLowScores): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Filtro Ativo:</strong> Exibindo apenas cursos com pontuação abaixo de 80% (Necessita Intervenção)
            <a href="analytics.php?year=<?= $filterYear ?>" class="btn btn-sm btn-light ms-3">
                <i class="bi bi-x-circle me-1"></i>Remover Filtro
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="filter-card">
            <!-- Quick Filters Pills -->
            <div class="mb-3">
                <label class="form-label fw-bold">Filtros Rápidos:</label>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="?low_scores=true&year=<?= $filterYear ?>" 
                       class="btn btn-sm <?= $filterLowScores ? 'btn-danger' : 'btn-outline-danger' ?>">
                        <i class="bi bi-exclamation-triangle me-1"></i>Necessita Intervenção (<80%)
                    </a>
                    <a href="?year=<?= $filterYear ?>" 
                       class="btn btn-sm <?= !$filterLowScores && empty($filterCategory) && !$filterMonth ? 'btn-primary' : 'btn-outline-primary' ?>">
                        <i class="bi bi-grid-3x3 me-1"></i>Todos os Cursos
                    </a>
                </div>
            </div>
            
            <hr class="my-3">
            
            <!-- Advanced Filters -->
            <form method="GET" action="" class="row g-3">
                <input type="hidden" name="low_scores" value="<?= $filterLowScores ? 'true' : '' ?>">
                
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
                
                <div class="col-md-3">
                    <label class="form-label">Mês</label>
                    <select name="month" class="form-select">
                        <option value="">Todos</option>
                        <?php
                        $months = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
                        for ($i = 1; $i <= 12; $i++) {
                            $selected = $filterMonth === $i ? 'selected' : '';
                            echo "<option value='$i' $selected>{$months[$i-1]}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Ano</label>
                    <select name="year" class="form-select">
                        <?php
                        for ($y = date('Y'); $y >= 2020; $y--) {
                            $selected = $filterYear === $y ? 'selected' : '';
                            echo "<option value='$y' $selected>$y</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel me-2"></i>Filtrar
                        </button>
                        <a href="analytics.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-2"></i>Limpar
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Summary Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="number"><?= number_format($stats['total_courses'] ?? 0) ?></div>
                <div class="label">Cursos Analisados</div>
            </div>
            
            <div class="stat-card">
                <div class="number"><?= number_format($stats['total_responses'] ?? 0) ?></div>
                <div class="label">Total de Respostas</div>
            </div>
            
            <div class="stat-card">
                <div class="number"><?= number_format($stats['avg_score'] ?? 0, 1) ?>%</div>
                <div class="label">Pontuação Média Geral</div>
            </div>
            
            <div class="stat-card">
                <div class="number"><?= number_format($stats['avg_pedagogical'] ?? 0, 1) ?>%</div>
                <div class="label">Média Pedagógica (35%)</div>
            </div>
            
            <div class="stat-card">
                <div class="number"><?= number_format($stats['avg_didactic'] ?? 0, 1) ?>%</div>
                <div class="label">Média Didática (40%)</div>
            </div>
            
            <div class="stat-card">
                <div class="number"><?= number_format($stats['avg_infrastructure'] ?? 0, 1) ?>%</div>
                <div class="label">Média Infraestrutura (25%)</div>
            </div>
        </div>
        
        <!-- Courses List -->
        <h3 class="mb-3">Cursos</h3>
        
        <?php if (empty($courses)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>Nenhum curso encontrado com os filtros selecionados.
            </div>
        <?php else: ?>
            <?php foreach ($courses as $course): ?>
                <?php
                $score = $course['overall_score'] ?? 0;
                $badgeClass = $score >= 90 ? 'excellence' : ($score >= 75 ? 'very-good' : ($score >= 60 ? 'adequate' : 'intervention'));
                $classification = getClassification($score);
                ?>
                
                <a href="course-details.php?id=<?= $course['id'] ?>" class="course-card-link" style="text-decoration: none; color: inherit; display: block;">
                    <div class="course-card course-card-clickable">
                        <div class="row align-items-center">
                            <div class="col-md-7">
                                <h4><?= sanitize($course['name']) ?></h4>
                                <p class="mb-2">
                                    <i class="bi bi-person me-2"></i><?= sanitize($course['docente_name']) ?><br>
                                    <i class="bi bi-tag me-2"></i><?= sanitize($course['category']) ?><br>
                                    <i class="bi bi-calendar me-2"></i><?= $months[$course['month']-1] ?? '' ?>/<?= $course['year'] ?><br>
                                    <i class="bi bi-chat-square-text me-2"></i><?= $course['response_count'] ?? 0 ?> respostas
                                </p>
                            </div>
                            
                            <div class="col-md-5 text-end">
                                <?php if ($course['overall_score']): ?>
                                    <div class="mb-3">
                                        <span class="score-badge <?= $badgeClass ?>">
                                            <?= number_format($score, 1) ?>%
                                        </span>
                                        <div><small class="text-muted"><?= $classification ?></small></div>
                                    </div>
                                    
                                    <div class="category-score">
                                        <small><strong>Pedagógico:</strong> <?= number_format($course['pedagogical_score'], 1) ?>%</small>
                                        <div class="progress">
                                            <div class="progress-bar bg-primary" style="width: <?= $course['pedagogical_score'] ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="category-score">
                                        <small><strong>Didático:</strong> <?= number_format($course['didactic_score'], 1) ?>%</small>
                                        <div class="progress">
                                            <div class="progress-bar bg-info" style="width: <?= $course['didactic_score'] ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="category-score">
                                        <small><strong>Infraestrutura:</strong> <?= number_format($course['infrastructure_score'], 1) ?>%</small>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" style="width: <?= $course['infrastructure_score'] ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <i class="bi bi-arrow-right-circle me-1"></i>
                                        <small class="text-muted">Clique para ver detalhes</small>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Sem respostas ainda</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>