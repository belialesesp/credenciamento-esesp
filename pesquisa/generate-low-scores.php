<?php
/**
 * Generate Low-Scoring Courses for Testing
 * Creates courses with scores below 60% to test "Necessita Intervenção" classification
 */

// Fix for CLI
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '/pesquisa/generate-low-scores.php';
}

// Suppress warnings
error_reporting(E_ERROR | E_PARSE);

require_once __DIR__ . '/includes/init.php';

// Re-enable error reporting
error_reporting(E_ALL);

echo "=== Generate Low-Scoring Test Courses ===\n\n";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;} .ok{color:green;} .warning{color:orange;} pre{background:white;padding:10px;}</style>";

$db = PesquisaDatabase::getInstance();

// Problem course scenarios
$problemScenarios = [
    [
        'name' => 'Curso Problemático - Docente Despreparado',
        'docente' => 'Prof. Inexperiente Silva',
        'category' => 'Agenda Esesp',
        'description' => 'Curso com graves problemas pedagógicos e didáticos',
        'score_range' => [30, 45] // Very low scores
    ],
    [
        'name' => 'Treinamento Inadequado - Infraestrutura Precária',
        'docente' => 'Dra. Mal Avaliada Costa',
        'category' => 'Esesp na Estrada',
        'description' => 'Problemas sérios de infraestrutura e organização',
        'score_range' => [40, 55]
    ],
    [
        'name' => 'Capacitação Deficiente - Metodologia Ruim',
        'docente' => 'Prof. Problemas Santos',
        'category' => 'EAD',
        'description' => 'Metodologia inadequada, conteúdo desorganizado',
        'score_range' => [35, 50]
    ],
    [
        'name' => 'Workshop Fracassado - Baixa Qualidade',
        'docente' => 'Dra. Críticas Oliveira',
        'category' => 'Demanda Específica',
        'description' => 'Múltiplos problemas reportados pelos participantes',
        'score_range' => [25, 40]
    ],
    [
        'name' => 'Curso Crítico - Requer Intervenção Urgente',
        'docente' => 'Prof. Emergência Lima',
        'category' => 'Pós-Graduação',
        'description' => 'Situação crítica que necessita intervenção imediata',
        'score_range' => [20, 35]
    ]
];

