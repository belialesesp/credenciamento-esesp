<?php
/**
 * Moodle Integration API
 * Fetches ongoing EAD courses and their satisfaction survey responses
 */

require_once __DIR__ . '/../includes/init.php';

class MoodleIntegration {
    private $moodleUrl;
    private $moodleToken;
    private $db;
    
    public function __construct() {
        // Configure your Moodle instance
        $this->moodleUrl = MOODLE_URL ?? 'https://ead.esesp.es.gov.br';
        $this->moodleToken = MOODLE_TOKEN ?? '';
        $this->db = PesquisaDatabase::getInstance();
    }
    
    /**
     * Fetch active courses from Moodle
     */
    public function fetchActiveCourses() {
        $endpoint = '/webservice/rest/server.php';
        
        $params = [
            'wstoken' => $this->moodleToken,
            'wsfunction' => 'core_course_get_courses',
            'moodlewsrestformat' => 'json'
        ];
        
        $url = $this->moodleUrl . $endpoint . '?' . http_build_query($params);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Only for development
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Erro ao conectar com Moodle. HTTP Code: $httpCode");
        }
        
        $courses = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Erro ao processar resposta do Moodle");
        }
        
        return $this->filterOngoingCourses($courses);
    }
    
    /**
     * Filter only ongoing courses (currently active)
     */
    private function filterOngoingCourses($courses) {
        $currentTimestamp = time();
        $ongoingCourses = [];
        
        foreach ($courses as $course) {
            // Skip site course (id = 1) and hidden courses
            if ($course['id'] == 1 || !empty($course['hidden'])) {
                continue;
            }
            
            // Check if course is currently active
            $startdate = $course['startdate'] ?? 0;
            $enddate = $course['enddate'] ?? 0;
            
            // Course is ongoing if:
            // - Has started (startdate <= now)
            // - Has not ended (enddate = 0 or enddate > now)
            if ($startdate <= $currentTimestamp && 
                ($enddate == 0 || $enddate > $currentTimestamp)) {
                $ongoingCourses[] = [
                    'moodle_id' => $course['id'],
                    'name' => $course['fullname'],
                    'shortname' => $course['shortname'],
                    'category' => $course['categoryid'] ?? null,
                    'startdate' => $startdate,
                    'enddate' => $enddate,
                    'summary' => strip_tags($course['summary'] ?? ''),
                ];
            }
        }
        
        return $ongoingCourses;
    }
    
    /**
     * Fetch course enrollments (to get docentes)
     */
    public function fetchCourseTeachers($moodleCourseId) {
        $endpoint = '/webservice/rest/server.php';
        
        $params = [
            'wstoken' => $this->moodleToken,
            'wsfunction' => 'core_enrol_get_enrolled_users',
            'moodlewsrestformat' => 'json',
            'courseid' => $moodleCourseId
        ];
        
        $url = $this->moodleUrl . $endpoint . '?' . http_build_query($params);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $users = json_decode($response, true);
        
        // Filter only teachers (typically role id 3 or 4 in Moodle)
        $teachers = [];
        foreach ($users as $user) {
            if (!empty($user['roles'])) {
                foreach ($user['roles'] as $role) {
                    if (in_array($role['roleid'], [3, 4])) { // Teacher or Non-editing teacher
                        $teachers[] = [
                            'name' => $user['fullname'],
                            'email' => $user['email'] ?? '',
                            'moodle_id' => $user['id']
                        ];
                        break;
                    }
                }
            }
        }
        
        return $teachers;
    }
    
    /**
     * Sync Moodle courses to pesquisa database
     */
    public function syncCoursesToPesquisa($userCPF) {
        try {
            $moodleCourses = $this->fetchActiveCourses();
            $currentMonth = (int)date('n');
            $currentYear = (int)date('Y');
            
            $this->db->beginTransaction();
            $syncedCount = 0;
            $errors = [];
            
            foreach ($moodleCourses as $moodleCourse) {
                try {
                    // Check if already synced
                    $existing = $this->db->fetchOne(
                        "SELECT id FROM courses WHERE moodle_course_id = ?",
                        [$moodleCourse['moodle_id']]
                    );
                    
                    if ($existing) {
                        continue; // Already synced
                    }
                    
                    // Get teachers
                    $teachers = $this->fetchCourseTeachers($moodleCourse['moodle_id']);
                    $docenteName = !empty($teachers) ? $teachers[0]['name'] : 'Docente EAD';
                    
                    // Generate token
                    $token = 'EAD-' . $moodleCourse['moodle_id'] . '-' . $currentMonth . $currentYear;
                    
                    // Insert course
                    $courseId = $this->db->insert('courses', [
                        'token' => $token,
                        'name' => $moodleCourse['name'],
                        'docente_name' => $docenteName,
                        'docente_cpf' => null, // Will need to be matched later
                        'category' => 'EAD',
                        'month' => $currentMonth,
                        'year' => $currentYear,
                        'description' => substr($moodleCourse['summary'], 0, 500),
                        'moodle_course_id' => $moodleCourse['moodle_id'],
                        'max_responses' => null,
                        'is_active' => true,
                        'created_by' => $userCPF,
                        'sync_source' => 'moodle'
                    ]);
                    
                    // Initialize analytics
                    $this->db->insert('analytics_cache', [
                        'course_id' => $courseId,
                        'response_count' => 0
                    ]);
                    
                    // Generate QR Code
                    generateQRCode($courseId, $token);
                    
                    $syncedCount++;
                    
                } catch (Exception $e) {
                    $errors[] = "Erro no curso '{$moodleCourse['name']}': " . $e->getMessage();
                }
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'synced' => $syncedCount,
                'total' => count($moodleCourses),
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Export survey responses to Moodle format (CSV)
     */
    public function exportToMoodleFormat($courseId) {
        $course = $this->db->fetchOne("SELECT * FROM courses WHERE id = ?", [$courseId]);
        
        if (!$course || $course['category'] !== 'EAD') {
            throw new Exception("Curso não é EAD ou não existe");
        }
        
        // Get responses
        $responses = $this->db->fetchAll("
            SELECT r.*, q.question_text, q.category_id
            FROM responses r
            JOIN questions q ON r.question_id = q.id
            WHERE r.course_id = ?
            ORDER BY r.created_at
        ", [$courseId]);
        
        // Format as CSV
        $csv = "Respondente,Data,Pergunta,Categoria,Resposta,Score\n";
        
        $responseGroups = [];
        foreach ($responses as $response) {
            $key = $response['respondent_id'] . '_' . date('Y-m-d', strtotime($response['created_at']));
            $responseGroups[$key][] = $response;
        }
        
        foreach ($responseGroups as $key => $group) {
            foreach ($group as $response) {
                $csv .= sprintf(
                    '"%s","%s","%s","%s","%s","%s"' . "\n",
                    $response['respondent_id'] ?? 'Anônimo',
                    date('Y-m-d H:i:s', strtotime($response['created_at'])),
                    str_replace('"', '""', $response['question_text']),
                    $response['category_id'],
                    str_replace('"', '""', $response['response_text'] ?? ''),
                    $response['score'] ?? ''
                );
            }
        }
        
        return $csv;
    }
}

// API Endpoint Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $action = $_POST['action'] ?? '';
        $userCPF = getCurrentUserCPF();
        
        if (!isPesquisaAdmin()) {
            throw new Exception("Acesso negado");
        }
        
        $moodle = new MoodleIntegration();
        
        switch ($action) {
            case 'sync':
                $result = $moodle->syncCoursesToPesquisa($userCPF);
                echo json_encode($result);
                break;
                
            case 'list':
                $courses = $moodle->fetchActiveCourses();
                echo json_encode([
                    'success' => true,
                    'courses' => $courses
                ]);
                break;
                
            case 'export':
                $courseId = (int)$_POST['course_id'];
                $csv = $moodle->exportToMoodleFormat($courseId);
                
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="moodle_export_' . $courseId . '.csv"');
                echo $csv;
                exit;
                
            default:
                throw new Exception("Ação inválida");
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>