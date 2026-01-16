<?php
/**
 * Initialization file for Survey Module
 * Works with your existing database.php that creates $pdo based on URL
 */

error_log("Pesquisa init.php: Starting initialization");

// Load root init - this will load your database.php which creates $pdo
$rootInitPath = dirname(dirname(dirname(__FILE__))) . '/init.php';

if (!file_exists($rootInitPath)) {
    $rootInitPath = '/var/www/html/init.php';
}

if (file_exists($rootInitPath)) {
    require_once $rootInitPath;
    error_log("Pesquisa init.php: Successfully loaded root init.php from: $rootInitPath");
} else {
    die("Configuration error: Root init.php not found");
}

// Verify we have a database connection (your config creates $pdo, not constants)
if (!isset($pdo) || !($pdo instanceof PDO)) {
    error_log("Pesquisa init.php: WARNING - PDO connection not found, will be created by PesquisaDatabase");
}

// Check authentication
if (class_exists('AuthMiddleware')) {
    try {
        AuthMiddleware::requireAuth();
        error_log("Pesquisa init.php: Authentication check passed");
    } catch (Exception $e) {
        error_log("Pesquisa init.php: Authentication error - " . $e->getMessage());
    }
}

// Define pesquisa-specific constants
if (!defined('PESQUISA_BASE_URL')) {
    define('PESQUISA_BASE_URL', 'https://credenciamento.esesp.es.gov.br/pesquisa');
    define('PESQUISA_SITE_NAME', 'Pesquisas ESESP');
    define('QR_CODE_DIR', dirname(dirname(__FILE__)) . '/qrcodes/');
    define('DIAGRAMS_DIR', dirname(dirname(__FILE__)) . '/diagrams/');
}

// Create directories
if (!file_exists(QR_CODE_DIR)) {
    mkdir(QR_CODE_DIR, 0755, true);
}
if (!file_exists(DIAGRAMS_DIR)) {
    mkdir(DIAGRAMS_DIR, 0755, true);
}

// Load PesquisaDatabase class
require_once __DIR__ . '/PesquisaDatabase.php';
error_log("Pesquisa init.php: Loaded PesquisaDatabase class");

/**
 * Get current authenticated user name
 */
function getCurrentUserName() {
    if (function_exists('getCurrentUser')) {
        $user = getCurrentUser();
        return $user['apelido'] ?? $user['nome'] ?? 'Usuário';
    }
    if (class_exists('AcessoCidadaoAuth')) {
        $user = AcessoCidadaoAuth::getUser();
        return $user['apelido'] ?? $user['nome'] ?? 'Usuário';
    }
    return 'Usuário';
}

/**
 * Get current authenticated user CPF
 */
function getCurrentUserCPF() {
    if (function_exists('getCurrentUser')) {
        $user = getCurrentUser();
        return $user['cpf'] ?? null;
    }
    if (class_exists('AcessoCidadaoAuth')) {
        $user = AcessoCidadaoAuth::getUser();
        return $user['cpf'] ?? null;
    }
    return null;
}

/**
 * Check if user is admin for pesquisa module
 */
function isPesquisaAdmin() {
    $user = null;
    
    if (function_exists('getCurrentUser')) {
        $user = getCurrentUser();
    } elseif (class_exists('AcessoCidadaoAuth')) {
        $user = AcessoCidadaoAuth::getUser();
    }
    
    if (!$user) {
        return false;
    }
    
    // Check admin role
    if (isset($user['role']) && $user['role'] === 'admin') {
        return true;
    }
    
    // For now, all authenticated users can manage surveys
    return true;
}

/**
 * Sanitize output
 */
