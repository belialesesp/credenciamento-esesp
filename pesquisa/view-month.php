<?php
/**
 * Monthly Diagram View
 * Shows detailed breakdown for a specific month
 */

require_once __DIR__ . '/includes/init.php';

$db = PesquisaDatabase::getInstance();
$userName = getCurrentUserName();

// Get parameters
$selectedCategory = $_GET['category'] ?? 'all';
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : 2024;
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');

// Month names
$monthNames = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

$categories = [
    'all' => 'Todas as Categorias',
    'Agenda Esesp' => 'Agenda Esesp',
    'Esesp na Estrada' => 'Esesp na Estrada',
    'EAD' => 'Cursos EAD',
    'Pós-Graduação' => 'Pós-Graduação',
    'Demanda Específica' => 'Demanda Específica'
];

// Generate monthly diagram
$monthlyDiagram = generateMonthlyDiagram($selectedCategory, $selectedYear, $selectedMonth);

// Get statistics for this month
$categoryWhere = '';
$categoryParams = [$selectedYear, $selectedMonth];
if ($selectedCategory !== 'all') {
    $categoryWhere = 'AND c.category = ?';
    $categoryParams[] = $selectedCategory;
}

$stats = [
    'total_courses' => $db->fetchColumn(
        "SELECT COUNT(*) FROM courses c WHERE year = ? AND month = ? $categoryWhere",
        $categoryParams
    ) ?? 0,
    
    'total_responses' => $db->fetchColumn(
        "SELECT COUNT(*) FROM responses r
         JOIN courses c ON r.course_id = c.id
         WHERE c.year = ? AND c.month = ? $categoryWhere",
        $categoryParams
    ) ?? 0,
    
    'avg_score' => $db->fetchColumn(
        "SELECT AVG(ac.overall_score)
         FROM analytics_cache ac
         JOIN courses c ON ac.course_id = c.id
         WHERE c.year = ? AND c.month = ? AND ac.overall_score IS NOT NULL $categoryWhere",
        $categoryParams
    ) ?? 0
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes de <?= $monthNames[$selectedMonth] ?>/<?= $selectedYear ?> - ESESP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1e3a5f;
            --secondary: #2c5f8d;
        }
        
        body { background: #f3f4f6; font-family: 'Segoe UI', sans-serif; }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .stat-card .number { font-size: 2.5rem; font-weight: 700; color: var(--primary); }
        .stat-card .label { color: #6b7280; font-size: 0.875rem; }
        
        .diagram-container {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .diagram-container img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .month-selector {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }
        
        .month-btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background: white;
            border: 2px solid #e5e7eb;
            color: #374151;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .month-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }
        
        .month-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        .category-pill {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            background: white;
            border: 2px solid #e5e7eb;
            color: #374151;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            margin: 0.25rem;
            transition: all 0.3s;
        }
        
        .category-pill:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .category-pill.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1><i class="bi bi-calendar-month me-2"></i><?= $monthNames[$selectedMonth] ?>/<?= $selectedYear ?></h1>
                    <p class="mb-0"><?= $categories[$selectedCategory] ?></p>
                </div>
                <div>
                    <a href="index.php?category=<?= urlencode($selectedCategory) ?>" class="btn btn-light">
                        <i class="bi bi-arrow-left me-2"></i>Voltar ao Dashboard
                    </a>
                </div>
            </div>
            
            <!-- Category Filter -->
            <div>
                <?php foreach ($categories as $key => $label): ?>
                    <a href="?category=<?= urlencode($key) ?>&year=<?= $selectedYear ?>&month=<?= $selectedMonth ?>" 
                       class="category-pill <?= $selectedCategory === $key ? 'active' : '' ?>">
                        <?= $label ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="number"><?= number_format($stats['total_courses']) ?></div>
                    <div class="label">Cursos no Mês</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="number"><?= number_format($stats['total_responses']) ?></div>
                    <div class="label">Total de Respostas</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="number"><?= number_format($stats['avg_score'], 1) ?>%</div>
                    <div class="label">Pontuação Média</div>
                </div>
            </div>
        </div>
        
        <!-- Month Selector -->
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="mb-3"><i class="bi bi-calendar3 me-2"></i>Selecione o Mês:</h6>
                <div class="month-selector">
                    <?php foreach ($monthNames as $m => $name): ?>
                        <a href="?category=<?= urlencode($selectedCategory) ?>&year=<?= $selectedYear ?>&month=<?= $m ?>" 
                           class="month-btn <?= $selectedMonth === $m ? 'active' : '' ?>">
                            <?= $name ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Monthly Diagram -->
        <div class="diagram-container">
            <h3 class="mb-3">
                <i class="bi bi-bar-chart-fill me-2"></i>
                <?php if ($selectedCategory === 'all'): ?>
                    Desempenho por Categoria
                <?php else: ?>
                    Cursos de <?= $categories[$selectedCategory] ?>
                <?php endif; ?>
            </h3>
            
            <?php if ($monthlyDiagram && file_exists(__DIR__ . '/' . $monthlyDiagram)): ?>
                <img src="<?= htmlspecialchars($monthlyDiagram) ?>?v=<?= time() ?>" 
                     alt="Diagrama Mensal"
                     onclick="window.open('<?= htmlspecialchars($monthlyDiagram) ?>', '_blank')">
                
                <div class="mt-3 d-flex gap-2 flex-wrap">
                    <a href="analytics.php?year=<?= $selectedYear ?>&month=<?= $selectedMonth ?>&category=<?= urlencode($selectedCategory) ?>" 
                       class="btn btn-primary">
                        <i class="bi bi-graph-up me-2"></i>Ver Análises Detalhadas
                    </a>
                    <a href="<?= htmlspecialchars($monthlyDiagram) ?>" 
                       download 
                       class="btn btn-outline-secondary">
                        <i class="bi bi-download me-2"></i>Download PNG
                    </a>
                    <button onclick="location.reload()" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise me-2"></i>Atualizar
                    </button>
                </div>
                
                <div class="alert alert-info mt-3 mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    <small>
                        <?php if ($selectedCategory === 'all'): ?>
                            <strong>Dica:</strong> Clique em uma categoria acima para ver todos os cursos daquela categoria neste mês.
                        <?php else: ?>
                            <strong>Dica:</strong> Cada barra representa um curso. Clique em "Todas as Categorias" para ver a comparação entre categorias.
                        <?php endif; ?>
                    </small>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Não há dados disponíveis para <?= $monthNames[$selectedMonth] ?>/<?= $selectedYear ?> 
                    <?php if ($selectedCategory !== 'all'): ?>
                        na categoria <?= $categories[$selectedCategory] ?>
                    <?php endif; ?>.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>