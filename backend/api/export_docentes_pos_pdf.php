<?php
// backend/api/export_docentes_pos_pdf.php - FIXED VERSION
require_once '../classes/database.class.php';
require_once '../../vendor/autoload.php';

// Fix: Use proper TCPDF namespace or direct class reference
use \TCPDF;

// Get filter parameters
$category = $_GET['category'] ?? '';
$course = $_GET['course'] ?? '';
$status = $_GET['status'] ?? '';

try {
    $conection = new Database();
    $conn = $conection->connect();
    
    // Special handling for "no-disciplines" filter
    if ($status === 'no-disciplines') {
        $sql = "
            SELECT DISTINCT
                t.id, t.name, t.email, t.phone, t.cpf, COALESCE(DATE_FORMAT(td.called_at, '%d/%m/%Y'), '') as called_at, t.created_at,
                '' as discipline_statuses
            FROM postg_teacher t
            LEFT JOIN postg_teacher_disciplines td ON t.id = td.teacher_id
        ";
        
        $where = ["td.teacher_id IS NULL"];
        $params = [];
        
        if ($category !== '') {
            $sql .= " INNER JOIN postg_teacher_activities ta ON t.id = ta.teacher_id";
            $where[] = "ta.activity_id = :category";
            $params[':category'] = $category;
        }
        
        $sql .= " WHERE " . implode(" AND ", $where);
        $sql .= " ORDER BY t.created_at ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // STEP 1: Get teachers matching filters
        $sql = "SELECT DISTINCT t.* FROM postg_teacher t";
        $joins = [];
        $where = [];
        $params = [];
        
        if ($category !== '') {
            $joins[] = "INNER JOIN postg_teacher_activities ta ON t.id = ta.teacher_id";
            $where[] = "ta.activity_id = :category";
            $params[':category'] = $category;
        }
        
        if ($status !== '' || $course !== '') {
            $joins[] = "INNER JOIN postg_teacher_disciplines td_filter ON t.id = td_filter.teacher_id";
            
            if ($course !== '') {
                $where[] = "td_filter.discipline_id = :course";
                $params[':course'] = $course;
            }
            
            if ($status === '1') {
                $where[] = "BINARY td_filter.enabled = '1'";
            } else if ($status === '0') {
                $where[] = "BINARY td_filter.enabled = '0'";
            } else if ($status === 'null') {
                $where[] = "(td_filter.enabled IS NULL OR BINARY td_filter.enabled = '')";
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
        
        // STEP 2: For each teacher, get their disciplines
        $result = [];
        foreach ($teachers as $teacher) {
            $discSql = "
                SELECT 
                    d.id,
                    d.name,
                    td.enabled
                FROM postg_teacher_disciplines td
                INNER JOIN postg_disciplinas d ON td.discipline_id = d.id
                WHERE td.teacher_id = :teacher_id
            ";
            
            $discParams = [':teacher_id' => $teacher['id']];
            
            // Apply course filter to disciplines
            if ($course !== '') {
                $discSql .= " AND d.id = :course";
                $discParams[':course'] = $course;
            }
            
            // Apply status filter to disciplines
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
            
            // Build discipline statuses string using || and : delimiters
            $disciplineStatuses = [];
            foreach ($disciplines as $disc) {
                $disciplineName = $disc['name'];
                
                // Determine status value
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
                
                $disciplineStatuses[] = $disc['id'] . ':' . $disciplineName . ':' . $statusValue;
            }
            
            $teacher['discipline_statuses'] = implode('||', $disciplineStatuses);
            $result[] = $teacher;
        }
        
        $teachers = $result;
    }
    
    // Get filter labels for PDF header
    $filterLabels = [];
    if ($category !== '') {
        $categoryQuery = "SELECT name FROM postg_activities WHERE id = :id";
        $categoryStmt = $conn->prepare($categoryQuery);
        $categoryStmt->execute([':id' => $category]);
        $categoryName = $categoryStmt->fetchColumn();
        if ($categoryName) {
            $filterLabels[] = "Categoria: " . $categoryName;
        }
    }
    
    if ($course !== '') {
        $courseQuery = "SELECT name FROM postg_disciplinas WHERE id = :id";
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
    $pdf->SetTitle('Lista de Docentes Pós-Graduação - ' . date('d/m/Y'));
    
    // Set default header data
    $headerText = 'Lista de Docentes - Pós-Graduação';
    $subHeaderText = 'Gerado em: ' . date('d/m/Y H:i:s');
    if (!empty($filterLabels)) {
        $subHeaderText .= "\n" . implode(' | ', $filterLabels);
    }
    $pdf->SetHeaderData('', 0, $headerText, $subHeaderText);
    
    // Set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', 10));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', 8));
    
    // Set margins
    $pdf->SetMargins(15, 25, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Add a page
    $pdf->AddPage('P', 'A4');
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Create HTML content with TCPDF-compatible table
    $html = '<table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr bgcolor="#e9ecef">
                <td width="30%"><b>Nome</b></td>
                <td width="15%" align="center"><b>Chamado em</b></td>
                <td width="20%" align="center"><b>Data de Inscrição</b></td>
                <td width="35%"><b>Cursos e Status</b></td>
            </tr>
        </thead>
        <tbody>';
    
    // Generate table rows
    foreach ($teachers as $teacher) {
        $created_at = new DateTime($teacher['created_at']);
        $dateF = $created_at->format('d/m/Y H:i');
        $called_at = $teacher['called_at'] ? (new DateTime($teacher['called_at']))->format('d/m/Y') : '---';
        
        $html .= '<tr>';
        $html .= '<td width="30%">' . htmlspecialchars($teacher['name']) . '</td>';
        $html .= '<td width="15%" align="center">' . $called_at . '</td>';
        $html .= '<td width="20%" align="center">' . $dateF . '</td>';
        $html .= '<td width="35%">';
        
        // Parse discipline statuses with CORRECT delimiters for postg (|| and :)
        if (!empty($teacher['discipline_statuses'])) {
            $statusPairs = explode('||', $teacher['discipline_statuses']);
            foreach ($statusPairs as $pair) {
                if (!empty($pair)) {
                    $parts = explode(':', $pair);
                    if (count($parts) >= 3) {
                        $discId = $parts[0];
                        $discName = $parts[1];
                        $status = $parts[2];
                        
                        // Handle discipline names that might contain colons
                        if (count($parts) > 3) {
                            $discName = implode(':', array_slice($parts, 1, -1));
                            $status = $parts[count($parts) - 1];
                        }
                        
                        $statusText = match ($status) {
                            '1' => 'Apto',
                            '0' => 'Inapto',
                            default => 'Aguardando',
                        };
                        $statusBgColor = match ($status) {
                            '1' => '#d4edda',
                            '0' => '#f8d7da',
                            default => '#fff3cd',
                        };
                        $statusTextColor = match ($status) {
                            '1' => '#155724',
                            '0' => '#721c24',
                            default => '#856404',
                        };
                        
                        $statusBgColor = match ($status) {
                            '1' => '#d4edda',
                            '0' => '#f8d7da',
                            default => '#fff3cd',
                        };
                        $statusTextColor = match ($status) {
                            '1' => '#155724',
                            '0' => '#721c24',
                            default => '#856404',
                        };
                        
                        $html .= '<b>' . htmlspecialchars($discName) . ':</b> ';
                        $html .= '<font color="' . $statusTextColor . '" bgcolor="' . $statusBgColor . '">' . $statusText . '</font><br/>';
                    }
                }
            }
        } else {
            $html .= '<i><font color="#6c757d">Sem disciplinas</font></i>';
        }
        
        $html .= '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    
    // Add summary information
    $html .= '<br/><br/>';
    $html .= '<table border="0" cellpadding="5">
        <tr>
            <td><b>Total de docentes:</b> ' . count($teachers) . '</td>
            <td><b>Filtros aplicados:</b> ' . (empty($filterLabels) ? 'Nenhum' : implode(', ', $filterLabels)) . '</td>
        </tr>
    </table>';
    
    // Output the HTML content
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Close and output PDF document
    $filename = 'docentes_pos_' . date('Y-m-d_H-i-s') . '.pdf';
    $pdf->Output($filename, 'D'); // D = download
    
} catch(PDOException $e) {
    // Important: Set proper content type for errors
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
} catch(Exception $e) {
    // Important: Set proper content type for errors
    header('Content-Type: application/json');
    echo json_encode(['error' => 'PDF generation error: ' . $e->getMessage()]);
    exit;
}
?>