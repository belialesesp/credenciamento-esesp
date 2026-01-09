<?php
/**
 * public/index.php - Dashboard Principal
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$db = Database::getInstance();
$user = getCurrentUser();

// Estatísticas gerais
$stats = [
    'total_courses' => $db->count('courses', 'is_active = 1'),
    'total_responses' => $db->count('responses'),
    'courses_this_month' => $db->count('courses', 'is_active = 1 AND year = ? AND month = ?', [date('Y'), date('n')]),
    'avg_score' => $db->fetchColumn("SELECT AVG(overall_score) FROM analytics_cache") ?? 0
];

// Últimas respostas
$recent_responses = $db->fetchAll("
    SELECT r.*, c.name as course_name, c.docente_name
    FROM responses r
    JOIN courses c ON r.course_id = c.id
    ORDER BY r.submitted_at DESC
    LIMIT 10
");

// Cursos por categoria
$by_category = $db->fetchAll("
    SELECT cat.name, COUNT(c.id) as count
    FROM categories cat
    LEFT JOIN courses c ON cat.id = c.category_id AND c.is_active = 1
    GROUP BY cat.id, cat.name
");

// Top cursos
$top_courses = $db->fetchAll("
    SELECT c.name, c.docente_name, ac.overall_score, ac.response_count
    FROM courses c
    JOIN analytics_cache ac ON c.id = ac.course_id
    WHERE c.is_active = 1 AND ac.overall_score IS NOT NULL
    ORDER BY ac.overall_score DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1e3a5f;
            --secondary: #2c5f8d;
            --success: #10b981;
        }
        
        body {
            background: #f3f4f6;
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            min-height: 100vh;
            color: white;
            padding: 2rem 1rem;
            position: fixed;
            width: 250px;
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
            margin-left: 250px;
            padding: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .stat-card .icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .stat-card .label {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background: white;
            border-bottom: 2px solid #f3f4f6;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: #374151;
        }
        
        .badge-custom {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4>🎓 ESESP</h4>
        <div class="mb-3 text-white-50" style="font-size: 0.875rem;">
            Olá, <?= e($user['full_name']) ?>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link active" href="index.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a class="nav-link" href="courses.php">
                <i class="bi bi-book"></i> Cursos
            </a>
            <a class="nav-link" href="analytics.php">
                <i class="bi bi-graph-up"></i> Análises
            </a>
            <a class="nav-link" href="responses.php">
                <i class="bi bi-clipboard-data"></i> Respostas
            </a>
            <hr style="border-color: rgba(255,255,255,0.2);">
            <a class="nav-link" href="logout.php">
                <i class="bi bi-box-arrow-right"></i> Sair
            </a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>📊 Dashboard</h1>
            <a href="courses.php" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Novo Curso
            </a>
        </div>
        
        <?php displayFlashMessage(); ?>
        
        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon">📚</div>
                    <div class="number"><?= $stats['total_courses'] ?></div>
                    <div class="label">Cursos Ativos</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon">✍️</div>
                    <div class="number"><?= $stats['total_responses'] ?></div>
                    <div class="label">Total de Respostas</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon">📅</div>
                    <div class="number"><?= $stats['courses_this_month'] ?></div>
                    <div class="label">Cursos Este Mês</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon">⭐</div>
                    <div class="number"><?= formatPercentage($stats['avg_score'], 1) ?></div>
                    <div class="label">Pontuação Média</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Cursos por Categoria -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        📂 Cursos por Categoria
                    </div>
                    <div class="card-body">
                        <?php foreach ($by_category as $cat): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span><?= e($cat['name']) ?></span>
                            <span class="badge bg-primary"><?= $cat['count'] ?> cursos</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Top Cursos -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        🏆 Top 5 Cursos
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Curso</th>
                                        <th>Docente</th>
                                        <th>Nota</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_courses as $course): ?>
                                    <tr>
                                        <td><?= truncate(e($course['name']), 40) ?></td>
                                        <td><?= truncate(e($course['docente_name']), 25) ?></td>
                                        <td>
                                            <strong><?= formatPercentage($course['overall_score']) ?></strong>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Últimas Respostas -->
        <div class="card">
            <div class="card-header">
                📝 Últimas Respostas Recebidas
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Curso</th>
                                <th>Docente</th>
                                <th>Data</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_responses as $response): ?>
                            <tr>
                                <td><?= e($response['course_name']) ?></td>
                                <td><?= e($response['docente_name']) ?></td>
                                <td><?= formatDateTime($response['submitted_at']) ?></td>
                                <td><code><?= e($response['ip_address']) ?></code></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
