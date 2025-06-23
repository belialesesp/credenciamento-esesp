<?php
// backend/api/export_docentes_pos_pdf.php - SIMPLIFIED WORKING VERSION
require_once '../classes/database.class.php';
require_once '../../vendor/autoload.php';

use TCPDF;

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors in PDF

// Get filter parameters
$category = $_GET['category'] ?? '';
$course = $_GET['course'] ?? '';
$status = $_GET['status'] ?? '';

try {
    $conection = new Database();
    $conn = $conection->connect();
    
    // STEP 1: Get teachers using SIMPLE query (same as working API)
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
    
    // If no teachers found, still generate PDF with message
    if (empty($teachers)) {
        $teachers = [];
    }
    
    // Get filter labels
    $filterLabels = [];
    if ($category !== '') {
        try {
            $categoryQuery = "SELECT name FROM postg_activities WHERE id = :id";
            $categoryStmt = $conn->prepare($categoryQuery);
            $categoryStmt->execute([':id' => $category]);
            $categoryName = $categoryStmt->fetchColumn();
            if ($categoryName) {
                $filterLabels[] = "Categoria: " . $categoryName;
            }
        } catch (Exception $e) {
            // Fallback to regular activities table
            try {
                $categoryQuery = "SELECT name FROM activities WHERE id = :id";
                $categoryStmt = $conn->prepare($categoryQuery);
                $categoryStmt->execute([':id' => $category]);
                $categoryName = $categoryStmt->fetchColumn();
                if ($categoryName) {
                    $filterLabels[] = "Categoria: " . $categoryName;
                }
            } catch (Exception $e2) {
                $filterLabels[] = "Categoria: ID " . $category;
            }
        }
    }
    
    if ($course !== '') {
        try {
            $courseQuery = "SELECT name FROM postg_disciplinas WHERE id = :id";
            $courseStmt = $conn->prepare($courseQuery);
            $courseStmt->execute([':id' => $course]);
            $courseName = $courseStmt->fetchColumn();
            if ($courseName) {
                $filterLabels[] = "Curso: " . $courseName;
            }
        } catch (Exception $e) {
            $filterLabels[] = "Curso: ID " . $course;
        }
    }
    
    if ($status !== '') {
        $statusLabel = match($status) {
            '1' => 'Apto',
            '0' => 'Inapto', 
            'null' => 'Aguardando',
            default => $status
        };
        $filterLabels[] = "Status: " . $statusLabel;
    }
    
    // Create PDF with simple configuration
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // Basic PDF setup
    $pdf->SetCreator('Sistema de Docentes');
    $pdf->SetTitle('Lista de Docentes Pós-Graduação');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);
    
    // Simple header
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Lista de Docentes - Pós-Graduação', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Gerado em: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
    
    if (!empty($filterLabels)) {
        $pdf->Cell(0, 5, implode(' | ', $filterLabels), 0, 1, 'C');
    }
    
    $pdf->Ln(5);
    
    // Simple table
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(40, 8, 'Nome', 1, 0, 'C');
    $pdf->Cell(50, 8, 'Email', 1, 0, 'C');
    $pdf->Cell(25, 8, 'Chamado em', 1, 0, 'C');
    $pdf->Cell(25, 8, 'Inscrição', 1, 0, 'C');
    $pdf->Cell(40, 8, 'Disciplinas', 1, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 8);
    
    if (empty($teachers)) {
        $pdf->Cell(180, 10, 'Nenhum docente encontrado com os filtros aplicados', 1, 1, 'C');
    } else {
        foreach ($teachers as $teacher) {
            // STEP 2: Get disciplines for this teacher (apply same filters)
            $discSql = "
                SELECT d.name, td.enabled
                FROM postg_teacher_disciplines td
                INNER JOIN postg_disciplinas d ON td.discipline_id = d.id
                WHERE td.teacher_id = :teacher_id
            ";
            
            $discParams = [':teacher_id' => $teacher['id']];
            
            // Apply same filters to disciplines
            if ($course !== '') {
                $discSql .= " AND d.id = :course";
                $discParams[':course'] = $course;
            }
            
            // KEY FIX: Apply status filter to disciplines too
            if ($status === '1') {
                $discSql .= " AND BINARY td.enabled = '1'";
            } else if ($status === '0') {
                $discSql .= " AND BINARY td.enabled = '0'";
            } else if ($status === 'null') {
                $discSql .= " AND (td.enabled IS NULL OR td.enabled = '')";
            }
            
            $discSql .= " ORDER BY d.name LIMIT 5"; // Limit for PDF space
            
            try {
                $discStmt = $conn->prepare($discSql);
                $discStmt->execute($discParams);
                $disciplines = $discStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $disciplines = [];
            }
            
            // Format dates
            $created_at = new DateTime($teacher['created_at']);
            $dateF = $created_at->format('d/m/Y');
            $called_at = $teacher['called_at'] ? (new DateTime($teacher['called_at']))->format('d/m/Y') : '---';
            
            // Count disciplines by status
            $statusCounts = ['A' => 0, 'I' => 0, 'G' => 0];
            foreach ($disciplines as $disc) {
                if ($disc['enabled'] === '1') $statusCounts['A']++;
                elseif ($disc['enabled'] === '0') $statusCounts['I']++;
                else $statusCounts['G']++;
            }
            
            $statusSummary = "A:{$statusCounts['A']} I:{$statusCounts['I']} G:{$statusCounts['G']}";
            
            // Add row
            $pdf->Cell(40, 6, substr($teacher['name'], 0, 20), 1, 0, 'L');
            $pdf->Cell(50, 6, substr($teacher['email'], 0, 25), 1, 0, 'L');
            $pdf->Cell(25, 6, $called_at, 1, 0, 'C');
            $pdf->Cell(25, 6, $dateF, 1, 0, 'C');
            $pdf->Cell(40, 6, $statusSummary, 1, 1, 'L');
        }
    }
    
    // Summary
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'Total: ' . count($teachers) . ' docentes', 0, 1, 'L');
    
    // Output PDF
    $filename = 'docentes_pos_' . date('Y-m-d_H-i-s') . '.pdf';
    $pdf->Output($filename, 'D');
    
} catch(Exception $e) {
    // Simple error response
    header('Content-Type: application/json');
    echo json_encode(['error' => 'PDF generation failed: ' . $e->getMessage()]);
    exit;
}
?>