<?php
/**
 * Diagnostic: Check yearly diagram data
 * Run this to see what data is available
 */

require_once __DIR__ . '/includes/init.php';

$db = PesquisaDatabase::getInstance();

echo "<h1>Diagnóstico do Diagrama Anual</h1>";
echo "<pre>";

// Check courses by year and month
echo "\n=== CURSOS POR MÊS (2024) ===\n";
$courses = $db->fetchAll("
    SELECT 
        c.month,
        c.category,
        COUNT(c.id) as course_count,
        COUNT(DISTINCT r.id) as response_count
    FROM courses c
    LEFT JOIN responses r ON r.course_id = c.id
    WHERE c.year = 2024
    GROUP BY c.month, c.category
    ORDER BY c.month, c.category
");

foreach ($courses as $row) {
    echo "Mês {$row['month']} - {$row['category']}: {$row['course_count']} cursos, {$row['response_count']} respostas\n";
}

// Check analytics_cache
echo "\n=== ANALYTICS CACHE (2024) ===\n";
$analytics = $db->fetchAll("
    SELECT 
        c.month,
        c.category,
        COUNT(ac.id) as cached_count,
        AVG(ac.overall_score) as avg_score
    FROM analytics_cache ac
    JOIN courses c ON ac.course_id = c.id
    WHERE c.year = 2024 AND ac.overall_score IS NOT NULL
    GROUP BY c.month, c.category
    ORDER BY c.month, c.category
");

foreach ($analytics as $row) {
    echo "Mês {$row['month']} - {$row['category']}: Cache={$row['cached_count']}, Média={$row['avg_score']}%\n";
}

// Test the exact query used by generateYearlyDiagram
echo "\n=== TESTE DA QUERY EXATA ===\n";
for ($month = 1; $month <= 12; $month++) {
    $result = $db->fetchOne("
        SELECT 
            AVG(ac.overall_score) as avg_score, 
            COUNT(DISTINCT c.id) as course_count,
            COUNT(DISTINCT r.id) as response_count
        FROM analytics_cache ac
        JOIN courses c ON ac.course_id = c.id
        LEFT JOIN responses r ON r.course_id = c.id
        WHERE c.year = 2024 AND c.month = ? AND ac.overall_score IS NOT NULL
    ", [$month]);
    
    if ($result && $result['course_count'] > 0) {
        echo "Mês $month: {$result['course_count']} cursos, Score={$result['avg_score']}%, Respostas={$result['response_count']}\n";
    } else {
        echo "Mês $month: SEM DADOS\n";
    }
}

// Check if dummy data was generated
echo "\n=== RESUMO GERAL ===\n";
$total_courses = $db->fetchColumn("SELECT COUNT(*) FROM courses WHERE year = 2024");
$total_responses = $db->fetchColumn("SELECT COUNT(*) FROM responses r JOIN courses c ON r.course_id = c.id WHERE c.year = 2024");
$total_cached = $db->fetchColumn("SELECT COUNT(*) FROM analytics_cache ac JOIN courses c ON ac.course_id = c.id WHERE c.year = 2024");

echo "Total de Cursos 2024: $total_courses\n";
echo "Total de Respostas 2024: $total_responses\n";
echo "Total no Cache 2024: $total_cached\n";

// Sample courses
echo "\n=== EXEMPLO DE CURSOS ===\n";
$samples = $db->fetchAll("
    SELECT c.id, c.name, c.month, c.year, c.category, ac.overall_score
    FROM courses c
    LEFT JOIN analytics_cache ac ON c.id = ac.course_id
    WHERE c.year = 2024
    LIMIT 10
");

foreach ($samples as $sample) {
    echo "ID {$sample['id']}: {$sample['name']} - Mês {$sample['month']}/{$sample['year']} - Score: {$sample['overall_score']}%\n";
}

echo "</pre>";
?>