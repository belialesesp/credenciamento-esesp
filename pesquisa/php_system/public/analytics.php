<?php
/**
 * analytics.php - Dashboard de Análises com Filtros Dinâmicos
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/analytics.php';

// Verificar autenticação
requireAuth();

$db = Database::getInstance();
$analytics = new Analytics();

// Processar ações
$action = $_GET['action'] ?? null;

if ($action === 'generate_diagram') {
    $courseId = $_GET['course_id'] ?? null;
    if ($courseId) {
        $result = $analytics->generateDiagram($courseId);
        echo json_encode(['success' => $result]);
        exit;
    }
}

if ($action === 'get_filtered_data') {
    header('Content-Type: application/json');
    
    $filter = $_GET['filter'] ?? 'all';
    $category = $_GET['category'] ?? null;
    $month = $_GET['month'] ?? null;
    $year = $_GET['year'] ?? date('Y');
    $docente = $_GET['docente'] ?? null;
    
    $data = $analytics->getFilteredAnalytics($filter, [
        'category' => $category,
        'month' => $month,
        'year' => $year,
        'docente' => $docente
    ]);
    
    echo json_encode($data);
    exit;
}

// Buscar categorias
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY name");

// Buscar anos disponíveis
$years = $db->fetchAll("SELECT DISTINCT year FROM courses ORDER BY year DESC");

// Buscar docentes
$docentes = $db->fetchAll("SELECT DISTINCT docente_name FROM courses WHERE docente_name IS NOT NULL ORDER BY docente_name");

// Estatísticas gerais
$stats = [
    'total_courses' => $db->fetchOne("SELECT COUNT(*) as count FROM courses WHERE is_active = 1")['count'],
    'total_responses' => $db->fetchOne("SELECT COUNT(*) as count FROM responses")['count'],
    'avg_score' => $db->fetchOne("SELECT AVG(overall_score) as avg FROM analytics_cache")['avg'] ?? 0
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análises - ESESP</title>
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
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            min-height: 100vh;
            color: white;
            padding: 2rem 1rem;
        }
        
        .sidebar h4 {
            margin-bottom: 2rem;
            font-weight: 600;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .main-content {
            padding: 2rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .stats-card .icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .stats-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .stats-card .label {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 0.75rem 1.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .filter-tab:hover {
            border-color: var(--primary);
        }
        
        .filter-tab.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .filter-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .diagram-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .diagram-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .diagram-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .diagram-card img {
            width: 100%;
            height: auto;
        }
        
        .diagram-info {
            padding: 1rem;
        }
        
        .diagram-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .diagram-meta {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .classification-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .badge-excelencia {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-muito-bom {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-adequado {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-intervencao {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <h4>🎓 ESESP</h4>
                <nav class="nav flex-column">
                    <a class="nav-link" href="index.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="courses.php">
                        <i class="bi bi-book"></i> Cursos
                    </a>
                    <a class="nav-link active" href="analytics.php">
                        <i class="bi bi-graph-up"></i> Análises
                    </a>
                    <a class="nav-link" href="responses.php">
                        <i class="bi bi-clipboard-data"></i> Respostas
                    </a>
                    <a class="nav-link" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Sair
                    </a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <h1 class="mb-4">📊 Análises e Relatórios</h1>
                
                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="stats-card">
                            <div class="icon">📚</div>
                            <div class="number"><?= $stats['total_courses'] ?></div>
                            <div class="label">Cursos Ativos</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <div class="icon">✍️</div>
                            <div class="number"><?= $stats['total_responses'] ?></div>
                            <div class="label">Total de Respostas</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <div class="icon">⭐</div>
                            <div class="number"><?= number_format($stats['avg_score'], 1) ?>%</div>
                            <div class="label">Pontuação Média</div>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <h5 class="mb-3">🔍 Filtros</h5>
                    
                    <!-- Filter Tabs -->
                    <div class="filter-tabs">
                        <button class="filter-tab active" data-filter="all" onclick="setFilter('all')">
                            Todos os Cursos
                        </button>
                        <button class="filter-tab" data-filter="monthly" onclick="setFilter('monthly')">
                            📅 Por Mês
                        </button>
                        <button class="filter-tab" data-filter="category" onclick="setFilter('category')">
                            📂 Por Categoria
                        </button>
                        <button class="filter-tab" data-filter="docente" onclick="setFilter('docente')">
                            👨‍🏫 Por Docente
                        </button>
                        <button class="filter-tab" data-filter="annual" onclick="setFilter('annual')">
                            📆 Visão Anual
                        </button>
                    </div>
                    
                    <!-- Filter Options -->
                    <div class="filter-options" id="filterOptions">
                        <!-- Categoria -->
                        <div class="filter-group" data-filter-type="category" style="display: none;">
                            <label class="form-label">Categoria</label>
                            <select class="form-select" id="filterCategory" onchange="applyFilters()">
                                <option value="">Todas</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Ano -->
                        <div class="filter-group" data-filter-type="year">
                            <label class="form-label">Ano</label>
                            <select class="form-select" id="filterYear" onchange="applyFilters()">
                                <?php foreach ($years as $y): ?>
                                <option value="<?= $y['year'] ?>" <?= $y['year'] == date('Y') ? 'selected' : '' ?>>
                                    <?= $y['year'] ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Mês -->
                        <div class="filter-group" data-filter-type="month" style="display: none;">
                            <label class="form-label">Mês</label>
                            <select class="form-select" id="filterMonth" onchange="applyFilters()">
                                <option value="">Todos</option>
                                <?php 
                                $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                                          'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                                foreach ($months as $i => $m): 
                                ?>
                                <option value="<?= $i + 1 ?>"><?= $m ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Docente -->
                        <div class="filter-group" data-filter-type="docente" style="display: none;">
                            <label class="form-label">Docente</label>
                            <select class="form-select" id="filterDocente" onchange="applyFilters()">
                                <option value="">Todos</option>
                                <?php foreach ($docentes as $doc): ?>
                                <option value="<?= htmlspecialchars($doc['docente_name']) ?>">
                                    <?= htmlspecialchars($doc['docente_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button class="btn btn-primary" onclick="applyFilters()">
                            <i class="bi bi-funnel"></i> Aplicar Filtros
                        </button>
                        <button class="btn btn-outline-secondary" onclick="resetFilters()">
                            <i class="bi bi-arrow-clockwise"></i> Limpar
                        </button>
                    </div>
                </div>
                
                <!-- Loading -->
                <div class="loading-spinner" id="loadingSpinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-2">Carregando dados...</p>
                </div>
                
                <!-- Diagrams Grid -->
                <div class="diagram-grid" id="diagramsGrid">
                    <!-- Será preenchido dinamicamente via JavaScript -->
                </div>
                
                <!-- Empty State -->
                <div class="empty-state" id="emptyState" style="display: none;">
                    <i class="bi bi-inbox"></i>
                    <h4>Nenhum resultado encontrado</h4>
                    <p>Tente ajustar os filtros ou adicionar mais cursos</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentFilter = 'all';
        
        function setFilter(filter) {
            currentFilter = filter;
            
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`[data-filter="${filter}"]`).classList.add('active');
            
            // Show/hide relevant filter options
            document.querySelectorAll('.filter-group').forEach(group => {
                group.style.display = 'none';
            });
            
            // Always show year
            document.querySelectorAll('[data-filter-type="year"]').forEach(el => {
                el.style.display = 'block';
            });
            
            // Show specific filters based on tab
            if (filter === 'monthly') {
                document.querySelectorAll('[data-filter-type="month"]').forEach(el => {
                    el.style.display = 'block';
                });
            } else if (filter === 'category') {
                document.querySelectorAll('[data-filter-type="category"]').forEach(el => {
                    el.style.display = 'block';
                });
            } else if (filter === 'docente') {
                document.querySelectorAll('[data-filter-type="docente"]').forEach(el => {
                    el.style.display = 'block';
                });
            }
            
            applyFilters();
        }
        
        async function applyFilters() {
            const year = document.getElementById('filterYear').value;
            const month = document.getElementById('filterMonth').value;
            const category = document.getElementById('filterCategory').value;
            const docente = document.getElementById('filterDocente').value;
            
            // Show loading
            document.getElementById('loadingSpinner').style.display = 'block';
            document.getElementById('diagramsGrid').style.display = 'none';
            document.getElementById('emptyState').style.display = 'none';
            
            try {
                const params = new URLSearchParams({
                    action: 'get_filtered_data',
                    filter: currentFilter,
                    year: year,
                    month: month,
                    category: category,
                    docente: docente
                });
                
                const response = await fetch(`analytics.php?${params}`);
                const data = await response.json();
                
                displayResults(data);
            } catch (error) {
                console.error('Error loading data:', error);
                alert('Erro ao carregar dados');
            } finally {
                document.getElementById('loadingSpinner').style.display = 'none';
            }
        }
        
        function displayResults(data) {
            const grid = document.getElementById('diagramsGrid');
            const empty = document.getElementById('emptyState');
            
            if (!data || data.length === 0) {
                grid.style.display = 'none';
                empty.style.display = 'block';
                return;
            }
            
            grid.style.display = 'grid';
            empty.style.display = 'none';
            
            grid.innerHTML = data.map(course => `
                <div class="diagram-card">
                    ${course.diagram_path ? `
                        <img src="${course.diagram_path}" alt="${course.name}">
                    ` : `
                        <div style="padding: 2rem; text-align: center; background: #f3f4f6;">
                            <i class="bi bi-image" style="font-size: 3rem; color: #9ca3af;"></i>
                            <p style="margin-top: 1rem; color: #6b7280;">Diagrama não gerado</p>
                        </div>
                    `}
                    <div class="diagram-info">
                        <div class="diagram-title">${course.name}</div>
                        <div class="diagram-meta">
                            👨‍🏫 ${course.docente_name || 'Não informado'}<br>
                            📂 ${course.category_name}<br>
                            📅 ${getMonthName(course.month)}/${course.year}<br>
                            ✍️ ${course.response_count} respostas
                        </div>
                        ${course.overall_score ? `
                            <div style="margin-top: 0.5rem;">
                                <span class="classification-badge ${getClassificationClass(course.classification)}">
                                    ${course.classification}
                                </span>
                                <div style="margin-top: 0.5rem; font-weight: 600; color: var(--primary);">
                                    ${parseFloat(course.overall_score).toFixed(1)}%
                                </div>
                            </div>
                        ` : ''}
                        <div class="action-buttons">
                            ${!course.diagram_path ? `
                                <button class="btn btn-sm btn-primary" onclick="generateDiagram(${course.id})">
                                    <i class="bi bi-arrow-repeat"></i> Gerar Diagrama
                                </button>
                            ` : `
                                <a href="${course.diagram_path}" class="btn btn-sm btn-outline-primary" download>
                                    <i class="bi bi-download"></i> Baixar
                                </a>
                            `}
                            <a href="responses.php?course_id=${course.id}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye"></i> Ver Respostas
                            </a>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        async function generateDiagram(courseId) {
            if (!confirm('Gerar diagrama para este curso?')) return;
            
            try {
                const response = await fetch(`analytics.php?action=generate_diagram&course_id=${courseId}`);
                const result = await response.json();
                
                if (result.success) {
                    alert('Diagrama gerado com sucesso!');
                    applyFilters(); // Reload data
                } else {
                    alert('Erro ao gerar diagrama');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Erro ao gerar diagrama');
            }
        }
        
        function resetFilters() {
            document.getElementById('filterYear').value = new Date().getFullYear();
            document.getElementById('filterMonth').value = '';
            document.getElementById('filterCategory').value = '';
            document.getElementById('filterDocente').value = '';
            setFilter('all');
        }
        
        function getMonthName(month) {
            const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
            return months[month - 1] || month;
        }
        
        function getClassificationClass(classification) {
            if (classification.includes('Excelência')) return 'badge-excelencia';
            if (classification.includes('Muito bom')) return 'badge-muito-bom';
            if (classification.includes('Adequado')) return 'badge-adequado';
            return 'badge-intervencao';
        }
        
        // Load initial data
        document.addEventListener('DOMContentLoaded', () => {
            applyFilters();
        });
    </script>
</body>
</html>
