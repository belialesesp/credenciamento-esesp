<?php
/**
 * Public Survey Form
 * Students scan QR Code → Opens this survey → Fills 15 questions
 */

// This file should be publicly accessible (no authentication required)
// So we use a minimal init without AuthMiddleware

session_start();

// Load only database class
require_once __DIR__ . '/includes/PesquisaDatabase.php';

define('PESQUISA_BASE_URL', 'https://credenciamento.esesp.es.gov.br/pesquisa');

$db = PesquisaDatabase::getInstance();

// Get course token from URL
$token = $_GET['t'] ?? '';

if (empty($token)) {
    die('Link inválido. Token não encontrado.');
}

// Get course by token
$course = $db->fetchOne("
    SELECT c.*, 
           (SELECT COUNT(*) FROM responses WHERE course_id = c.id) as current_responses
    FROM courses c
    WHERE c.token = ? AND c.is_active = TRUE
", [$token]);

if (!$course) {
    die('Curso não encontrado ou inativo.');
}

// Check if max responses reached
if ($course['max_responses'] && $course['current_responses'] >= $course['max_responses']) {
    die('Este curso já atingiu o número máximo de respostas. Obrigado!');
}

// Get all questions
$questions = $db->fetchAll("
    SELECT sq.*, qc.name as category_name, qc.weight
    FROM survey_questions sq
    JOIN question_categories qc ON sq.category_id = qc.id
    WHERE sq.is_active = TRUE
    ORDER BY sq.display_order
");

// Group questions by category
$questionsByCategory = [];
foreach ($questions as $question) {
    $catName = $question['category_name'];
    if (!isset($questionsByCategory[$catName])) {
        $questionsByCategory[$catName] = [
            'name' => $catName,
            'weight' => $question['weight'],
            'questions' => []
        ];
    }
    $questionsByCategory[$catName]['questions'][] = $question;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $startTime = $_POST['start_time'] ?? time();
        $completionTime = time() - $startTime;
        
        // Validate all questions answered
        $answers = [];
        foreach ($questions as $question) {
            $qId = $question['id'];
            $answer = $_POST["q_$qId"] ?? null;
            
            if ($question['is_required'] && empty($answer)) {
                throw new Exception("Por favor, responda todas as perguntas obrigatórias.");
            }
            
            $answers[$qId] = [
                'value' => $answer,
                'score' => (int)$answer, // For Likert scale
                'category_id' => $question['category_id']
            ];
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        try {
            // Calculate scores by category
            $categoryScores = [];
            foreach ($questionsByCategory as $catName => $catData) {
                $catQuestions = $catData['questions'];
                $catScores = [];
                
                foreach ($catQuestions as $q) {
                    if (isset($answers[$q['id']])) {
                        $catScores[] = $answers[$q['id']]['score'];
                    }
                }
                
                if (!empty($catScores)) {
                    $avgScore = array_sum($catScores) / count($catScores);
                    $categoryScores[$catName] = ($avgScore / 5) * 100; // Convert to 0-100 scale
                }
            }
            
            // Calculate overall weighted score
            $overallScore = 0;
            foreach ($questionsByCategory as $catName => $catData) {
                if (isset($categoryScores[$catName])) {
                    $weight = $catData['weight'] / 100;
                    $overallScore += $categoryScores[$catName] * $weight;
                }
            }
            
            // Insert response
            $responseId = $db->insert('responses', [
                'course_id' => $course['id'],
                'respondent_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'respondent_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'overall_score' => round($overallScore, 2),
                'category_scores' => json_encode($categoryScores),
                'completion_time' => $completionTime
            ]);
            
            // Insert answers
            foreach ($answers as $questionId => $answerData) {
                $db->insert('response_answers', [
                    'response_id' => $responseId,
                    'question_id' => $questionId,
                    'answer_value' => $answerData['value'],
                    'answer_score' => $answerData['score']
                ]);
            }
            
            // Recalculate analytics IMMEDIATELY
            $db->callProcedure('RecalculateAnalytics', [$course['id']]);
            
            $db->commit();
            
            // Redirect to thank you page
            header("Location: thank-you.php?score=" . round($overallScore, 1));
            exit;
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Survey submission error: " . $error);
    }
}

$startTime = time();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesquisa de Satisfação - <?= htmlspecialchars($course['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1e3a5f;
            --secondary: #2c5f8d;
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
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .survey-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .survey-header h1 {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }
        
        .survey-body {
            padding: 2rem;
        }
        
        .category-section {
            margin-bottom: 2.5rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .category-section:last-child {
            border-bottom: none;
        }
        
        .category-header {
            background: #f8fafc;
            padding: 1rem 1.5rem;
            border-left: 4px solid var(--primary);
            margin-bottom: 1.5rem;
            border-radius: 4px;
        }
        
        .category-header h3 {
            margin: 0;
            color: var(--primary);
            font-size: 1.25rem;
        }
        
        .category-header .weight-badge {
            background: var(--primary);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            margin-left: 1rem;
        }
        
        .question-block {
            margin-bottom: 2rem;
        }
        
        .question-number {
            display: inline-block;
            background: var(--primary);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            text-align: center;
            line-height: 32px;
            font-weight: 600;
            margin-right: 0.75rem;
        }
        
        .question-text {
            font-size: 1.05rem;
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        .likert-scale {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .likert-option {
            position: relative;
        }
        
        .likert-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .likert-option label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            min-width: 80px;
        }
        
        .likert-option input:checked + label {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        .likert-option label:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }
        
        .likert-option .emoji {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .likert-option .label-text {
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .progress-bar-container {
            position: sticky;
            top: 0;
            background: white;
            padding: 1rem 0;
            z-index: 100;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .submit-section {
            text-align: center;
            padding-top: 2rem;
        }
        
        @media (max-width: 768px) {
            .likert-scale {
                gap: 0.5rem;
            }
            
            .likert-option label {
                min-width: 60px;
                padding: 0.75rem 0.5rem;
            }
            
            .likert-option .emoji {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="survey-container">
        <div class="survey-card">
            <!-- Header -->
            <div class="survey-header">
                <h1><i class="bi bi-clipboard-check me-2"></i><?= htmlspecialchars($course['name']) ?></h1>
                <p class="mb-1"><strong>Docente:</strong> <?= htmlspecialchars($course['docente_name']) ?></p>
                <p class="mb-0"><small><?= htmlspecialchars($course['category']) ?> - <?= date('m/Y', mktime(0, 0, 0, $course['month'], 1, $course['year'])) ?></small></p>
            </div>
            
            <!-- Body -->
            <div class="survey-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <!-- Progress Bar -->
                <div class="progress-bar-container">
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar" id="survey-progress" role="progressbar" style="width: 0%"></div>
                    </div>
                    <small class="text-muted"><span id="answered-count">0</span> de <?= count($questions) ?> respondidas</small>
                </div>
                
                <form method="POST" action="" id="survey-form">
                    <input type="hidden" name="start_time" value="<?= $startTime ?>">
                    
                    <?php $questionNumber = 1; ?>
                    <?php foreach ($questionsByCategory as $catName => $catData): ?>
                        <div class="category-section">
                            <div class="category-header">
                                <h3>
                                    <?= htmlspecialchars($catName) ?>
                                    <span class="weight-badge">Peso: <?= number_format($catData['weight'], 0) ?>%</span>
                                </h3>
                            </div>
                            
                            <?php foreach ($catData['questions'] as $question): ?>
                                <div class="question-block">
                                    <div class="question-text">
                                        <span class="question-number"><?= $questionNumber ?></span>
                                        <?= htmlspecialchars($question['question_text']) ?>
                                        <?php if ($question['is_required']): ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="likert-scale">
                                        <?php
                                        $options = [
                                            1 => ['emoji' => '😞', 'label' => 'Muito Insatisfeito'],
                                            2 => ['emoji' => '😕', 'label' => 'Insatisfeito'],
                                            3 => ['emoji' => '😐', 'label' => 'Neutro'],
                                            4 => ['emoji' => '🙂', 'label' => 'Satisfeito'],
                                            5 => ['emoji' => '😄', 'label' => 'Muito Satisfeito']
                                        ];
                                        
                                        foreach ($options as $value => $option):
                                        ?>
                                            <div class="likert-option">
                                                <input type="radio" 
                                                       name="q_<?= $question['id'] ?>" 
                                                       id="q_<?= $question['id'] ?>_<?= $value ?>" 
                                                       value="<?= $value ?>"
                                                       <?= $question['is_required'] ? 'required' : '' ?>
                                                       onchange="updateProgress()">
                                                <label for="q_<?= $question['id'] ?>_<?= $value ?>">
                                                    <span class="emoji"><?= $option['emoji'] ?></span>
                                                    <span class="label-text"><?= $value ?></span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php $questionNumber++; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Submit Section -->
                    <div class="submit-section">
                        <button type="submit" class="btn btn-primary btn-lg px-5" id="submit-btn" disabled>
                            <i class="bi bi-send me-2"></i>Enviar Respostas
                        </button>
                        <p class="text-muted mt-3 small">
                            <i class="bi bi-shield-check me-1"></i>Suas respostas são anônimas e confidenciais
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const totalQuestions = <?= count($questions) ?>;
        
        function updateProgress() {
            const answered = document.querySelectorAll('input[type="radio"]:checked').length;
            const percentage = (answered / totalQuestions) * 100;
            
            document.getElementById('survey-progress').style.width = percentage + '%';
            document.getElementById('answered-count').textContent = answered;
            
            // Enable submit button when all required questions are answered
            const submitBtn = document.getElementById('submit-btn');
            if (answered === totalQuestions) {
                submitBtn.disabled = false;
                submitBtn.classList.add('pulse');
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.remove('pulse');
            }
        }
        
        // Smooth scroll to next question on answer
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Small delay for visual feedback
                setTimeout(() => {
                    const currentQuestion = this.closest('.question-block');
                    const nextQuestion = currentQuestion.nextElementSibling;
                    
                    if (nextQuestion && nextQuestion.classList.contains('question-block')) {
                        nextQuestion.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 300);
            });
        });
        
        // Prevent accidental page leave
        let formSubmitted = false;
        document.getElementById('survey-form').addEventListener('submit', function() {
            formSubmitted = true;
        });
        
        window.addEventListener('beforeunload', function(e) {
            if (!formSubmitted && document.querySelectorAll('input[type="radio"]:checked').length > 0) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>
