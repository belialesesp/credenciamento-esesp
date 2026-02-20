<?php
/**
 * Sistema de Indicadores de Gestão ESESP
 * With category-based navigation
 */

require_once __DIR__ . '/includes/init.php';

$db = PesquisaDatabase::getInstance();
$userName = getCurrentUserName();
$isAdmin = isPesquisaAdmin();

// Get selected category filter
$selectedCategory = $_GET['category'] ?? 'all';

// Statistics per category
$categories = [
    'all' => 'Todas as Categorias',
    'Agenda Esesp' => 'Agenda Esesp',
    'Esesp na Estrada' => 'Esesp na Estrada',
    'EAD' => 'Cursos EAD',
    'Pós-Graduação' => 'Pós-Graduação',
    'Demanda Específica' => 'Demanda Específica'
];

// Build WHERE clause for category filter
$categoryWhere = '';
$categoryParams = [];
if ($selectedCategory !== 'all') {
    $categoryWhere = 'AND c.category = ?';
    $categoryParams = [$selectedCategory];
}

// Get statistics
try {
    $stats = [
        'total_courses' => $db->fetchColumn(
            "SELECT COUNT(*) FROM courses c WHERE is_active = 1 $categoryWhere",
            $categoryParams
        ) ?? 0,
        
        'total_responses' => $db->fetchColumn(
            "SELECT COUNT(*) FROM responses r 
             JOIN courses c ON r.course_id = c.id 
             WHERE 1=1 $categoryWhere",
            $categoryParams
        ) ?? 0,
        
        'courses_this_month' => $db->fetchColumn(
            "SELECT COUNT(*) FROM courses c 
             WHERE is_active = 1 AND year = ? AND month = ? $categoryWhere",
            array_merge([date('Y'), date('n')], $categoryParams)
        ) ?? 0,
        
        'avg_score' => $db->fetchColumn(
            "SELECT AVG(ac.overall_score) 
             FROM analytics_cache ac 
             JOIN courses c ON ac.course_id = c.id 
             WHERE ac.overall_score IS NOT NULL $categoryWhere",
            $categoryParams
        ) ?? 0,
        
        'avg_pedagogical' => $db->fetchColumn(
            "SELECT AVG(ac.pedagogical_score) 
             FROM analytics_cache ac 
             JOIN courses c ON ac.course_id = c.id 
             WHERE ac.pedagogical_score IS NOT NULL $categoryWhere",
            $categoryParams
        ) ?? 0,
        
        'avg_didactic' => $db->fetchColumn(
            "SELECT AVG(ac.didactic_score) 
             FROM analytics_cache ac 
             JOIN courses c ON ac.course_id = c.id 
             WHERE ac.didactic_score IS NOT NULL $categoryWhere",
            $categoryParams
        ) ?? 0,
        
        'avg_infrastructure' => $db->fetchColumn(
            "SELECT AVG(ac.infrastructure_score) 
             FROM analytics_cache ac 
             JOIN courses c ON ac.course_id = c.id 
             WHERE ac.infrastructure_score IS NOT NULL $categoryWhere",
            $categoryParams
        ) ?? 0
    ];
} catch (PDOException $e) {
    $stats = [
        'total_courses' => 0,
        'total_responses' => 0,
        'courses_this_month' => 0,
        'avg_score' => 0,
        'avg_pedagogical' => 0,
        'avg_didactic' => 0,
        'avg_infrastructure' => 0
    ];
}

