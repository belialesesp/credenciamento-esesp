<?php
/**
 * public/courses.php - Gerenciamento de Cursos com QR Code
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/analytics.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$db = Database::getInstance();
$analytics = new Analytics();

// Ações AJAX
if (isAjaxRequest()) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? $_GET['action'] ?? null;
    
    if ($action === 'delete' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $deleted = $db->delete('courses', 'id = ?', [$id]);
        echo json_encode(['success' => $deleted > 0]);
        exit;
    }
    
    if ($action === 'toggle_active' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $course = $db->fetchOne('SELECT is_active FROM courses WHERE id = ?', [$id]);
        $newStatus = $course['is_active'] ? 0 : 1;
        $updated = $db->update('courses', ['is_active' => $newStatus], 'id = ?', [$id]);
        echo json_encode(['success' => $updated > 0, 'new_status' => $newStatus]);
        exit;
    }
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isAjaxRequest()) {
    $id = intval($_POST['id'] ?? 0);
    $name = sanitizeInput($_POST['name'] ?? '');
    $docente = sanitizeInput($_POST['docente_name'] ?? '');
    $category = intval($_POST['category_id'] ?? 0);
    $month = intval($_POST['month'] ?? 0);
    $year = intval($_POST['year'] ?? date('Y'));
    
    // Validações
    $errors = [];
    if (empty($name)) $errors[] = 'Nome do curso é obrigatório';
    if (empty($category)) $errors[] = 'Categoria é obrigatória';
    if ($month < 1 || $month > 12) $errors[] = 'Mês inválido';
    
    if (empty($errors)) {
        // Gerar token
        $token = generateSurveyToken($name, $month, $year);
        
        // Verificar se token já existe
        $existing = $db->fetchOne('SELECT id FROM courses WHERE survey_token = ? AND id != ?', [$token, $id]);
        if ($existing) {
            $token .= '-' . time(); // Add timestamp to make unique
        }
        
        $data = [
            'name' => $name,
            'docente_name' => $docente,
            'category_id' => $category,
            'month' => $month,
            'year' => $year,
            'survey_token' => $token
        ];
        
        if ($id > 0) {
            // Atualizar
            $db->update('courses', $data, 'id = ?', [$id]);
            redirectWithMessage('courses.php', 'Curso atualizado com sucesso!');
        } else {
            // Inserir
            $db->insert('courses', $data);
            redirectWithMessage('courses.php', 'Curso criado com sucesso!');
        }
    } else {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
    }
}

// Obter curso para edição
$editCourse = null;
if (isset($_GET['edit'])) {
    $editCourse = $db->fetchOne('SELECT * FROM courses WHERE id = ?', [intval($_GET['edit'])]);
}

// Buscar categorias
$categories = $db->fetchAll('SELECT * FROM categories ORDER BY name');

// Buscar cursos
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';

$sql = "SELECT c.*, cat.name as category_name, 
        (SELECT COUNT(*) FROM responses WHERE course_id = c.id) as response_count
        FROM courses c
        LEFT JOIN categories cat ON c.category_id = cat.id
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (c.name LIKE ? OR c.docente_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($categoryFilter) {
    $sql .= " AND c.category_id = ?";
    $params[] = $categoryFilter;
}

$sql .= " ORDER BY c.year DESC, c.month DESC, c.name ASC";
$courses = $db->fetchAll($sql, $params);

$formErrors = $_SESSION['form_errors'] ?? [];
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cursos - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1e3a5f;
            --secondary: #2c5f8d;
        }
        
        body { background: #f3f4f6; }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            min-height: 100vh;
            color: white;
            padding: 2rem 1rem;
            position: fixed;
            width: 250px;
        }
        
        .sidebar h4 { margin-bottom: 2rem; font-weight: 600; }
        
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
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .qr-modal .qr-container {
            text-align: center;
            padding: 2rem;
        }
        
        .qr-modal img {
            max-width: 300px;
            border: 4px solid #f3f4f6;
            border-radius: 12px;
        }
        
        .copy-link {
            background: #f3f4f6;
            padding: 0.75rem;
            border-radius: 8px;
            font-family: monospace;
            word-break: break-all;
        }
        
        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4>🎓 ESESP</h4>
        <nav class="nav flex-column">
            <a class="nav-link" href="index.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a class="nav-link active" href="courses.php">
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
            <h1>📚 Gerenciar Cursos</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#courseModal" onclick="clearForm()">
                <i class="bi bi-plus-lg"></i> Novo Curso
            </button>
        </div>
        
        <?php displayFlashMessage(); ?>
        
        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Buscar por nome ou docente..."
                               value="<?= e($search) ?>">
                    </div>
                    <div class="col-md-4">
                        <select name="category" class="form-select">
                            <option value="">Todas as categorias</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Lista de Cursos -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Curso</th>
                                <th>Docente</th>
                                <th>Categoria</th>
                                <th>Período</th>
                                <th>Respostas</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                            <tr>
                                <td>
                                    <strong><?= e($course['name']) ?></strong>
                                </td>
                                <td><?= e($course['docente_name']) ?></td>
                                <td><?= e($course['category_name']) ?></td>
                                <td><?= getMonthName($course['month']) ?>/<?= $course['year'] ?></td>
                                <td>
                                    <span class="badge bg-info"><?= $course['response_count'] ?></span>
                                </td>
                                <td>
                                    <?php if ($course['is_active']): ?>
                                    <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary btn-icon" 
                                            onclick="showQRCode(<?= $course['id'] ?>, '<?= e($course['name']) ?>', '<?= e($course['survey_token']) ?>')"
                                            title="Ver QR Code">
                                        <i class="bi bi-qr-code"></i>
                                    </button>
                                    <a href="?edit=<?= $course['id'] ?>" 
                                       class="btn btn-sm btn-warning btn-icon"
                                       title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger btn-icon" 
                                            onclick="deleteCourse(<?= $course['id'] ?>)"
                                            title="Excluir">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Formulário de Curso -->
    <div class="modal fade" id="courseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><?= $editCourse ? 'Editar Curso' : 'Novo Curso' ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <?php if ($formErrors): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($formErrors as $error): ?>
                            <div><?= e($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <input type="hidden" name="id" value="<?= $editCourse['id'] ?? '' ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Nome do Curso *</label>
                            <input type="text" 
                                   name="name" 
                                   class="form-control" 
                                   value="<?= e($editCourse['name'] ?? $formData['name'] ?? '') ?>"
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Docente</label>
                            <input type="text" 
                                   name="docente_name" 
                                   class="form-control"
                                   value="<?= e($editCourse['docente_name'] ?? $formData['docente_name'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Categoria *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" 
                                        <?= ($editCourse['category_id'] ?? $formData['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                    <?= e($cat['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mês *</label>
                                <select name="month" class="form-select" required>
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" 
                                            <?= ($editCourse['month'] ?? $formData['month'] ?? date('n')) == $m ? 'selected' : '' ?>>
                                        <?= getMonthName($m) ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ano *</label>
                                <input type="number" 
                                       name="year" 
                                       class="form-control"
                                       value="<?= e($editCourse['year'] ?? $formData['year'] ?? date('Y')) ?>"
                                       min="2020" 
                                       max="2030"
                                       required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: QR Code -->
    <div class="modal fade" id="qrModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">QR Code da Pesquisa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body qr-modal">
                    <div class="qr-container">
                        <h6 id="qrCourseName" class="mb-3"></h6>
                        <img id="qrImage" src="" alt="QR Code">
                        <div class="mt-3">
                            <label class="form-label">Link da Pesquisa:</label>
                            <div class="copy-link" id="surveyLink"></div>
                            <button class="btn btn-sm btn-primary mt-2" onclick="copyLink()">
                                <i class="bi bi-clipboard"></i> Copiar Link
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if ($editCourse || $formErrors): ?>
        // Abrir modal se estiver editando ou houver erros
        const modal = new bootstrap.Modal(document.getElementById('courseModal'));
        modal.show();
        <?php endif; ?>
        
        function clearForm() {
            document.querySelector('#courseModal form').reset();
            document.querySelector('input[name="id"]').value = '';
            document.querySelector('.modal-title').textContent = 'Novo Curso';
        }
        
        function showQRCode(courseId, courseName, token) {
            const baseUrl = '<?= SITE_URL ?>';
            const surveyUrl = `${baseUrl}/survey/${token}`;
            const qrUrl = `https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=${encodeURIComponent(surveyUrl)}`;
            
            document.getElementById('qrCourseName').textContent = courseName;
            document.getElementById('qrImage').src = qrUrl;
            document.getElementById('surveyLink').textContent = surveyUrl;
            
            const modal = new bootstrap.Modal(document.getElementById('qrModal'));
            modal.show();
        }
        
        function copyLink() {
            const link = document.getElementById('surveyLink').textContent;
            navigator.clipboard.writeText(link).then(() => {
                alert('Link copiado!');
            });
        }
        
        async function deleteCourse(id) {
            if (!confirm('Tem certeza que deseja excluir este curso?')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            
            const response = await fetch('courses.php', {
                method: 'POST',
                body: formData,
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            });
            
            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert('Erro ao excluir curso');
            }
        }
    </script>
</body>
</html>
