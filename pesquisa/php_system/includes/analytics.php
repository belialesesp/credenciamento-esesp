<?php
/**
 * includes/analytics.php - Enhanced Analytics with Filtering and QR Code
 */

require_once __DIR__ . '/db.php';

class Analytics {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Calcula indicadores para um curso
     */
    public function calculateIndicators($courseId) {
        // Buscar respostas
        $sql = "SELECT * FROM responses WHERE course_id = ?";
        $responses = $this->db->fetchAll($sql, [$courseId]);
        
        if (empty($responses)) {
            return null;
        }
        
        $count = count($responses);
        
        // Calcular médias por categoria
        $pedagogicos = $this->calculateCategoryAverage($responses, [
            'q1_organizacao_curricular',
            'q2_coerencia_pedagogica',
            'q3_metodologia_ensino',
            'q4_carga_horaria',
            'q5_material_pedagogico'
        ]);
        
        $didaticos = $this->calculateCategoryAverage($responses, [
            'q6_dominio_conteudo',
            'q7_disponibilidade_escuta',
            'q8_recursos_didaticos',
            'q9_participacao_ativa',
            'q10_aplicabilidade'
        ]);
        
        $infraestrutura = $this->calculateCategoryAverage($responses, [
            'q11_conforto_sala',
            'q12_acessibilidade',
            'q13_infraestrutura_tech',
            'q14_manutencao_predial',
            'q15_limpeza_organizacao'
        ]);
        
        // Calcular pontuação geral (ponderada)
        $overallScore = ($pedagogicos * 0.40) + ($didaticos * 0.35) + ($infraestrutura * 0.25);
        
        // Classificação
        $classification = $this->getClassification($overallScore);
        
        // Salvar no cache
        $this->saveToCache($courseId, [
            'overall_score' => $overallScore,
            'pedagogicos_score' => $pedagogicos,
            'didaticos_score' => $didaticos,
            'infraestrutura_score' => $infraestrutura,
            'classification' => $classification,
            'response_count' => $count
        ]);
        
        return [
            'overall_score' => $overallScore,
            'pedagogicos_score' => $pedagogicos,
            'didaticos_score' => $didaticos,
            'infraestrutura_score' => $infraestrutura,
            'classification' => $classification,
            'response_count' => $count
        ];
    }
    
    private function calculateCategoryAverage($responses, $questions) {
        $total = 0;
        $count = count($responses) * count($questions);
        
        foreach ($responses as $response) {
            foreach ($questions as $question) {
                $total += $response[$question];
            }
        }
        
        // Converter para porcentagem (escala 1-5 -> 0-100%)
        $average = ($total / $count);
        return ($average / 5) * 100;
    }
    
    private function getClassification($score) {
        if ($score >= 90) return 'Excelência institucional';
        if ($score >= 75) return 'Muito bom';
        if ($score >= 60) return 'Adequado';
        return 'Necessita intervenção';
    }
    
    private function saveToCache($courseId, $data) {
        $sql = "INSERT INTO analytics_cache 
                (course_id, overall_score, pedagogicos_score, didaticos_score, 
                 infraestrutura_score, classification, response_count)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                overall_score = VALUES(overall_score),
                pedagogicos_score = VALUES(pedagogicos_score),
                didaticos_score = VALUES(didaticos_score),
                infraestrutura_score = VALUES(infraestrutura_score),
                classification = VALUES(classification),
                response_count = VALUES(response_count)";
        
        $this->db->query($sql, [
            $courseId,
            $data['overall_score'],
            $data['pedagogicos_score'],
            $data['didaticos_score'],
            $data['infraestrutura_score'],
            $data['classification'],
            $data['response_count']
        ]);
    }
    
