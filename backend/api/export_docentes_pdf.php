<?php
// backend/api/export_docentes_pdf.php - FIXED VERSION
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
    
    // Use the same query logic as get_filtered_teachers.php with GROUP_CONCAT
    $sql = "SELECT DISTINCT 
                t.id,
                t.name,
                t.email,
                t.created_at,
                t.called_at,
                GROUP_CONCAT(
                    DISTINCT CONCAT(
                        d.id, '|~|', 
                        d.name, '|~|', 
                        COALESCE(td.enabled, 'null')
                    ) SEPARATOR '|~~|'
                ) as discipline_statuses
            FROM teacher t";
    
    $joins = [];
    $where = [];
    $params = [];
    
    // Always join with disciplines to get status info
    $joins[] = "LEFT JOIN teacher_disciplines td ON t.id = td.teacher_id";
    $joins[] = "LEFT JOIN disciplinas d ON td.discipline_id = d.id";
    
    if ($category !== '') {
        $joins[] = "INNER JOIN teacher_activities ta ON t.id = ta.teacher_id";
        $where[] = "ta.activity_id = :category";
        $params[':category'] = $category;
    }
    
    if ($course !== '') {
        $where[] = "td.discipline_id = :course";
        $params[':course'] = $course;
    }
    
    // Apply joins
    $sql .= ' ' . implode(' ', array_unique($joins));
    
    // Apply WHERE conditions
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    
    // Group by teacher
    $sql .= ' GROUP BY t.id';
    
    // Handle status filtering with HAVING clause
    if ($status !== '' && $status !== null) {
        if ($status === '1') {
            $sql .= " HAVING SUM(CASE WHEN td.enabled = '1' THEN 1 ELSE 0 END) > 0";
        } else if ($status === '0') {
            $sql .= " HAVING SUM(CASE WHEN td.enabled = '0' THEN 1 ELSE 0 END) > 0";
        } else if ($status === 'null') {
            $sql .= " HAVING SUM(CASE WHEN (td.enabled IS NULL OR td.enabled = '') THEN 1 ELSE 0 END) > 0";
        } else if ($status === 'no-disciplines') {
            $sql .= " HAVING COUNT(td.discipline_id) = 0";
        }
    }
    
    $sql .= ' ORDER BY t.created_at ASC';
    
    // Add LIMIT to prevent memory issues when no filters
    if (empty($category) && empty($course) && empty($status)) {
        $sql .= ' LIMIT 500'; // Limit to 500 records when no filters
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    
    // Add limit warning
    if (empty($category) && empty($course) && empty($status)) {
        $filterLabels[] = "Limitado a 500 registros";
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
        .discipline-item { margin-bottom: 2px; font-size: 8px; }
    </style>';
    
    $html .= '<table>
        <thead>
            <tr>
                <th width="22%">Nome</th>
                <th width="22%">Email</th>
                <th width="12%">Chamado em</th>
                <th width="14%">Data de Inscrição</th>
                <th width="30%">Cursos e Status</th>
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
        
        // Parse discipline statuses with CORRECT delimiters for docentes.php
        if (!empty($teacher['discipline_statuses'])) {
            $statusPairs = explode('|~~|', $teacher['discipline_statuses']); // Use |~~| for records
            foreach ($statusPairs as $pair) {
                if (!empty($pair)) {
                    $parts = explode('|~|', $pair); // Use |~| for fields
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