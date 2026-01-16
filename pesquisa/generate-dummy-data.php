<?php
/**
 * Generate Dummy Data for Testing
 * Creates 5 courses per month of 2024 for each category with realistic responses
 * 
 * Usage: php generate-dummy-data.php
 */

// Suppress warnings for cleaner output
error_reporting(E_ERROR | E_PARSE);

// Fix for CLI execution - set REQUEST_URI if not set
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '/pesquisa/generate-dummy-data.php';
}

require_once __DIR__ . '/includes/init.php';

// Re-enable error reporting after init
error_reporting(E_ALL);

echo "=== ESESP Survey System - Dummy Data Generator ===\n\n";

$db = PesquisaDatabase::getInstance();

// Categories
$categories = [
    'Agenda Esesp',
    'Esesp na Estrada',
    'EAD',
    'Pós-Graduação',
    'Demanda Específica'
];

// Course name templates
$courseTemplates = [
    'Agenda Esesp' => [
        'Gestão Pública Estratégica',
        'Liderança e Governança',
        'Inovação no Setor Público',
        'Planejamento Governamental',
        'Políticas Públicas Avançadas'
    ],
    'Esesp na Estrada' => [
        'Atendimento ao Cidadão',
        'Gestão Municipal',
        'Desenvolvimento Regional',
        'Modernização Administrativa',
        'Participação Social'
    ],
    'EAD' => [
        'Administração Pública Digital',
        'Gestão de Pessoas',
        'Orçamento e Finanças',
        'Compras Governamentais',
        'Gestão de Projetos Públicos'
    ],
    'Pós-Graduação' => [
        'MBA em Gestão Pública',
        'Especialização em Políticas Públicas',
        'Gestão Estratégica Governamental',
        'Direito Administrativo Aplicado',
        'Controladoria no Setor Público'
    ],
    'Demanda Específica' => [
        'Capacitação Específica Secretaria',
        'Treinamento Equipe Técnica',
        'Workshop Gestão Documental',
        'Curso Personalizado Município',
        'Formação Técnica Especializada'
    ]
];

// Docente names
$docentes = [
    'Dr. João Silva',
    'Dra. Maria Santos',
    'Prof. Carlos Oliveira',
    'Profa. Ana Costa',
    'Dr. Pedro Almeida',
    'Dra. Juliana Ferreira',
    'Prof. Roberto Lima',
    'Profa. Fernanda Souza',
    'Dr. Lucas Martins',
    'Dra. Patricia Rocha'
];

// Month names
$months = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

// Get all active questions
$questions = $db->fetchAll("
    SELECT sq.id, sq.category_id, qc.name as category_name
    FROM survey_questions sq
    JOIN question_categories qc ON sq.category_id = qc.id
    WHERE sq.is_active = TRUE
    ORDER BY sq.display_order
");

if (count($questions) != 15) {
    die("ERROR: Expected 15 questions, found " . count($questions) . ". Please run database schema first.\n");
}

// Group questions by category
$questionsByCategory = [];
foreach ($questions as $q) {
    $questionsByCategory[$q['category_id']][] = $q['id'];
}

echo "Found " . count($questions) . " active questions\n";
echo "Categories: " . count($categories) . "\n";
echo "Months: 12\n";
echo "Courses per month per category: 5\n";
echo "Total courses to create: " . (12 * count($categories) * 5) . "\n";
echo "\nStarting generation...\n\n";

$totalCourses = 0;
$totalResponses = 0;

// Generate for year 2024
$year = 2024;

foreach ($categories as $category) {
    echo "Category: $category\n";
    
    for ($month = 1; $month <= 12; $month++) {
        echo "  {$months[$month]}...\n";
        
        for ($courseNum = 1; $courseNum <= 5; $courseNum++) {
            try {
                // Select course name template
                $courseNameTemplate = $courseTemplates[$category][($courseNum - 1) % count($courseTemplates[$category])];
                $courseName = $courseNameTemplate . " - " . $months[$month];
                
                // Select random docente
                $docente = $docentes[array_rand($docentes)];
                
                // Generate token
                $token = generateCourseToken($courseName, $month, $year);
                
                // Check if exists
                $existing = $db->fetchOne("SELECT id FROM courses WHERE token = ?", [$token]);
                if ($existing) {
                    $token .= '-' . substr(md5(microtime()), 0, 4);
                }
                
                // Insert course
                $courseId = $db->insert('courses', [
                    'token' => $token,
                    'name' => $courseName,
                    'docente_name' => $docente,
                    'category' => $category,
                    'month' => $month,
                    'year' => $year,
                    'description' => "Curso de capacitação em $courseNameTemplate para servidores públicos",
                    'max_responses' => null,
                    'is_active' => true,
                    'created_by' => '00000000000',
                    'created_at' => date('Y-m-d H:i:s', strtotime("$year-$month-15 10:00:00"))
                ]);
                
                // Generate survey URL (without QR code to speed up)
                $surveyUrl = PESQUISA_BASE_URL . '/survey.php?t=' . urlencode($token);
                $db->update('courses', 
                    ['survey_url' => $surveyUrl],
                    'id = ?',
                    [$courseId]
                );
                
                $totalCourses++;
                
                // Generate responses (random between 5 and 25)
                $numResponses = rand(5, 25);
                
                for ($respNum = 1; $respNum <= $numResponses; $respNum++) {
                    // Generate realistic scores with some variation
                    // Most courses should be good (75-90%), some excellent (90+), few need improvement (<75)
                    $rand = rand(1, 100);
                    if ($rand <= 60) {
                        // 60% chance: Good scores (75-89%)
                        $baseScore = rand(75, 89);
                    } elseif ($rand <= 85) {
                        // 25% chance: Excellent scores (90-100%)
                        $baseScore = rand(90, 100);
                    } else {
                        // 15% chance: Needs improvement (60-74%)
                        $baseScore = rand(60, 74);
                    }
                    
                    // Generate responses for all 15 questions
                    $categoryScores = [];
                    $allAnswers = [];
                    
                    foreach ($questionsByCategory as $catId => $questionIds) {
                        $categoryTotal = 0;
                        $categoryCount = count($questionIds);
                        
                        foreach ($questionIds as $questionId) {
                            // Add some variation around base score
                            $variation = rand(-10, 10);
                            $score = max(20, min(100, $baseScore + $variation));
                            
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
                        'completion_time' => rand(180, 600), // 3-10 minutes
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
                
                // Recalculate analytics for this course
                recalculateAnalytics($courseId);
                
                echo "    ✓ Course #$courseNum: $courseName ($numResponses responses)\n";
                
            } catch (Exception $e) {
                echo "    ✗ Error creating course: " . $e->getMessage() . "\n";
            }
        }
    }
    echo "\n";
}

echo "\n=== GENERATION COMPLETE ===\n";
echo "Total courses created: $totalCourses\n";
echo "Total responses generated: $totalResponses\n";
echo "\nYou can now:\n";
echo "1. View courses at: /pesquisa/courses.php\n";
echo "2. View analytics at: /pesquisa/analytics.php\n";
echo "3. Generate diagrams for courses with responses\n";
echo "\nDone!\n";