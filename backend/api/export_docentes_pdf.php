<?php
// backend/api/export_docentes_pdf.php - FIXED TO SHOW DISCIPLINE NAMES
require_once '../classes/database.class.php';
require_once '../../vendor/autoload.php';

use TCPDF;

// Get filter parameters
$category = $_GET['category'] ?? '';
$course = $_GET['course'] ?? '';
$status = $_GET['status'] ?? '';

try {
    $conection = new Database();
    $conn = $conection->connect();
    
    // USE THE EXACT SAME LOGIC AS THE WORKING get_filtered_teachers.php
    
    // Special handling for "no-disciplines" filter
    if ($status === 'no-disciplines') {
        $sql = "
            SELECT DISTINCT
                t.id, t.name, t.email, t.phone, t.cpf, t.called_at, t.created_at,
                '' as discipline_statuses
            FROM teacher t
            LEFT JOIN teacher_disciplines td ON t.id = td.teacher_id
        ";
        
        $where = ["td.teacher_id IS NULL"];
        $params = [];
        
        if ($category !== '') {
            $sql .= " INNER JOIN teacher_activities ta ON t.id = ta.teacher_id";
            $where[] = "ta.activity_id = :category";
            $params[':category'] = $category;
        }
        
        $sql .= " WHERE " . implode(" AND ", $where);
        $sql .= " ORDER BY t.created_at ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // STEP 1: Get teachers matching filters (exactly like working API)
        $sql = "SELECT DISTINCT t.* FROM teacher t";
        $joins = [];
        $where = [];
        $params = [];
        
        if ($category !== '') {
            $joins[] = "INNER JOIN teacher_activities ta ON t.id = ta.teacher_id";
            $where[] = "ta.activity_id = :category";
            $params[':category'] = $category;
        }
        
        if ($status !== '' || $course !== '') {
            $joins[] = "INNER JOIN teacher_disciplines td ON t.id = td.teacher_id";
            
            if ($course !== '') {
                $where[] = "td.discipline_id = :course";
                $params[':course'] = $course;
            }
            
            // Use BINARY for exact comparison to avoid type coercion
            if ($status === '1') {
                $where[] = "BINARY td.enabled = '1'";
            } else if ($status === '0') {
                $where[] = "BINARY td.enabled = '0'";
            } else if ($status === 'null') {
                $where[] = "(td.enabled IS NULL OR td.enabled = '')";
            }
        }
        
        $sql .= ' ' . implode(' ', $joins);
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY t.created_at ASC';
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // STEP 2: For each teacher, get their disciplines (exactly like working API)
        $result = [];
        foreach ($teachers as $teacher) {
            $discSql = "
                SELECT 
                    d.id,
                    d.name,
                    td.enabled
                FROM teacher_disciplines td
                INNER JOIN disciplinas d ON td.discipline_id = d.id
                WHERE td.teacher_id = :teacher_id
            ";
            
            $discParams = [':teacher_id' => $teacher['id']];
            
            // Apply course filter to disciplines
            if ($course !== '') {
                $discSql .= " AND d.id = :course";
                $discParams[':course'] = $course;
            }
            
            // Apply status filter to disciplines (KEY FIX: This makes PDF match page results)
            if ($status !== '' && $status !== 'no-disciplines') {
                if ($status === '1') {
                    $discSql .= " AND BINARY td.enabled = '1'";
                } else if ($status === '0') {
                    $discSql .= " AND BINARY td.enabled = '0'";
                } else if ($status === 'null') {
                    $discSql .= " AND (td.enabled IS NULL OR BINARY td.enabled = '')";
                }
            }
            
            $discSql .= " ORDER BY d.name";
            
            $discStmt = $conn->prepare($discSql);
            $discStmt->execute($discParams);
            $disciplines = $discStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Build discipline statuses string using |~~| and |~| delimiters
            $disciplineStatuses = [];
            foreach ($disciplines as $disc) {
                $disciplineName = $disc['name'];
                
                // Determine status value (exactly like working API)
                $enabledRaw = $disc['enabled'];
                if ($enabledRaw === null || $enabledRaw === '') {
                    $statusValue = 'null';
                } elseif ($enabledRaw == 0 && !is_null($enabledRaw)) {
                    $statusValue = '0';
                } elseif ($enabledRaw == 1) {
                    $statusValue = '1';
                } else {
                    $statusValue = 'null';
                }
                
                $disciplineStatuses[] = $disc['id'] . '|~|' . $disciplineName . '|~|' . $statusValue;
            }
            
            $teacher['discipline_statuses'] = implode('|~~|', $disciplineStatuses);
            $result[] = $teacher;
        }
        
        $teachers = $result;
    }
    
    // Get filter labels for PDF header
    $filterLabels = [];
    if ($category !== '') {
        $categoryQuery = "SELECT name FROM activities WHERE id = :id";
        $categoryStmt = $conn->prepare($categoryQuery);
        $categoryStmt->execute([':id' => $category]);
        $categoryName = $categoryStmt->fetchColumn();
        if ($categoryName) {
            $filterLabels[] = "Categoria: " . $categoryName;
        }
    }
    
    if ($course !== '') {
        $courseQuery = "SELECT name FROM disciplinas WHERE id = :id";
        $courseStmt = $conn->prepare($courseQuery);
        $courseStmt->execute([':id' => $course]);
        $courseName = $courseStmt->fetchColumn();
        if ($courseName) {
            $filterLabels[] = "Curso: " . $courseName;
        }
    }
    
    if ($status !== '') {
        $statusLabel = match($status) {
            '1' => 'Apto',
            '0' => 'Inapto', 
            'null' => 'Aguardando',
            'no-disciplines' => 'Sem disciplinas',
            default => 'Todos'
        };
        $filterLabels[] = "Status: " . $statusLabel;
    }
    
    // Create PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Sistema de Docentes');
    $pdf->SetAuthor('Sistema de Docentes');
    $pdf->SetTitle('Lista de Docentes - ' . date('d/m/Y'));
    
    // Set default header data
    $headerText = 'Lista de Docentes';
    $subHeaderText = 'Gerado em: ' . date('d/m/Y H:i:s');
    if (!empty($filterLabels)) {
        $subHeaderText .= "\n" . implode(' | ', $filterLabels);
    }
    $pdf->SetHeaderData('', 0, $headerText, $subHeaderText);
    
    // Set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    
    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 9);
    
    // Create HTML content
    $html = '<style>
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #f0f0f0; font-weight: bold; padding: 6px; border: 1px solid #ddd; font-size: 10px; }
        td { padding: 5px; border: 1px solid #ddd; font-size: 9px; }
        .status-approved { background-color: #d4edda; color: #155724; padding: 2px 4px; border-radius: 3px; }
        .status-not-approved { background-color: #f8d7da; color: #721c24; padding: 2px 4px; border-radius: 3px; }
        .status-pending { background-color: #fff3cd; color: #856404; padding: 2px 4px; border-radius: 3px; }
        .discipline-item { margin-bottom: 3px; font-size: 8px; }
    </style>';
    
    $html .= '<table>
        <thead>
            <tr>
                <th width="20%">Nome</th>
                <th width="20%">Email</th>
                <th width="12%">Chamado em</th>
                <th width="12%">Data de Inscrição</th>
                <th width="36%">Cursos e Status</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($teachers as $teacher) {
        $created_at = new DateTime($teacher['created_at']);
        $dateF = $created_at->format('d/m/Y H:i');
        $called_at = $teacher['called_at'] ? (new DateTime($teacher['called_at']))->format('d/m/Y') : '---';
        
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($teacher['name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($teacher['email']) . '</td>';
        $html .= '<td>' . $called_at . '</td>';
        $html .= '<td>' . $dateF . '</td>';
        $html .= '<td>';
        
        // Parse discipline statuses with CORRECT delimiters (|~~| and |~|)
        if (!empty($teacher['discipline_statuses'])) {
            $statusPairs = explode('|~~|', $teacher['discipline_statuses']);
            foreach ($statusPairs as $pair) {
                if (!empty($pair)) {
                    $parts = explode('|~|', $pair);
                    if (count($parts) >= 3) {
                        $discId = $parts[0];
                        $discName = $parts[1];
                        $status = $parts[2];
                        
                        $statusText = match ($status) {
                            '1' => 'Apto',
                            '0' => 'Inapto',
                            default => 'Aguardando',
                        };
                        $statusClass = match ($status) {
                            '1' => 'status-approved',
                            '0' => 'status-not-approved',
                            default => 'status-pending',
                        };
                        
                        $html .= '<div class="discipline-item">';
                        $html .= '<strong>' . htmlspecialchars($discName) . ':</strong> ';
                        $html .= '<span class="' . $statusClass . '">' . $statusText . '</span>';
                        $html .= '</div>';
                    }
                }
            }
        } else {
            $html .= '<em>Sem disciplinas</em>';
        }
        
        $html .= '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    
    // Add summary information
    $html .= '<br><div style="font-size: 10px; color: #666;">';
    $html .= '<strong>Total de docentes:</strong> ' . count($teachers) . '<br>';
    $html .= '<strong>Filtros aplicados:</strong> ' . (empty($filterLabels) ? 'Nenhum' : implode(', ', $filterLabels));
    $html .= '</div>';
    
    // Output the HTML content
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Close and output PDF document
    $filename = 'docentes_' . date('Y-m-d_H-i-s') . '.pdf';
    $pdf->Output($filename, 'D'); // D = download
    
} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch(Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'PDF generation error: ' . $e->getMessage()]);
}
?>