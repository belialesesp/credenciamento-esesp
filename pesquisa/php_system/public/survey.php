<?php
/**
 * survey.php - Formulário de Pesquisa de Satisfação
 * URL: /pesquisas/survey/{token}
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Obter token da URL
$token = $_GET['token'] ?? null;

if (!$token) {
    die("Link de pesquisa inválido.");
}

$db = Database::getInstance();

// Buscar curso pelo token
$course = $db->fetchOne(
    "SELECT c.*, cat.name as category_name 
     FROM courses c 
     JOIN categories cat ON c.category_id = cat.id 
     WHERE c.survey_token = ? AND c.is_active = 1",
    [$token]
);

if (!$course) {
    die("Pesquisa não encontrada ou já encerrada.");
}

// Processar submissão
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar dados
    $responses = [];
    for ($i = 1; $i <= 15; $i++) {
        $value = intval($_POST["q{$i}"] ?? 0);
        if ($value < 1 || $value > 5) {
            die(json_encode(['success' => false, 'message' => 'Resposta inválida']));
        }
        $responses["q{$i}"] = $value;
    }
    
    // Inserir resposta
    $sql = "INSERT INTO responses (
        course_id, 
        q1_organizacao_curricular, q2_coerencia_pedagogica, q3_metodologia_ensino, 
        q4_carga_horaria, q5_material_pedagogico,
        q6_dominio_conteudo, q7_disponibilidade_escuta, q8_recursos_didaticos, 
        q9_participacao_ativa, q10_aplicabilidade,
        q11_conforto_sala, q12_acessibilidade, q13_infraestrutura_tech, 
        q14_manutencao_predial, q15_limpeza_organizacao,
        ip_address, user_agent
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = array_merge(
        [$course['id']], 
        array_values($responses),
        [$_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]
    );
    
    try {
        $db->query($sql, $params);
        
        // Recalcular análises
        require_once __DIR__ . '/../includes/analytics.php';
        $analytics = new Analytics();
        $analytics->calculateIndicators($course['id']);
        
        echo json_encode(['success' => true, 'message' => 'Resposta registrada com sucesso!']);
        exit;
    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar resposta']);
        exit;
    }
}

// Perguntas do questionário
$questions = [
    // Pedagógicos (40%)
    [
        'id' => 1,
        'category' => 'Aspectos Pedagógicos',
        'text' => 'A organização curricular atendeu as expectativas de forma clara e organizada?'
    ],
    [
        'id' => 2,
        'category' => 'Aspectos Pedagógicos',
        'text' => 'A coerência pedagógica contribuiu para classificar esse curso de qualidade?'
    ],
    [
        'id' => 3,
        'category' => 'Aspectos Pedagógicos',
        'text' => 'A metodologia de ensino utilizada foi estimulante e variada?'
    ],
    [
        'id' => 4,
        'category' => 'Aspectos Pedagógicos',
        'text' => 'A carga horária foi suficiente para sua aprendizagem?'
    ],
    [
        'id' => 5,
        'category' => 'Aspectos Pedagógicos',
        'text' => 'O material pedagógico oferece qualidade e compreensão dos assuntos abordados?'
    ],
    
    // Didáticos (35%)
    [
        'id' => 6,
        'category' => 'Aspectos Didáticos',
        'text' => 'O docente demonstrou domínio de conteúdo e experiência profissional?'
    ],
    [
        'id' => 7,
        'category' => 'Aspectos Didáticos',
        'text' => 'O docente mostrou-se disponível e receptivo às questões dos participantes?'
    ],
    [
        'id' => 8,
        'category' => 'Aspectos Didáticos',
        'text' => 'Os recursos didáticos utilizados favoreceram a aprendizagem?'
    ],
    [
        'id' => 9,
        'category' => 'Aspectos Didáticos',
        'text' => 'O curso estimulou a participação ativa e o diálogo entre os participantes?'
    ],
    [
        'id' => 10,
        'category' => 'Aspectos Didáticos',
        'text' => 'O curso contribuiu para sua atuação profissional de forma aplicável?'
    ],
    
    // Infraestrutura (25%)
    [
        'id' => 11,
        'category' => 'Aspectos de Infraestrutura',
        'text' => 'A sala de aula ofereceu conforto adequado (temperatura, iluminação, acústica)?'
    ],
    [
        'id' => 12,
        'category' => 'Aspectos de Infraestrutura',
        'text' => 'A acessibilidade do espaço físico foi adequada?'
    ],
    [
        'id' => 13,
        'category' => 'Aspectos de Infraestrutura',
        'text' => 'Os equipamentos tecnológicos funcionaram adequadamente?'
    ],
    [
        'id' => 14,
        'category' => 'Aspectos de Infraestrutura',
        'text' => 'A manutenção predial está em bom estado de conservação?'
    ],
    [
        'id' => 15,
        'category' => 'Aspectos de Infraestrutura',
        'text' => 'A limpeza e organização dos espaços foram satisfatórias?'
    ]
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesquisa de Satisfação - ESESP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1e3a5f;
            --secondary: #2c5f8d;
            --success: #10b981;
            --warning: #f59e0b;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .survey-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .survey-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .survey-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .survey-header h1 {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }
        
        .survey-info {
            background: rgba(255,255,255,0.1);
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
        }
        
        .survey-info p {
            margin: 0.25rem 0;
            font-size: 0.95rem;
        }
        
        .survey-body {
            padding: 2rem;
        }
        
        .progress-bar-container {
            background: #e5e7eb;
            height: 8px;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .progress-bar-fill {
            background: linear-gradient(90deg, var(--success), var(--primary));
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .question-card {
            display: none;
            animation: fadeIn 0.3s;
        }
        
        .question-card.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .category-badge {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        
        .question-text {
            font-size: 1.25rem;
            color: #1f2937;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .rating-options {
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .rating-option {
            flex: 1;
            text-align: center;
        }
        
        .rating-option input[type="radio"] {
            display: none;
        }
        
        .rating-option label {
            display: block;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }
        
        .rating-option label:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .rating-option input:checked + label {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
            transform: scale(1.05);
        }
        
        .rating-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .rating-label {
            font-size: 0.875rem;
        }
        
        .navigation-buttons {
            display: flex;
            gap: 1rem;
            justify-content: space-between;
        }
        
        .btn-nav {
            padding: 0.75rem 2rem;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-prev {
            background: #e5e7eb;
            color: #1f2937;
        }
        
        .btn-prev:hover {
            background: #d1d5db;
        }
        
        .btn-next, .btn-submit {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }
        
        .btn-next:hover, .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .success-message {
            display: none;
            text-align: center;
            padding: 3rem;
        }
        
        .success-icon {
            font-size: 4rem;
            color: var(--success);
            margin-bottom: 1rem;
        }
        
        .scale-legend {
            display: flex;
            justify-content: space-between;
            margin-top: 0.5rem;
            font-size: 0.75rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="survey-container">
        <div class="survey-card">
            <div class="survey-header">
                <h1>Pesquisa de Satisfação</h1>
                <div class="survey-info">
                    <p><strong>Curso:</strong> <?= htmlspecialchars($course['name']) ?></p>
                    <p><strong>Docente:</strong> <?= htmlspecialchars($course['docente_name']) ?></p>
                    <p><strong>Categoria:</strong> <?= htmlspecialchars($course['category_name']) ?></p>
                </div>
            </div>
            
            <div class="survey-body">
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" id="progressBar" style="width: 0%"></div>
                </div>
                
                <form id="surveyForm">
                    <?php foreach ($questions as $index => $question): ?>
                    <div class="question-card" data-question="<?= $index ?>">
                        <span class="category-badge"><?= $question['category'] ?></span>
                        <div class="question-text">
                            <?= $index + 1 ?>. <?= $question['text'] ?>
                        </div>
                        
                        <div class="rating-options">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <div class="rating-option">
                                <input type="radio" 
                                       id="q<?= $question['id'] ?>_<?= $i ?>" 
                                       name="q<?= $question['id'] ?>" 
                                       value="<?= $i ?>" 
                                       required>
                                <label for="q<?= $question['id'] ?>_<?= $i ?>">
                                    <div class="rating-number"><?= $i ?></div>
                                    <div class="rating-label">
                                        <?php
                                        $labels = ['Discordo Totalmente', 'Discordo', 'Neutro', 'Concordo', 'Concordo Totalmente'];
                                        echo $labels[$i-1];
                                        ?>
                                    </div>
                                </label>
                            </div>
                            <?php endfor; ?>
                        </div>
                        
                        <div class="scale-legend">
                            <span>👎 Discordo Totalmente</span>
                            <span>👍 Concordo Totalmente</span>
                        </div>
                        
                        <div class="navigation-buttons">
                            <button type="button" class="btn-nav btn-prev" onclick="previousQuestion()" <?= $index === 0 ? 'style="visibility: hidden"' : '' ?>>
                                ← Anterior
                            </button>
                            <?php if ($index < count($questions) - 1): ?>
                            <button type="button" class="btn-nav btn-next" onclick="nextQuestion()">
                                Próxima →
                            </button>
                            <?php else: ?>
                            <button type="button" class="btn-nav btn-submit" onclick="submitSurvey()">
                                ✓ Enviar Pesquisa
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </form>
                
                <div class="success-message" id="successMessage">
                    <div class="success-icon">✓</div>
                    <h2>Obrigado pela sua participação!</h2>
                    <p>Sua resposta foi registrada com sucesso.</p>
                    <p style="color: #6b7280; margin-top: 1rem;">
                        Suas contribuições ajudam a melhorar a qualidade dos nossos cursos.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentQuestion = 0;
        const totalQuestions = <?= count($questions) ?>;
        
        // Mostrar primeira pergunta
        document.addEventListener('DOMContentLoaded', () => {
            showQuestion(0);
        });
        
        function showQuestion(index) {
            document.querySelectorAll('.question-card').forEach(card => {
                card.classList.remove('active');
            });
            
            const card = document.querySelector(`[data-question="${index}"]`);
            if (card) {
                card.classList.add('active');
                currentQuestion = index;
                updateProgress();
            }
        }
        
        function nextQuestion() {
            // Validar se a pergunta atual foi respondida
            const currentInput = document.querySelector(`[data-question="${currentQuestion}"] input[type="radio"]:checked`);
            if (!currentInput) {
                alert('Por favor, selecione uma resposta antes de continuar.');
                return;
            }
            
            if (currentQuestion < totalQuestions - 1) {
                showQuestion(currentQuestion + 1);
            }
        }
        
        function previousQuestion() {
            if (currentQuestion > 0) {
                showQuestion(currentQuestion - 1);
            }
        }
        
        function updateProgress() {
            const progress = ((currentQuestion + 1) / totalQuestions) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
        }
        
        async function submitSurvey() {
            // Validar última pergunta
            const lastInput = document.querySelector(`[data-question="${currentQuestion}"] input[type="radio"]:checked`);
            if (!lastInput) {
                alert('Por favor, responda a última pergunta.');
                return;
            }
            
            // Coletar todas as respostas
            const formData = new FormData(document.getElementById('surveyForm'));
            
            // Verificar se todas as 15 perguntas foram respondidas
            let answeredCount = 0;
            for (let i = 1; i <= 15; i++) {
                if (formData.has('q' + i)) {
                    answeredCount++;
                }
            }
            
            if (answeredCount < 15) {
                alert('Por favor, responda todas as perguntas antes de enviar.');
                return;
            }
            
            // Enviar via AJAX
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Mostrar mensagem de sucesso
                    document.querySelector('.survey-body form').style.display = 'none';
                    document.querySelector('.progress-bar-container').style.display = 'none';
                    document.getElementById('successMessage').style.display = 'block';
                } else {
                    alert('Erro ao enviar pesquisa: ' + result.message);
                }
            } catch (error) {
                alert('Erro ao enviar pesquisa. Por favor, tente novamente.');
                console.error(error);
            }
        }
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight' && currentQuestion < totalQuestions - 1) {
                nextQuestion();
            } else if (e.key === 'ArrowLeft' && currentQuestion > 0) {
                previousQuestion();
            }
        });
    </script>
</body>
</html>