function sanitize($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate token
 */
function generateCourseToken($name, $month, $year) {
    $months = [
        1 => 'jan', 2 => 'fev', 3 => 'mar', 4 => 'abr',
        5 => 'mai', 6 => 'jun', 7 => 'jul', 8 => 'ago',
        9 => 'set', 10 => 'out', 11 => 'nov', 12 => 'dez'
    ];
    
    $token = mb_strtolower($name, 'UTF-8');
    $token = str_replace(
        ['á','à','ã','â','é','ê','í','ó','õ','ô','ú','ç',' '],
        ['a','a','a','a','e','e','i','o','o','o','u','c','-'],
        $token
    );
    $token = preg_replace('/[^a-z0-9-]/', '', $token);
    $token = preg_replace('/-+/', '-', $token);
    $token = trim($token, '-');
    $token = substr($token, 0, 50);
    
    $monthAbbr = $months[$month] ?? 'xxx';
    return $token . '-' . $monthAbbr . '-' . $year;
}

/**
 * Generate QR Code with multiple fallback methods
 */
function generateQRCode($courseId, $token) {
    try {
        $surveyUrl = PESQUISA_BASE_URL . '/survey.php?t=' . urlencode($token);
        $filename = 'qr_' . $token . '.png';
        $filepath = QR_CODE_DIR . $filename;
        
        error_log("QR Code: Generating for URL: $surveyUrl");
        
        $qrImage = null;
        
        // Method 1: Try primary QR API with timeout
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'ignore_errors' => true
                ]
            ]);
            
            $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($surveyUrl);
            $qrImage = @file_get_contents($qrApiUrl, false, $context);
            
            if ($qrImage && strlen($qrImage) > 100) {
                error_log("QR Code: Successfully generated using primary API");
            } else {
                $qrImage = null;
            }
        } catch (Exception $e) {
            error_log("QR Code: Primary API failed - " . $e->getMessage());
        }
        
        // Method 2: Try Google Charts API
        if (!$qrImage) {
            try {
                error_log("QR Code: Trying Google Charts API");
                $qrApiUrl = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($surveyUrl);
                $qrImage = @file_get_contents($qrApiUrl, false, $context);
                
                if ($qrImage && strlen($qrImage) > 100) {
                    error_log("QR Code: Successfully generated using Google API");
                } else {
                    $qrImage = null;
                }
            } catch (Exception $e) {
                error_log("QR Code: Google API failed - " . $e->getMessage());
            }
        }
        
        // Method 3: Create placeholder image with URL
        if (!$qrImage) {
            error_log("QR Code: Creating placeholder image");
            $qrImage = createPlaceholderQR($surveyUrl, $token);
        }
        
        // Save the image
        if ($qrImage) {
            file_put_contents($filepath, $qrImage);
            error_log("QR Code: Saved to $filepath");
            
            $db = PesquisaDatabase::getInstance();
            $db->update('courses', 
                ['qr_code_path' => 'qrcodes/' . $filename, 'survey_url' => $surveyUrl],
                'id = ?',
                [$courseId]
            );
            
            return $filename;
        }
        
        // If image generation failed, at least save the URL
        $db = PesquisaDatabase::getInstance();
        $db->update('courses', 
            ['survey_url' => $surveyUrl],
            'id = ?',
            [$courseId]
        );
        
        error_log("QR Code: Could not generate image, but URL saved");
        return false;
        
    } catch (Exception $e) {
        error_log("QR Code generation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Create a placeholder QR code image
 */
function createPlaceholderQR($url, $token) {
    $width = 300;
    $height = 300;
    $image = imagecreatetruecolor($width, $height);
    
    if (!$image) {
        error_log("QR Code: Could not create image resource");
        return false;
    }
    
    // Colors
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 30, 58, 95); // ESESP blue
    $gray = imagecolorallocate($image, 107, 114, 128);
    
    // Fill background
    imagefill($image, 0, 0, $white);
    
    // Draw border
    imagesetthickness($image, 3);
    imagerectangle($image, 10, 10, $width-11, $height-11, $black);
    
    // Add text
    imagestring($image, 5, 95, 100, "QR CODE ESESP", $black);
    imagestring($image, 3, 80, 130, "Pesquisa de Satisfacao", $gray);
    imagestring($image, 2, 90, 160, substr($token, 0, 30), $gray);
    
    // Add URL info
    imagestring($image, 1, 30, 250, "Acesse manualmente:", $gray);
    imagestring($image, 1, 15, 265, substr($url, 0, 45), $gray);
    if (strlen($url) > 45) {
        imagestring($image, 1, 15, 280, substr($url, 45, 45), $gray);
    }
    
    // Save to buffer
    ob_start();
    imagepng($image);
    $imageData = ob_get_clean();
    imagedestroy($image);
    
    error_log("QR Code: Created placeholder image");
    return $imageData;
}

/**
 * Flash messages
 */
function setFlashMessage($message, $type = 'info', $allowHtml = false) {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type,
        'html' => $allowHtml
    ];
}

