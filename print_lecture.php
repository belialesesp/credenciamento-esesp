<?php
require __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;

// --- DB CONFIG ---
$host = 'localhost';
$db = 'credenciamento_esesp';
$user = 'root';
$pass = '';

// --- CONNECT ---
$pdo = new PDO(
    "mysql:host=$host;dbname=$db;charset=utf8mb4",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// --- FETCH DATA ---
$stmt = $pdo->query("
    SELECT 
        l.name AS palestra,
        l.details,
        t.name  AS docente,
        pt.name AS docente_pos
    FROM lecture l
    LEFT JOIN teacher t 
        ON t.id = l.teacher_id
    LEFT JOIN postg_teacher pt 
        ON pt.id = l.postg_teacher_id
    ORDER BY l.id
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($rows);


// --- BUILD HTML ---
$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    h1 { text-align: center; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #333; padding: 6px; }
    th { background: #eee; }
</style>
</head>
<body>
<h1>Palestras</h1>
<div class="meta">Total: ' . $total . '</div>
<table>
<tr>';

if (!empty($rows)) {
    // Table headers
    foreach (array_keys($rows[0]) as $col) {
        $html .= '<th>' . htmlspecialchars($col) . '</th>';
    }
    $html .= '</tr>';

    // Rows
    foreach ($rows as $r) {
        $html .= '<tr>';
        foreach ($r as $v) {
            $html .= '<td>' . htmlspecialchars((string)$v) . '</td>';
        }
        $html .= '</tr>';
    }
} else {
    $html .= '<th>Não encontrado</th></tr>';
}

$html .= '
</table>
</body>
</html>';

// --- GENERATE PDF ---
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Stream to browser
$dompdf->stream('lecture.pdf', ['Attachment' => false]);