    /**
     * Get filtered analytics based on criteria
     */
    public function getFilteredAnalytics($filter, $options = []) {
        $category = $options['category'] ?? null;
        $month = $options['month'] ?? null;
        $year = $options['year'] ?? date('Y');
        $docente = $options['docente'] ?? null;
        
        $sql = "SELECT 
                    c.id,
                    c.name,
                    c.docente_name,
                    c.month,
                    c.year,
                    c.survey_token,
                    cat.name as category_name,
                    ac.overall_score,
                    ac.pedagogicos_score,
                    ac.didaticos_score,
                    ac.infraestrutura_score,
                    ac.classification,
                    ac.response_count,
                    ac.diagram_path
                FROM courses c
                LEFT JOIN categories cat ON c.category_id = cat.id
                LEFT JOIN analytics_cache ac ON c.id = ac.course_id
                WHERE c.is_active = 1";
        
        $params = [];
        
        // Apply filters
        if ($year) {
            $sql .= " AND c.year = ?";
            $params[] = $year;
        }
        
        if ($month) {
            $sql .= " AND c.month = ?";
            $params[] = $month;
        }
        
        if ($category) {
            $sql .= " AND c.category_id = ?";
            $params[] = $category;
        }
        
        if ($docente) {
            $sql .= " AND c.docente_name = ?";
            $params[] = $docente;
        }
        
        // Only show courses with responses
        if ($filter !== 'all') {
            $sql .= " AND ac.response_count > 0";
        }
        
        $sql .= " ORDER BY c.year DESC, c.month DESC, c.name ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Generate diagram using Python script
     */
    public function generateDiagram($courseId) {
        $course = $this->db->fetchOne("SELECT * FROM courses WHERE id = ?", [$courseId]);
        
        if (!$course) {
            return false;
        }
        
        // Check if has responses
        $responseCount = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM responses WHERE course_id = ?",
            [$courseId]
        )['count'];
        
        if ($responseCount == 0) {
            return false;
        }
        
        // Calculate indicators if not cached
        $analytics = $this->db->fetchOne(
            "SELECT * FROM analytics_cache WHERE course_id = ?",
            [$courseId]
        );
        
        if (!$analytics) {
            $analytics = $this->calculateIndicators($courseId);
            if (!$analytics) {
                return false;
            }
        }
        
        // Prepare data for Python
        $data = $this->prepareDataForPython($courseId);
        
        // Export to temp JSON
        $tempFile = sys_get_temp_dir() . '/course_' . $courseId . '_' . time() . '.json';
        file_put_contents($tempFile, json_encode($data, JSON_UNESCAPED_UNICODE));
        
        // Output path
        $outputDir = __DIR__ . '/../output/diagrams/';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        $outputPath = $outputDir . $course['survey_token'] . '.png';
        
        // Execute Python script
        $scriptPath = __DIR__ . '/../python/generate_diagrams.py';
        $pythonPath = PYTHON_PATH;
        
        $command = sprintf(
            '%s %s %s %s 2>&1',
            escapeshellarg($pythonPath),
            escapeshellarg($scriptPath),
            escapeshellarg($tempFile),
            escapeshellarg($outputPath)
        );
        
        $output = shell_exec($command);
        error_log("Python output: " . $output);
        
        // Clean temp file
        unlink($tempFile);
        
        // Check if file was created
        if (file_exists($outputPath)) {
            // Update cache with diagram path (relative to public)
            $relativePath = 'output/diagrams/' . basename($outputPath);
            $this->db->query(
                "UPDATE analytics_cache SET diagram_path = ? WHERE course_id = ?",
                [$relativePath, $courseId]
            );
            return true;
        }
        
        error_log("Diagram generation failed for course $courseId");
        return false;
    }
    