// Get questions
$questions = $db->fetchAll("
    SELECT sq.id, sq.category_id, qc.name as category_name
    FROM survey_questions sq
    JOIN question_categories qc ON sq.category_id = qc.id
    WHERE sq.is_active = TRUE
    ORDER BY sq.display_order
");

// Group questions by category
$questionsByCategory = [];
foreach ($questions as $q) {
    $questionsByCategory[$q['category_id']][] = $q['id'];
}

$totalCourses = 0;
$totalResponses = 0;

// Generate one course per month for variety
$months = [1, 3, 5, 7, 9, 11]; // Different months throughout 2024
$year = 2024;

foreach ($problemScenarios as $index => $scenario) {
    $month = $months[$index % count($months)];
    
    try {
        // Generate token
        $token = generateCourseToken($scenario['name'], $month, $year);
        
        // Check if exists
        $existing = $db->fetchOne("SELECT id FROM courses WHERE token = ?", [$token]);
        if ($existing) {
            $token .= '-' . substr(md5(microtime()), 0, 4);
        }
        
        // Insert course
        $courseId = $db->insert('courses', [
            'token' => $token,
            'name' => $scenario['name'],
            'docente_name' => $scenario['docente'],
            'category' => $scenario['category'],
            'month' => $month,
            'year' => $year,
            'description' => $scenario['description'],
            'max_responses' => null,
            'is_active' => true,
            'created_by' => '00000000000',
            'created_at' => date('Y-m-d H:i:s', strtotime("$year-$month-15 10:00:00"))
        ]);
        
        // Generate survey URL
        $surveyUrl = PESQUISA_BASE_URL . '/survey.php?t=' . urlencode($token);
        $db->update('courses', 
            ['survey_url' => $surveyUrl],
            'id = ?',
            [$courseId]
        );
        
        $totalCourses++;
        
        // Generate responses with LOW scores
        $numResponses = rand(8, 20); // Reasonable number of responses
        
        for ($respNum = 1; $respNum <= $numResponses; $respNum++) {
            // Generate LOW scores based on scenario range
            $baseScore = rand($scenario['score_range'][0], $scenario['score_range'][1]);
            
            // Generate responses for all 15 questions
            $categoryScores = [];
            $allAnswers = [];
            
            foreach ($questionsByCategory as $catId => $questionIds) {
                $categoryTotal = 0;
                $categoryCount = count($questionIds);
                
                foreach ($questionIds as $questionId) {
                    // Add some variation but keep it low
                    $variation = rand(-5, 10);
                    $score = max(10, min(100, $baseScore + $variation));
                    
                    // Convert 0-100% to 1-5 scale
                    $answerValue = round($score / 20);
                    $answerValue = max(1, min(5, $answerValue));
                    
                    $categoryTotal += $answerValue;
                    $allAnswers[] = [
                        'question_id' => $questionId,
                        'answer_value' => $answerValue,
                        'answer_score' => $answerValue
                    ];
                }
                
                // Calculate category score (0-100%)
                $categoryScores[$catId] = ($categoryTotal / $categoryCount) * 20;
            }
            
            // Calculate weighted overall score
            // Pedagógico (40%), Didático (35%), Infraestrutura (25%)
            $overallScore = 
                ($categoryScores[1] * 0.40) + 
                ($categoryScores[2] * 0.35) + 
                ($categoryScores[3] * 0.25);
            
            // Insert response
            $responseDate = date('Y-m-d H:i:s', strtotime("$year-$month-" . rand(16, 28) . " " . rand(8, 18) . ":00:00"));
            
            $responseId = $db->insert('responses', [
                'course_id' => $courseId,
                'overall_score' => $overallScore,
                'category_scores' => json_encode([
                    'pedagogical' => $categoryScores[1],
                    'didactic' => $categoryScores[2],
                    'infrastructure' => $categoryScores[3]
                ]),
                'completion_time' => rand(180, 600),
                'submitted_at' => $responseDate,
                'respondent_ip' => '127.0.0.' . rand(1, 255)
            ]);
            
            // Insert individual answers
            foreach ($allAnswers as $answer) {
                $db->insert('response_answers', [
                    'response_id' => $responseId,
                    'question_id' => $answer['question_id'],
                    'answer_value' => $answer['answer_value'],
                    'answer_score' => $answer['answer_score']
                ]);
            }
            
            $totalResponses++;
        }
        
        // Recalculate analytics
        recalculateAnalytics($courseId);
        
        // Get final score
        $analytics = $db->fetchOne("SELECT overall_score FROM analytics_cache WHERE course_id = ?", [$courseId]);
        $finalScore = $analytics['overall_score'] ?? 0;
        
        echo "<p class='warning'>✓ Course created: <strong>" . htmlspecialchars($scenario['name']) . "</strong></p>";
        echo "<p>  - Score: <strong style='color:red;'>" . number_format($finalScore, 1) . "%</strong> (Necessita Intervenção)</p>";
        echo "<p>  - Responses: $numResponses</p>";
        echo "<p>  - Month: $month/$year</p>\n";
        
    } catch (Exception $e) {
        echo "<p style='color:red;'>✗ Error creating course: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
}

echo "\n<hr>\n";
echo "<h2>Summary</h2>\n";
echo "<p class='ok'>✓ Total low-scoring courses created: <strong>$totalCourses</strong></p>\n";
echo "<p class='ok'>✓ Total responses generated: <strong>$totalResponses</strong></p>\n";
echo "\n<h3>These courses have scores BELOW 60% and should be classified as:</h3>\n";
echo "<p style='color:red;font-weight:bold;font-size:1.2em;'>❌ Necessita Intervenção</p>\n";

echo "\n<h3>Next Steps:</h3>\n";
echo "<ol>\n";
echo "<li><a href='analytics.php'>Go to Analytics</a> - Filter and view these low-scoring courses</li>\n";
echo "<li><a href='lowest-scores.php'>View Score Report</a> - See all courses sorted by score</li>\n";
echo "<li><strong>Generate Diagrams</strong> - Click 'Gerar Diagrama' on these courses to see RED classification</li>\n";
echo "</ol>\n";

echo "\n<h3>Low-Scoring Courses Created:</h3>\n";
echo "<table border='1' cellpadding='8' style='background:white;border-collapse:collapse;'>\n";
echo "<tr style='background:#fee2e2;'>\n";
echo "<th>Course Name</th><th>Category</th><th>Month</th><th>Expected Score Range</th>\n";
echo "</tr>\n";

foreach ($problemScenarios as $index => $scenario) {
    $month = $months[$index % count($months)];
    echo "<tr>\n";
    echo "<td>" . htmlspecialchars($scenario['name']) . "<br><small><em>" . htmlspecialchars($scenario['docente']) . "</em></small></td>\n";
    echo "<td>" . htmlspecialchars($scenario['category']) . "</td>\n";
    echo "<td>$month/2024</td>\n";
    echo "<td style='color:red;font-weight:bold;'>" . $scenario['score_range'][0] . "% - " . $scenario['score_range'][1] . "%</td>\n";
    echo "</tr>\n";
}
echo "</table>\n";

echo "\n<p><strong>Done!</strong> You can now test the red 'Necessita Intervenção' classification in your diagrams and analytics. 🔴</p>\n";
?>