// Recent responses
try {
    $recent_responses = $db->fetchAll("
        SELECT r.*, c.name as course_name, c.docente_name, c.category
        FROM responses r
        JOIN courses c ON r.course_id = c.id
        WHERE 1=1 $categoryWhere
        ORDER BY r.submitted_at DESC
        LIMIT 10
    ", $categoryParams);
} catch (PDOException $e) {
    $recent_responses = [];
}

// Top courses
try {
    $top_courses = $db->fetchAll("
        SELECT c.name, c.docente_name, c.category, ac.overall_score, ac.response_count
        FROM courses c
        JOIN analytics_cache ac ON c.id = ac.course_id
        WHERE c.is_active = 1 AND ac.overall_score IS NOT NULL $categoryWhere
        ORDER BY ac.overall_score DESC
        LIMIT 5
    ", $categoryParams);
} catch (PDOException $e) {
    $top_courses = [];
}

// Generate yearly diagram
$yearlyDiagram = generateYearlyDiagram($selectedCategory, 2024);

// Check for courses with low scores (only once per session per category)
$lowScoreKey = 'low_score_alert_' . $selectedCategory . '_2024';
if (!isset($_SESSION[$lowScoreKey])) {
    $lowScoreCheck = checkLowScoreCourses($selectedCategory, 2024);
    if ($lowScoreCheck['has_issues']) {
        setFlashMessage($lowScoreCheck['message'], 'warning', true);
        $_SESSION[$lowScoreKey] = true; // Don't show again this session
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indicadores de Gestão ESESP - <?= PESQUISA_SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1e3a5f;
            --secondary: #2c5f8d;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }
        
        body {
            background: #f3f4f6;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            min-height: 100vh;
            color: white;
            padding: 1.5rem 0;
            position: fixed;
            width: 250px;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 4px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar-brand {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            padding: 0 1.5rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
        }
        
        .sidebar-brand i {
            margin-right: 0.5rem;
        }
        
        .nav-section {
            margin-bottom: 1.5rem;
        }
        
        .nav-section-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            opacity: 0.7;
            padding: 0 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1.5rem;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white;
            border-left-color: white;
        }
        
        .sidebar .nav-link i {
            margin-right: 0.75rem;
            width: 20px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            color: var(--primary);
            font-weight: 600;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .stat-card .icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-card .icon.blue { background: #dbeafe; color: #2563eb; }
        .stat-card .icon.green { background: #d1fae5; color: #059669; }
        .stat-card .icon.yellow { background: #fef3c7; color: #d97706; }
        .stat-card .icon.purple { background: #ede9fe; color: #7c3aed; }
        .stat-card .icon.cyan { background: #cffafe; color: #0891b2; }
        .stat-card .icon.pink { background: #fce7f3; color: #db2777; }
        
        .stat-card .number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .stat-card .label {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            padding: 1rem 1.5rem;
        }
        
        .table th {
            font-weight: 600;
            color: #374151;
            border-bottom-width: 1px;
        }
        
        .badge-score {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .badge-score.high { background: #d1fae5; color: #059669; }
        .badge-score.medium { background: #fef3c7; color: #d97706; }
        .badge-score.low { background: #fee2e2; color: #dc2626; }
        
        .back-link {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            padding: 0 1.5rem;
            margin-bottom: 1rem;
        }
        
        .back-link:hover {
            color: white;
        }
        
        .user-info {
            padding: 1rem 1.5rem;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            margin: 1rem 1.5rem 0;
        }
        
        .user-info .name {
            font-weight: 500;
        }
        
        .user-info .role {
            font-size: 0.75rem;
            opacity: 0.7;
        }
        
        .category-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            background: #e5e7eb;
            color: #374151;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column">
        <a href="/credenciamento/" class="back-link">
            <i class="bi bi-arrow-left me-2"></i> Voltar ao Credenciamento
        </a>
        
        <div class="sidebar-brand">
            <i class="bi bi-clipboard-data"></i> Indicadores de Gestão ESESP
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Menu Principal</div>
            <nav class="nav flex-column">
                <a class="nav-link active" href="index.php">
                    <i class="bi bi-speedometer2"></i> Indicadores de Gestão ESESP
                </a>
                <a class="nav-link" href="courses.php">
                    <i class="bi bi-book"></i> Gerenciar Cursos
                </a>
                <a class="nav-link" href="create-course.php">
                    <i class="bi bi-plus-circle"></i> Criar Pesquisa de Satisfação
                </a>
                <a class="nav-link" href="analytics.php">
                    <i class="bi bi-graph-up"></i> Gráficos
                </a>
            </nav>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Categorias</div>
            <nav class="nav flex-column">
                <a class="nav-link <?= $selectedCategory === 'all' ? 'active' : '' ?>" href="?category=all">
                    <i class="bi bi-grid"></i> Todas
                </a>
                <a class="nav-link <?= $selectedCategory === 'Agenda Esesp' ? 'active' : '' ?>" href="?category=Agenda+Esesp">
                    <i class="bi bi-calendar-event"></i> Agenda Esesp
                </a>
                <a class="nav-link <?= $selectedCategory === 'Esesp na Estrada' ? 'active' : '' ?>" href="?category=Esesp+na+Estrada">
                    <i class="bi bi-truck"></i> Esesp na Estrada
                </a>
                <a class="nav-link <?= $selectedCategory === 'EAD' ? 'active' : '' ?>" href="?category=EAD">
                    <i class="bi bi-laptop"></i> Cursos EAD
                </a>
                <a class="nav-link <?= $selectedCategory === 'Pós-Graduação' ? 'active' : '' ?>" href="?category=Pós-Graduação">
                    <i class="bi bi-mortarboard"></i> Pós-Graduação
                </a>
                <a class="nav-link <?= $selectedCategory === 'Demanda Específica' ? 'active' : '' ?>" href="?category=Demanda+Específica">
                    <i class="bi bi-briefcase"></i> Demanda Específica
                </a>
            </nav>
        </div>
        
        <div class="user-info mt-auto">
            <div class="name"><?= sanitize($userName) ?></div>
            <div class="role"><?= $isAdmin ? 'Administrador' : 'Usuário' ?></div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1><i class="bi bi-speedometer2 me-2"></i>Indicadores de Gestão - <?= $categories[$selectedCategory] ?></h1>
            <p class="text-muted">Visão Geral dos Indicadores de Gestão</p>
        </div>
        
        <?= displayFlashMessage() ?>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <a href="courses.php?status=active&category=<?= urlencode($selectedCategory) ?>" class="stat-card">
                <div class="icon blue"><i class="bi bi-book"></i></div>
                <div class="number"><?= number_format($stats['total_courses']) ?></div>
                <div class="label">Cursos Ativos</div>
            </a>
            
            <a href="courses.php?category=<?= urlencode($selectedCategory) ?>" class="stat-card">
                <div class="icon green"><i class="bi bi-chat-square-text"></i></div>
                <div class="number"><?= number_format($stats['total_responses']) ?></div>
                <div class="label">Total de Respostas</div>
            </a>
            
            <a href="analytics.php?category=<?= urlencode($selectedCategory) ?>" class="stat-card">
                <div class="icon purple"><i class="bi bi-star"></i></div>
                <div class="number"><?= number_format($stats['avg_score'], 1) ?>%</div>
                <div class="label">Pontuação Média Geral</div>
            </a>
        </div>
        
        <!-- Category Scores (Weighted) -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon cyan"><i class="bi bi-book-half"></i></div>
                <div class="number"><?= number_format($stats['avg_pedagogical'], 1) ?>%</div>
                <div class="label">Média Pedagógico (35%)</div>
            </div>
            
            <div class="stat-card">
                <div class="icon blue"><i class="bi bi-person-video3"></i></div>
                <div class="number"><?= number_format($stats['avg_didactic'], 1) ?>%</div>
                <div class="label">Média Didático (40%)</div>
            </div>
            
            <div class="stat-card">
                <div class="icon pink"><i class="bi bi-building"></i></div>
                <div class="number"><?= number_format($stats['avg_infrastructure'], 1) ?>%</div>
                <div class="label">Média Infraestrutura (25%)</div>
            </div>
        </div>
        
        <div class="row">
            <!-- Top Cursos -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-trophy me-2"></i>Top Cursos por Avaliação
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_courses)): ?>
                            <p class="text-muted text-center py-4">Nenhum dado disponível ainda.</p>
                        <?php else: ?>
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Curso</th>
                                        <th>Categoria</th>
                                        <th>Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_courses as $course): ?>
                                    <tr>
                                        <td>
                                            <?= sanitize($course['name']) ?><br>
                                            <small class="text-muted"><?= sanitize($course['docente_name']) ?></small>
                                        </td>
                                        <td><span class="category-badge"><?= sanitize($course['category']) ?></span></td>
                                        <td>
                                            <?php 
                                            $score = $course['overall_score'];
                                            $class = $score >= 80 ? 'high' : ($score >= 60 ? 'medium' : 'low');
                                            ?>
                                            <span class="badge-score <?= $class ?>"><?= number_format($score, 1) ?>%</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Últimas Respostas -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-clock-history me-2"></i>Últimas Respostas
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_responses)): ?>
                            <p class="text-muted text-center py-4">Nenhuma resposta registrada ainda.</p>
                        <?php else: ?>
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Curso</th>
                                        <th>Categoria</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_responses as $response): ?>
                                    <tr>
                                        <td>
                                            <strong><?= sanitize($response['course_name']) ?></strong>
                                            <br><small class="text-muted"><?= sanitize($response['docente_name']) ?></small>
                                        </td>
                                        <td><span class="category-badge"><?= sanitize($response['category']) ?></span></td>
                                        <td>
                                            <small><?= date('d/m/Y H:i', strtotime($response['submitted_at'])) ?></small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Yearly Diagram -->
        <?php if ($yearlyDiagram && file_exists(__DIR__ . '/' . $yearlyDiagram)): ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-bar-chart-line me-2"></i>Evolução Anual - 2024</span>
                <span class="badge bg-primary"><?= $categories[$selectedCategory] ?></span>
            </div>
            <div class="card-body">
                <img id="yearly-diagram-img"
                     src="<?= htmlspecialchars($yearlyDiagram) ?>?v=<?= time() ?>" 
                     alt="Diagrama Anual" 
                     class="img-fluid rounded"
                     style="width: 100%; height: auto; cursor: pointer;"
                     title="Clique em um mês para ver detalhes">
                
                <div class="mt-3 d-flex gap-2 flex-wrap">
                    <a href="analytics.php?year=2024&category=<?= urlencode($selectedCategory) ?>" 
                       class="btn btn-primary">
                        <i class="bi bi-graph-up me-2"></i>Ver Análises Detalhadas
                    </a>
                    <a href="<?= htmlspecialchars($yearlyDiagram) ?>" 
                       download 
                       class="btn btn-outline-secondary">
                        <i class="bi bi-download me-2"></i>Download PNG
                    </a>
                    <button onclick="location.reload()" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise me-2"></i>Atualizar Diagrama
                    </button>
                </div>
                
                <div class="alert alert-info mt-3 mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    <small>
                        <strong>Dica:</strong> Clique na imagem para abrir em tamanho real. 
                        Use os botões das categorias na barra lateral para filtrar os dados.
                    </small>
                </div>
            </div>
        </div>
        <?php elseif($yearlyDiagram === false): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Não foi possível gerar o diagrama anual. Verifique se há dados para o ano de 2024.
        </div>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-lightning me-2"></i>Ações Rápidas
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <a href="create-course.php" class="btn btn-primary w-100">
                            <i class="bi bi-plus-lg me-2"></i>Novo Curso
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="analytics.php?category=<?= urlencode($selectedCategory) ?>" class="btn btn-outline-primary w-100">
                            <i class="bi bi-graph-up me-2"></i>Ver Análises
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Make yearly diagram clickable
    document.addEventListener('DOMContentLoaded', function() {
        const yearlyImage = document.getElementById('yearly-diagram-img');
        
        if (!yearlyImage) return;
        
        yearlyImage.style.cursor = 'pointer';
        
        yearlyImage.addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const width = rect.width;
            
            // The diagram has margins (approximately 8% on left and 2% on right)
            // Adjust for margins before calculating
            const leftMargin = width * 0.08;  // 8% margin on left
            const rightMargin = width * 0.02; // 2% margin on right
            const usableWidth = width - leftMargin - rightMargin;
            
            // Adjust click position to account for left margin
            const adjustedX = x - leftMargin;
            
            // If click is in the margin areas, ignore it
            if (adjustedX < 0 || adjustedX > usableWidth) {
                return;
            }
            
            // Divide usable width into 12 equal parts for months
            const monthWidth = usableWidth / 12;
            const clickedMonth = Math.floor(adjustedX / monthWidth) + 1;
            
            // Ensure month is valid (1-12)
            if (clickedMonth < 1 || clickedMonth > 12) {
                return;
            }
            
            // Get current category and year
            const urlParams = new URLSearchParams(window.location.search);
            const category = urlParams.get('category') || 'all';
            const year = '2024';
            
            // Redirect to monthly view
            window.location.href = `view-month.php?category=${encodeURIComponent(category)}&year=${year}&month=${clickedMonth}`;
        });
        
        // Add hover effect
        yearlyImage.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const width = rect.width;
            
            // Apply same margin adjustment for hover
            const leftMargin = width * 0.08;
            const rightMargin = width * 0.02;
            const usableWidth = width - leftMargin - rightMargin;
            const adjustedX = x - leftMargin;
            
            if (adjustedX < 0 || adjustedX > usableWidth) {
                this.title = 'Clique em um mês';
                return;
            }
            
            const monthWidth = usableWidth / 12;
            const month = Math.floor(adjustedX / monthWidth) + 1;
            
            // Validate month range
            if (month < 1 || month > 12) {
                this.title = 'Clique em um mês';
                return;
            }
            
            const monthNames = ['', 'JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 
                               'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'];
            
            this.title = `Clique para ver ${monthNames[month]}`;
        });
    });
    </script>
</body>
</html>