    private function prepareDataForPython($courseId) {
        $course = $this->db->fetchOne(
            "SELECT c.*, cat.name as category_name 
             FROM courses c 
             LEFT JOIN categories cat ON c.category_id = cat.id 
             WHERE c.id = ?",
            [$courseId]
        );
        
        $analytics = $this->db->fetchOne(
            "SELECT * FROM analytics_cache WHERE course_id = ?",
            [$courseId]
        );
        
        return [
            'course' => $course['name'],
            'docente' => $course['docente_name'],
            'category' => $course['category_name'],
            'month' => $course['month'],
            'year' => $course['year'],
            'results' => [
                'overall_score' => floatval($analytics['overall_score']),
                'classification' => $analytics['classification'],
                'details' => [
                    'pedagogicos' => [
                        'score' => floatval($analytics['pedagogicos_score']),
                        'weight' => 40
                    ],
                    'didaticos' => [
                        'score' => floatval($analytics['didaticos_score']),
                        'weight' => 35
                    ],
                    'infraestrutura' => [
                        'score' => floatval($analytics['infraestrutura_score']),
                        'weight' => 25
                    ]
                ]
            ],
            'response_count' => intval($analytics['response_count']),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Generate QR Code for survey
     */
    public function generateQRCode($courseId) {
        $course = $this->db->fetchOne("SELECT * FROM courses WHERE id = ?", [$courseId]);
        
        if (!$course) {
            return null;
        }
        
        $surveyUrl = SITE_URL . '/survey/' . $course['survey_token'];
        
        // Use Google Charts API for QR code generation (free, no dependencies)
        $qrCodeUrl = sprintf(
            'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=%s&choe=UTF-8',
            urlencode($surveyUrl)
        );
        
        return [
            'url' => $surveyUrl,
            'qr_code_url' => $qrCodeUrl,
            'token' => $course['survey_token']
        ];
    }
    
    /**
     * Get monthly summary for a category
     */
    public function getMonthlySummary($year, $categoryId = null) {
        $sql = "SELECT 
                    c.month,
                    COUNT(DISTINCT c.id) as course_count,
                    COUNT(r.id) as response_count,
                    AVG(ac.overall_score) as avg_score
                FROM courses c
                LEFT JOIN responses r ON c.id = r.course_id
                LEFT JOIN analytics_cache ac ON c.id = ac.course_id
                WHERE c.year = ? AND c.is_active = 1";
        
        $params = [$year];
        
        if ($categoryId) {
            $sql .= " AND c.category_id = ?";
            $params[] = $categoryId;
        }
        
        $sql .= " GROUP BY c.month ORDER BY c.month";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get annual summary by category
     */
    public function getAnnualSummaryByCategory($year) {
        $sql = "SELECT 
                    cat.name as category_name,
                    COUNT(DISTINCT c.id) as course_count,
                    COUNT(r.id) as response_count,
                    AVG(ac.overall_score) as avg_score,
                    AVG(ac.pedagogicos_score) as avg_pedagogicos,
                    AVG(ac.didaticos_score) as avg_didaticos,
                    AVG(ac.infraestrutura_score) as avg_infraestrutura
                FROM categories cat
                LEFT JOIN courses c ON cat.id = c.category_id AND c.year = ? AND c.is_active = 1
                LEFT JOIN responses r ON c.id = r.course_id
                LEFT JOIN analytics_cache ac ON c.id = ac.course_id
                GROUP BY cat.id, cat.name
                ORDER BY cat.name";
        
        return $this->db->fetchAll($sql, [$year]);
    }
    
    /**
     * Get top performing courses
     */
    public function getTopCourses($limit = 10, $year = null) {
        $sql = "SELECT 
                    c.id,
                    c.name,
                    c.docente_name,
                    cat.name as category_name,
                    c.month,
                    c.year,
                    ac.overall_score,
                    ac.classification,
                    ac.response_count
                FROM courses c
                LEFT JOIN categories cat ON c.category_id = cat.id
                LEFT JOIN analytics_cache ac ON c.id = ac.course_id
                WHERE c.is_active = 1 AND ac.overall_score IS NOT NULL";
        
        $params = [];
        
        if ($year) {
            $sql .= " AND c.year = ?";
            $params[] = $year;
        }
        
        $sql .= " ORDER BY ac.overall_score DESC LIMIT ?";
        $params[] = $limit;
        
        return $this->db->fetchAll($sql, $params);
    }
}