function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        
        $alertClass = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info'
        ][$flash['type']] ?? 'alert-info';
        
        $icon = [
            'success' => '<i class="bi bi-check-circle-fill me-2"></i>',
            'error' => '<i class="bi bi-x-circle-fill me-2"></i>',
            'warning' => '<i class="bi bi-exclamation-triangle-fill me-2"></i>',
            'info' => '<i class="bi bi-info-circle-fill me-2"></i>'
        ][$flash['type']] ?? '';
        
        // Allow HTML if explicitly set
        $message = isset($flash['html']) && $flash['html'] ? $flash['message'] : sanitize($flash['message']);
        
        return sprintf(
            '<div class="alert %s alert-dismissible fade show" role="alert">
                %s%s
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>',
            $alertClass,
            $icon,
            $message
        );
    }
    return '';
}

/**
 * Score formatting
 */
function formatScore($score) {
    if ($score >= 90) return '<span class="badge bg-success">Excelência: ' . number_format($score, 1) . '%</span>';
    if ($score >= 75) return '<span class="badge bg-primary">Muito Bom: ' . number_format($score, 1) . '%</span>';
    if ($score >= 60) return '<span class="badge bg-warning">Adequado: ' . number_format($score, 1) . '%</span>';
    return '<span class="badge bg-danger">Intervenção: ' . number_format($score, 1) . '%</span>';
}

function getClassification($score) {
    if ($score >= 90) return 'Excelência institucional';
    if ($score >= 75) return 'Muito bom';
    if ($score >= 60) return 'Adequado';
    return 'Necessita intervenção';
}

/**
 * Analytics
 */
