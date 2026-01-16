<?php
/**
 * Course Details Page
 * Shows detailed analytics for a specific course including:
 * - Overall statistics
 * - Category breakdowns
 * - Individual responses
 * - QR Code download
 * - Diagram generation/view
 */

require_once __DIR__ . '/includes/init.php';

$db = PesquisaDatabase::getInstance();
$userName = getCurrentUserName();
$isAdmin = isPesquisaAdmin();

// Get course ID
$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$courseId) {
    setFlashMessage("ID do curso não fornecido.", 'error');
    header("Location: index.php");
    exit;
}

// Get course details
$course = $db->fetchOne("
    SELECT c.*, ac.overall_score, ac.pedagogical_score, ac.didactic_score,
           ac.infrastructure_score, ac.response_count, ac.last_updated
    FROM courses c
    LEFT JOIN analytics_cache ac ON c.id = ac.course_id
    WHERE c.id = ?
", [$courseId]);

if (!$course) {
    setFlashMessage("Curso não encontrado.", 'error');
    header("Location: index.php");
    exit;
}

// Auto-generate diagram if needed
$diagramPath = null;
if ($course['response_count'] > 0) {
    // diagram_path already comes from the courses table in $course
    $cachedDiagram = $course['diagram_path'] ?? null;
    
    $shouldRegenerate = false;
    
    if ($cachedDiagram && file_exists(__DIR__ . '/' . $cachedDiagram)) {
        // Check if diagram is older than 1 hour
        $diagramAge = time() - filemtime(__DIR__ . '/' . $cachedDiagram);
        if ($diagramAge > 3600) { // 1 hour cache
            $shouldRegenerate = true;
        } else {
            $diagramPath = $cachedDiagram;
        }
    } else {
        $shouldRegenerate = true;
    }
    
    // Generate diagram if needed
    if ($shouldRegenerate) {
        $diagramPath = generateDiagram($courseId);
    }
}

// Get all responses for this course
$responses = $db->fetchAll("
    SELECT r.*, 
           DATE_FORMAT(r.submitted_at, '%d/%m/%Y %H:%i') as formatted_date
    FROM responses r
    WHERE r.course_id = ?
    ORDER BY r.submitted_at DESC
", [$courseId]);

// Get question-by-question analysis
$questionAnalysis = $db->fetchAll("
    SELECT 
        sq.id,
        sq.question_text,
        qc.name as category_name,
        AVG(ra.answer_score) as avg_score,
        COUNT(ra.id) as response_count,
        SUM(CASE WHEN ra.answer_score = 5 THEN 1 ELSE 0 END) as count_5,
        SUM(CASE WHEN ra.answer_score = 4 THEN 1 ELSE 0 END) as count_4,
        SUM(CASE WHEN ra.answer_score = 3 THEN 1 ELSE 0 END) as count_3,
        SUM(CASE WHEN ra.answer_score = 2 THEN 1 ELSE 0 END) as count_2,
        SUM(CASE WHEN ra.answer_score = 1 THEN 1 ELSE 0 END) as count_1
    FROM survey_questions sq
    JOIN question_categories qc ON sq.category_id = qc.id
    LEFT JOIN response_answers ra ON sq.id = ra.question_id
    LEFT JOIN responses r ON ra.response_id = r.id
    WHERE sq.is_active = TRUE AND (r.course_id = ? OR r.course_id IS NULL)
    GROUP BY sq.id, sq.question_text, qc.name
    ORDER BY sq.display_order
", [$courseId]);

// Handle manual recalculation
if (isset($_POST['recalculate'])) {
    if (recalculateAnalytics($courseId)) {
        setFlashMessage("Analytics recalculados com sucesso!", 'success');
    } else {
        setFlashMessage("Erro ao recalcular analytics.", 'error');
    }
    header("Location: course-details.php?id=$courseId");
    exit;
}

// Handle course deactivation
if (isset($_POST['toggle_active'])) {
    $newStatus = $course['is_active'] ? 0 : 1;
    $db->update('courses', ['is_active' => $newStatus], 'id = ?', [$courseId]);
    $message = $newStatus ? "Curso ativado" : "Curso desativado";
    setFlashMessage($message, 'success');
    header("Location: course-details.php?id=$courseId");
    exit;
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
    <title>Detalhes do Curso - <?= sanitize($course['name']) ?></title>
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
        
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .info-card h3 {
            color: var(--primary);
            font-size: 1.25rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-box {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }
        
        .stat-box .label {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }
        
        .stat-box .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .qr-container {
            text-align: center;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .qr-container img {
            max-width: 300px;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        
        .survey-link {
            background: #f0f9ff;
            padding: 1rem;
            border-radius: 8px;
            font-family: monospace;
            word-break: break-all;
            margin: 1rem 0;
        }
        
        .question-analysis {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .distribution-bar {
            display: flex;
            height: 30px;
            border-radius: 4px;
            overflow: hidden;
            margin: 0.5rem 0;
        }
        
        .distribution-bar > div {
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .score-5 { background: #10b981; }
        .score-4 { background: #3b82f6; }
        .score-3 { background: #f59e0b; }
        .score-2 { background: #ef4444; }
        .score-1 { background: #991b1b; }
        
        .response-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
        }
        
        .btn-action {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .diagram-container {
            padding: 1rem;
        }
        
        .diagram-clickable:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="bi bi-book me-2"></i><?= sanitize($course['name']) ?></h1>
                    <p class="mb-0">
                        <i class="bi bi-person me-2"></i><?= sanitize($course['docente_name']) ?>
                        <span class="ms-3"><i class="bi bi-tag me-2"></i><?= sanitize($course['category']) ?></span>
                        <span class="ms-3"><i class="bi bi-calendar me-2"></i><?= $months[$course['month']] ?>/<?= $course['year'] ?></span>
                    </p>
                </div>
                <a href="analytics.php" class="btn btn-light">
                    <i class="bi bi-arrow-left me-2"></i>Voltar
                </a>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container mb-5">
        <?= displayFlashMessage() ?>
        
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                
                <!-- Overall Statistics -->
                <div class="info-card">
                    <h3><i class="bi bi-graph-up me-2"></i>Estatísticas Gerais</h3>
                    
                    <?php if ($course['overall_score']): ?>
                        <div class="stat-grid">
                            <div class="stat-box">
                                <div class="label">Pontuação Geral</div>
                                <div class="value"><?= number_format($course['overall_score'], 1) ?>%</div>
                                <small class="text-muted"><?= getClassification($course['overall_score']) ?></small>
                            </div>
                            
                            <div class="stat-box">
                                <div class="label">Pedagógico (40%)</div>
                                <div class="value"><?= number_format($course['pedagogical_score'], 1) ?>%</div>
                            </div>
                            
                            <div class="stat-box">
                                <div class="label">Didático (35%)</div>
                                <div class="value"><?= number_format($course['didactic_score'], 1) ?>%</div>
                            </div>
                            
                            <div class="stat-box">
                                <div class="label">Infraestrutura (25%)</div>
                                <div class="value"><?= number_format($course['infrastructure_score'], 1) ?>%</div>
                            </div>
                            
                            <div class="stat-box">
                                <div class="label">Total de Respostas</div>
                                <div class="value"><?= number_format($course['response_count']) ?></div>
                            </div>
                            
                            <div class="stat-box">
                                <div class="label">Última Atualização</div>
                                <div class="value" style="font-size: 1rem;">
                                    <?= date('d/m/Y H:i', strtotime($course['last_updated'])) ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Chart -->
                        <canvas id="categoryChart" height="100"></canvas>
                        
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Ainda não há respostas para este curso. Compartilhe o QR Code ou link com os alunos.
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Diagram Section -->
                <?php if ($course['response_count'] > 0): ?>
                <div class="info-card">
                    <h3><i class="bi bi-diagram-3 me-2"></i>Diagrama de Desempenho</h3>
                    
                    <?php if ($diagramPath && file_exists(__DIR__ . '/' . $diagramPath)): ?>
                        <div class="diagram-container text-center">
                            <img src="<?= htmlspecialchars($diagramPath) ?>?v=<?= time() ?>" 
                                 alt="Diagrama de Desempenho"
                                 class="img-fluid diagram-clickable"
                                 onclick="window.open('<?= htmlspecialchars($diagramPath) ?>', '_blank')"
                                 title="Clique para abrir em tamanho completo"
                                 style="max-width: 100%; height: auto; border-radius: 8px; cursor: pointer; transition: transform 0.3s;">
                            
                            <div class="mt-3 d-flex gap-2 justify-content-center flex-wrap">
                                <a href="<?= htmlspecialchars($diagramPath) ?>" 
                                   download 
                                   class="btn btn-success">
                                    <i class="bi bi-download me-1"></i>Download PNG
                                </a>
                                <button onclick="location.reload()" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Atualizar Diagrama
                                </button>
                            </div>
                            
                            <div class="alert alert-info mt-3 mb-0 text-start">
                                <small>
                                    <i class="bi bi-lightbulb me-1"></i>
                                    <strong>Dica:</strong> Clique na imagem para abrir em tamanho completo em uma nova aba.
                                    O diagrama é atualizado automaticamente a cada hora.
                                </small>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Diagrama não disponível.</strong>
                            Erro ao gerar o diagrama. Tente recarregar a página.
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Question Analysis -->
                <?php if ($course['response_count'] > 0): ?>
                    <div class="info-card">
                        <h3><i class="bi bi-question-circle me-2"></i>Análise por Pergunta</h3>
                        
                        <?php 
                        $currentCategory = '';
                        foreach ($questionAnalysis as $i => $qa): 
                            if ($qa['response_count'] == 0) continue;
                            
                            if ($currentCategory !== $qa['category_name']):
                                if ($currentCategory !== '') echo '</div>';
                                $currentCategory = $qa['category_name'];
                                echo '<h5 class="mt-4 mb-3 text-primary">' . sanitize($currentCategory) . '</h5>';
                                echo '<div>';
                            endif;
                            
                            $avgScore = $qa['avg_score'];
                            $total = $qa['response_count'];
                        ?>
                            <div class="question-analysis">
                                <strong>Q<?= $i + 1 ?>:</strong> <?= sanitize($qa['question_text']) ?>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span><strong>Média:</strong> <?= number_format($avgScore, 2) ?>/5.00 (<?= number_format(($avgScore/5)*100, 1) ?>%)</span>
                                    <span class="text-muted"><?= $total ?> respostas</span>
                                </div>
                                
                                <!-- Distribution Bar -->
                                <div class="distribution-bar">
                                    <?php if ($qa['count_5'] > 0): ?>
                                        <div class="score-5" style="width: <?= ($qa['count_5']/$total)*100 ?>%">
                                            <?= $qa['count_5'] ?> (5⭐)
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($qa['count_4'] > 0): ?>
                                        <div class="score-4" style="width: <?= ($qa['count_4']/$total)*100 ?>%">
                                            <?= $qa['count_4'] ?> (4⭐)
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($qa['count_3'] > 0): ?>
                                        <div class="score-3" style="width: <?= ($qa['count_3']/$total)*100 ?>%">
                                            <?= $qa['count_3'] ?> (3⭐)
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($qa['count_2'] > 0): ?>
                                        <div class="score-2" style="width: <?= ($qa['count_2']/$total)*100 ?>%">
                                            <?= $qa['count_2'] ?> (2⭐)
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($qa['count_1'] > 0): ?>
                                        <div class="score-1" style="width: <?= ($qa['count_1']/$total)*100 ?>%">
                                            <?= $qa['count_1'] ?> (1⭐)
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Recent Responses -->
                    <div class="info-card">
                        <h3><i class="bi bi-chat-square-text me-2"></i>Respostas Recentes</h3>
                        
                        <?php foreach (array_slice($responses, 0, 10) as $response): ?>
                            <div class="response-card">
                                <div class="d-flex justify-content-between">
                                    <span>
                                        <i class="bi bi-calendar3 me-2"></i><?= $response['formatted_date'] ?>
                                    </span>
                                    <span class="badge bg-primary">
                                        <?= number_format($response['overall_score'], 1) ?>%
                                    </span>
                                </div>
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>Tempo de conclusão: <?= $response['completion_time'] ?>s
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
            </div>
            
            <!-- Right Column -->
            <div class="col-lg-4">
                
                <!-- QR Code & Link -->
                <div class="info-card">
                    <h3><i class="bi bi-qr-code me-2"></i>QR Code & Link</h3>
                    
                    <div class="qr-container">
                        <?php if ($course['qr_code_path'] && file_exists(__DIR__ . '/' . $course['qr_code_path'])): ?>
                            <img src="<?= sanitize($course['qr_code_path']) ?>" alt="QR Code" class="img-fluid">
                            <div class="mt-3">
                                <a href="<?= sanitize($course['qr_code_path']) ?>" download class="btn btn-sm btn-primary">
                                    <i class="bi bi-download me-1"></i>Baixar QR Code
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">QR Code não encontrado</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="survey-link">
                        <small class="text-muted d-block mb-1">Link da Pesquisa:</small>
                        <?= sanitize($course['survey_url'] ?? PESQUISA_BASE_URL . '/survey.php?t=' . $course['token']) ?>
                    </div>
                    
                    <button class="btn btn-sm btn-outline-secondary w-100" onclick="copyLink()">
                        <i class="bi bi-clipboard me-1"></i>Copiar Link
                    </button>
                </div>
                
                <!-- Actions -->
                <div class="info-card">
                    <h3><i class="bi bi-tools me-2"></i>Ações</h3>
                    
                    <form method="POST" class="d-grid gap-2">
                        <button type="submit" name="recalculate" class="btn btn-primary btn-action">
                            <i class="bi bi-arrow-repeat me-1"></i>Recalcular Analytics
                        </button>
                        
                        <a href="analytics.php?course_id=<?= $courseId ?>" class="btn btn-info btn-action">
                            <i class="bi bi-graph-up me-1"></i>Ver no Dashboard
                        </a>
                        
                        <button type="submit" name="toggle_active" 
                                class="btn btn-<?= $course['is_active'] ? 'warning' : 'success' ?> btn-action">
                            <i class="bi bi-<?= $course['is_active'] ? 'pause' : 'play' ?>-circle me-1"></i>
                            <?= $course['is_active'] ? 'Desativar' : 'Ativar' ?> Curso
                        </button>
                    </form>
                    
                    <?php if ($course['response_count'] > 0): ?>
                        <hr>
                        <a href="export-responses.php?course_id=<?= $courseId ?>" class="btn btn-success btn-action w-100">
                            <i class="bi bi-file-earmark-excel me-1"></i>Exportar para Excel
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Course Info -->
                <div class="info-card">
                    <h3><i class="bi bi-info-circle me-2"></i>Informações</h3>
                    
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Token:</strong></td>
                            <td><code><?= sanitize($course['token']) ?></code></td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>
                                <span class="badge bg-<?= $course['is_active'] ? 'success' : 'secondary' ?>">
                                    <?= $course['is_active'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Limite:</strong></td>
                            <td><?= $course['max_responses'] ? number_format($course['max_responses']) : 'Ilimitado' ?></td>
                        </tr>
                        <tr>
                            <td><strong>Criado em:</strong></td>
                            <td><?= date('d/m/Y H:i', strtotime($course['created_at'])) ?></td>
                        </tr>
                        <?php if ($course['description']): ?>
                        <tr>
                            <td colspan="2">
                                <strong>Descrição:</strong><br>
                                <?= nl2br(sanitize($course['description'])) ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
                
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Copy link function
        function copyLink() {
            const link = "<?= $course['survey_url'] ?? PESQUISA_BASE_URL . '/survey.php?t=' . $course['token'] ?>";
            navigator.clipboard.writeText(link).then(() => {
                alert('Link copiado para a área de transferência!');
            });
        }
        
        <?php if ($course['overall_score']): ?>
        // Category Chart
        const ctx = document.getElementById('categoryChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Pedagógico (40%)', 'Didático (35%)', 'Infraestrutura (25%)'],
                datasets: [{
                    label: 'Pontuação (%)',
                    data: [
                        <?= $course['pedagogical_score'] ?>,
                        <?= $course['didactic_score'] ?>,
                        <?= $course['infrastructure_score'] ?>
                    ],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.7)',
                        'rgba(139, 92, 246, 0.7)',
                        'rgba(16, 185, 129, 0.7)'
                    ],
                    borderColor: [
                        'rgb(59, 130, 246)',
                        'rgb(139, 92, 246)',
                        'rgb(16, 185, 129)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Desempenho por Categoria'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>