function recalculateAnalytics($courseId) {
    try {
        $db = PesquisaDatabase::getInstance();
        $db->callProcedure('RecalculateAnalytics', [$courseId]);
        return true;
    } catch (Exception $e) {
        error_log("Analytics recalculation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Database queries
 */
function getActiveQuestions() {
    $db = PesquisaDatabase::getInstance();
    return $db->fetchAll("
        SELECT sq.*, qc.name as category_name, qc.weight as category_weight
        FROM survey_questions sq
        JOIN question_categories qc ON sq.category_id = qc.id
        WHERE sq.is_active = TRUE
        ORDER BY sq.display_order
    ");
}

function getCourseByToken($token) {
    $db = PesquisaDatabase::getInstance();
    return $db->fetchOne("
        SELECT * FROM courses 
        WHERE token = ? AND is_active = TRUE
    ", [$token]);
}

function getCourseStats($courseId) {
    $db = PesquisaDatabase::getInstance();
    return $db->fetchOne("
        SELECT * FROM analytics_cache 
        WHERE course_id = ?
    ", [$courseId]);
}

error_log("Pesquisa init.php: Initialization complete");

/**
 * Generate yearly progress diagram
 */
function generateYearlyDiagram($category = 'all', $year = 2024) {
    $db = PesquisaDatabase::getInstance();
    $categoryWhere = '';
    $categoryParams = [];
    if ($category !== 'all') {
        $categoryWhere = 'AND c.category = ?';
        $categoryParams = [$category];
    }
    $monthlyData = [];
    $monthlyCounts = [];
    $goalScores = [];
    for ($month = 1; $month <= 12; $month++) {
        $params = array_merge([$year, $month], $categoryParams);
        $avgScore = $db->fetchColumn(
            "SELECT AVG(ac.overall_score)
             FROM analytics_cache ac
             JOIN courses c ON ac.course_id = c.id
             WHERE c.year = ? AND c.month = ? AND ac.overall_score IS NOT NULL $categoryWhere",
            $params
        );
        $responseCount = $db->fetchColumn(
            "SELECT COUNT(*)
             FROM responses r
             JOIN courses c ON r.course_id = c.id
             WHERE c.year = ? AND c.month = ? $categoryWhere",
            $params
        );
        $monthlyData[$month] = $avgScore ? (float)$avgScore : 0;
        $monthlyCounts[$month] = $responseCount ? (int)$responseCount : 0;
        if ($monthlyCounts[$month] > 0) {
            $goalScores[$month] = 70 + ($month * 2.5);
        } else {
            $goalScores[$month] = 0;
        }
    }
    $diagramData = [
        'year' => $year,
        'category' => $category === 'all' ? 'Todas as Categorias' : $category,
        'monthly_scores' => $monthlyData,
        'monthly_counts' => $monthlyCounts,
        'goal_scores' => $goalScores
    ];
    error_log("Yearly diagram data: " . json_encode($diagramData));
    $jsonFile = sys_get_temp_dir() . '/yearly_' . md5($category . $year . microtime()) . '.json';
    file_put_contents($jsonFile, json_encode($diagramData, JSON_PRETTY_PRINT));
    $safeCategory = preg_replace('/[^a-z0-9]/', '-', strtolower($category));
    $outputFile = DIAGRAMS_DIR . 'yearly_' . $safeCategory . '_' . $year . '.png';
    $possiblePaths = [
        __DIR__ . '/../generate_yearly_diagram.py',
        __DIR__ . '/generate_yearly_diagram.py',
        dirname(__DIR__) . '/pesquisa/generate_yearly_diagram.py',
        '/var/www/html/pesquisa/generate_yearly_diagram.py'
    ];
    $pythonScript = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $pythonScript = $path;
            break;
        }
    }
    if (!$pythonScript) {
        error_log("Python script not found!");
        if (file_exists($jsonFile)) unlink($jsonFile);
        return false;
    }
    $command = sprintf('python3 %s %s %s 2>&1', escapeshellarg($pythonScript), escapeshellarg($jsonFile), escapeshellarg($outputFile));
    error_log("Executing: $command");
    exec($command, $output, $returnCode);
    error_log("Python output: " . implode("\n", $output));
    if (file_exists($jsonFile)) unlink($jsonFile);
    if ($returnCode === 0 && file_exists($outputFile)) {
        return 'diagrams/' . basename($outputFile);
    }
    error_log("Yearly diagram failed: " . implode("\n", $output));
    return false;
}

/**
 * Generate Monthly Diagram
 * Shows breakdown for a specific month
 */

function generateMonthlyDiagram($category = 'all', $year = 2024, $month = 1) {
    $db = PesquisaDatabase::getInstance();
    
    // Month names
    $monthNames = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
    ];
    
    $items = [];
    
    if ($category === 'all') {
        // Show one bar per category for this month
        $categories = ['Agenda Esesp', 'Esesp na Estrada', 'EAD', 'Pós-Graduação', 'Demanda Específica'];
        
        foreach ($categories as $cat) {
            // Get average score for this category in this month
            $avgScore = $db->fetchColumn(
                "SELECT AVG(ac.overall_score)
                 FROM analytics_cache ac
                 JOIN courses c ON ac.course_id = c.id
                 WHERE c.year = ? AND c.month = ? AND c.category = ? AND ac.overall_score IS NOT NULL",
                [$year, $month, $cat]
            );
            
            // Get response count
            $responseCount = $db->fetchColumn(
                "SELECT COUNT(*)
                 FROM responses r
                 JOIN courses c ON r.course_id = c.id
                 WHERE c.year = ? AND c.month = ? AND c.category = ?",
                [$year, $month, $cat]
            );
            
            if ($avgScore && $responseCount > 0) {
                $items[] = [
                    'name' => $cat,
                    'score' => (float)$avgScore,
                    'responses' => (int)$responseCount
                ];
            }
        }
    } else {
        // Show one bar per course in this category/month
        $courses = $db->fetchAll(
            "SELECT c.id, c.name, c.docente_name, ac.overall_score, ac.response_count
             FROM courses c
             JOIN analytics_cache ac ON c.id = ac.course_id
             WHERE c.year = ? AND c.month = ? AND c.category = ? AND ac.overall_score IS NOT NULL
             ORDER BY ac.overall_score DESC",
            [$year, $month, $category]
        );
        
        foreach ($courses as $course) {
            $items[] = [
                'name' => $course['name'] . ' - ' . $course['docente_name'],
                'score' => (float)$course['overall_score'],
                'responses' => (int)$course['response_count']
            ];
        }
    }
    
    // If no data, return false
    if (empty($items)) {
        return false;
    }
    
    // Prepare data for Python
    $diagramData = [
        'year' => $year,
        'month' => $month,
        'month_name' => $monthNames[$month],
        'category' => $category === 'all' ? 'Todas as Categorias' : $category,
        'items' => $items
    ];
    
    // Save JSON
    $jsonFile = sys_get_temp_dir() . '/monthly_' . md5($category . $year . $month . microtime()) . '.json';
    file_put_contents($jsonFile, json_encode($diagramData, JSON_PRETTY_PRINT));
    
    // Output file
    $safeCategory = preg_replace('/[^a-z0-9]/', '-', strtolower($category));
    $outputFile = DIAGRAMS_DIR . 'monthly_' . $safeCategory . '_' . $year . '_' . $month . '.png';
    
    // Find Python script
    $possiblePaths = [
        __DIR__ . '/../generate_monthly_diagram.py',
        __DIR__ . '/generate_monthly_diagram.py',
        dirname(__DIR__) . '/pesquisa/generate_monthly_diagram.py',
        '/var/www/html/pesquisa/generate_monthly_diagram.py'
    ];
    
    $pythonScript = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $pythonScript = $path;
            break;
        }
    }
    
    if (!$pythonScript) {
        error_log("Monthly diagram: Python script not found!");
        if (file_exists($jsonFile)) unlink($jsonFile);
        return false;
    }
    
    // Execute Python
    $command = sprintf(
        'python3 %s %s %s 2>&1',
        escapeshellarg($pythonScript),
        escapeshellarg($jsonFile),
        escapeshellarg($outputFile)
    );
    
    exec($command, $output, $returnCode);
    
    // Cleanup
    if (file_exists($jsonFile)) {
        unlink($jsonFile);
    }
    
    if ($returnCode === 0 && file_exists($outputFile)) {
        return 'diagrams/' . basename($outputFile);
    }
    
    error_log("Monthly diagram failed: " . implode("\n", $output));
    return false;
}

/**
 * Check for courses with low scores and return alert info
 */
function checkLowScoreCourses($category = 'all', $year = 2024) {
    $db = PesquisaDatabase::getInstance();
    
    $categoryWhere = '';
    $categoryParams = [$year];
    if ($category !== 'all') {
        $categoryWhere = 'AND c.category = ?';
        $categoryParams[] = $category;
    }
    
    $lowScoreCourses = $db->fetchAll(
        "SELECT c.id, c.name, c.docente_name, c.category,
                ac.overall_score, ac.pedagogical_score, 
                ac.didactic_score, ac.infrastructure_score
         FROM courses c
         JOIN analytics_cache ac ON c.id = ac.course_id
         WHERE c.year = ? $categoryWhere
         AND (ac.overall_score < 70 OR ac.pedagogical_score < 70 
              OR ac.didactic_score < 70 OR ac.infrastructure_score < 70)
         ORDER BY ac.overall_score ASC
         LIMIT 5",
        $categoryParams
    );
    
    if (!empty($lowScoreCourses)) {
        $count = count($lowScoreCourses);
        
        $message = '<strong>⚠️ Atenção: ' . $count . ' curso(s) necessita(m) de atenção!</strong><br><br>';
        $message .= '<ul class="mb-0" style="padding-left: 1.5rem;">';
        
        foreach ($lowScoreCourses as $course) {
            $issues = [];
            
            if ($course['overall_score'] < 70) {
                $issues[] = 'Geral: ' . number_format($course['overall_score'], 1) . '%';
            }
            if ($course['pedagogical_score'] < 70) {
                $issues[] = 'Pedagógico: ' . number_format($course['pedagogical_score'], 1) . '%';
            }
            if ($course['didactic_score'] < 70) {
                $issues[] = 'Didático: ' . number_format($course['didactic_score'], 1) . '%';
            }
            if ($course['infrastructure_score'] < 70) {
                $issues[] = 'Infraestrutura: ' . number_format($course['infrastructure_score'], 1) . '%';
            }
            
            $message .= '<li>';
            $message .= '<strong>' . htmlspecialchars($course['name']) . '</strong><br>';
            $message .= '<small>' . htmlspecialchars($course['docente_name']) . ' - ' . htmlspecialchars($course['category']) . '</small><br>';
            $message .= '<small class="text-danger">' . implode(' | ', $issues) . '</small>';
            $message .= '</li>';
        }
        
        $message .= '</ul>';
        $message .= '<br><a href="analytics.php?low_scores=true&year=' . $year . '" class="btn btn-sm btn-warning">Ver Cursos com Baixo Desempenho</a>';
        
        return [
            'has_issues' => true,
            'count' => $count,
            'message' => $message
        ];
    }
    
    return ['has_issues' => false];
}

/**
 * Format diagram generation message with proper HTML
 */
function formatDiagramMessage($analytics) {
    $warnings = [];
    
    if ($analytics['overall_score'] < 70) {
        $warnings[] = 'Pontuação geral: ' . number_format($analytics['overall_score'], 1) . '%';
    }
    
    if ($analytics['pedagogical_score'] < 70) {
        $warnings[] = 'Aspecto Pedagógico: ' . number_format($analytics['pedagogical_score'], 1) . '%';
    }
    
    if ($analytics['didactic_score'] < 70) {
        $warnings[] = 'Aspecto Didático: ' . number_format($analytics['didactic_score'], 1) . '%';
    }
    
    if ($analytics['infrastructure_score'] < 70) {
        $warnings[] = 'Aspecto Infraestrutura: ' . number_format($analytics['infrastructure_score'], 1) . '%';
    }
    
    $aspects = [
        'Pedagógico' => $analytics['pedagogical_score'],
        'Didático' => $analytics['didactic_score'],
        'Infraestrutura' => $analytics['infrastructure_score']
    ];
    $lowestAspect = array_keys($aspects, min($aspects))[0];
    $lowestScore = min($aspects);
    
    if (!empty($warnings)) {
        $message = '<strong>Diagrama gerado! Atenção:</strong><br><br>';
        $message .= '<ul class="mb-2" style="padding-left: 1.5rem;">';
        foreach ($warnings as $warning) {
            $message .= '<li>⚠️ ' . $warning . ' <span class="badge bg-danger">Abaixo de 70%</span></li>';
        }
        $message .= '</ul>';
        $message .= '<hr class="my-2">';
        $message .= '<p class="mb-0">📊 Aspecto com menor nota: <strong>' . $lowestAspect . ' (' . number_format($lowestScore, 1) . '%)</strong></p>';
        
        return ['message' => $message, 'type' => 'warning'];
    } else {
        $message = '<strong>✅ Diagrama gerado com sucesso!</strong><br><br>';
        $message .= '<p class="mb-0">📊 Aspecto com menor nota: <strong>' . $lowestAspect . ' (' . number_format($lowestScore, 1) . '%)</strong></p>';
        
        return ['message' => $message, 'type' => 'success'];
    }
}

/**
 * Generate individual course diagram
 * Returns the relative path to the generated diagram
 */
function generateDiagram($courseId) {
    $db = PesquisaDatabase::getInstance();
    
    // Get course data
    $course = $db->fetchOne("SELECT * FROM courses WHERE id = ?", [$courseId]);
    if (!$course) return false;
    
    // Get analytics
    $analytics = $db->fetchOne("SELECT * FROM analytics_cache WHERE course_id = ?", [$courseId]);
    if (!$analytics) return false;
    
    // Prepare data for Python script
    $data = [
        'course_name' => $course['name'],
        'docente_name' => $course['docente_name'],
        'overall_score' => $analytics['overall_score'] ?? 0,
        'pedagogical_score' => $analytics['pedagogical_score'] ?? 0,
        'didactic_score' => $analytics['didactic_score'] ?? 0,
        'infrastructure_score' => $analytics['infrastructure_score'] ?? 0,
        'response_count' => $analytics['response_count'] ?? 0
    ];
    
    // Save data to temp JSON file
    $jsonFile = sys_get_temp_dir() . '/course_' . $courseId . '_data.json';
    file_put_contents($jsonFile, json_encode($data));
    
    // Output filename
    if (!defined('DIAGRAMS_DIR')) {
        define('DIAGRAMS_DIR', __DIR__ . '/../diagrams/');
    }
    $outputFile = DIAGRAMS_DIR . 'diagram_' . $course['token'] . '.png';
    
    // Call Python script
    $pythonScript = __DIR__ . '/../generate_diagram.py';
    if (!file_exists($pythonScript)) {
        // Try alternative paths
        $pythonScript = dirname(__DIR__) . '/generate_diagram.py';
    }
    
    $command = sprintf('python3 %s %s %s 2>&1',
        escapeshellarg($pythonScript),
        escapeshellarg($jsonFile),
        escapeshellarg($outputFile)
    );
    
    exec($command, $output, $returnCode);
    
    // Clean up temp file
    if (file_exists($jsonFile)) {
        unlink($jsonFile);
    }
    
    if ($returnCode === 0 && file_exists($outputFile)) {
        // Update course with diagram path
        $relativePath = 'diagrams/' . basename($outputFile);
        $db->update('courses', 
            ['diagram_path' => $relativePath],
            'id = ?',
            [$courseId]
        );
        
        return $relativePath;
    }
    
    error_log("Diagram generation failed for course $courseId: " . implode("\n", $output));
    return false;
}