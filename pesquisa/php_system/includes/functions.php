<?php
/**
 * includes/functions.php - Funções Auxiliares
 */

/**
 * Formatar data para exibição
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) {
        return '-';
    }
    
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Formatar data e hora
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    return formatDate($datetime, $format);
}

/**
 * Obter nome do mês
 */
function getMonthName($month) {
    $months = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
    ];
    
    return $months[(int)$month] ?? '';
}

/**
 * Obter nome do mês abreviado
 */
function getMonthShortName($month) {
    $months = [
        1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr',
        5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
        9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'
    ];
    
    return $months[(int)$month] ?? '';
}

/**
 * Gerar slug a partir de texto
 */
function generateSlug($text) {
    // Converter para minúsculas
    $text = mb_strtolower($text, 'UTF-8');
    
    // Substituir caracteres acentuados
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    
    // Remover caracteres especiais
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    
    // Substituir espaços e múltiplos hífens por um único hífen
    $text = preg_replace('/[\s-]+/', '-', $text);
    
    // Remover hífens no início e fim
    $text = trim($text, '-');
    
    return $text;
}

/**
 * Gerar token único
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Gerar token para survey
 */
function generateSurveyToken($courseName, $month, $year) {
    $slug = generateSlug($courseName);
    $monthName = generateSlug(getMonthShortName($month));
    
    return $slug . '-' . $monthName . '-' . $year;
}

/**
 * Formatar número com decimais
 */
function formatNumber($number, $decimals = 2) {
    return number_format($number, $decimals, ',', '.');
}

/**
 * Formatar porcentagem
 */
function formatPercentage($value, $decimals = 1) {
    return formatNumber($value, $decimals) . '%';
}

/**
 * Obter classe CSS para classificação
 */
function getClassificationClass($classification) {
    $classification = mb_strtolower($classification, 'UTF-8');
    
    if (strpos($classification, 'excelência') !== false) {
        return 'badge-excelencia';
    }
    if (strpos($classification, 'muito bom') !== false) {
        return 'badge-muito-bom';
    }
    if (strpos($classification, 'adequado') !== false) {
        return 'badge-adequado';
    }
    return 'badge-intervencao';
}

/**
 * Obter ícone emoji para classificação
 */
function getClassificationIcon($classification) {
    $classification = mb_strtolower($classification, 'UTF-8');
    
    if (strpos($classification, 'excelência') !== false) {
        return '🏆';
    }
    if (strpos($classification, 'muito bom') !== false) {
        return '⭐';
    }
    if (strpos($classification, 'adequado') !== false) {
        return '✓';
    }
    return '⚠️';
}

/**
 * Verificar se arquivo existe e retornar URL
 */
function getFileUrl($path) {
    if (empty($path)) {
        return null;
    }
    
    $fullPath = __DIR__ . '/../public/' . $path;
    
    if (file_exists($fullPath)) {
        return SITE_URL . '/' . $path;
    }
    
    return null;
}

/**
 * Criar diretório se não existir
 */
function ensureDirectoryExists($dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

/**
 * Remover arquivo com segurança
 */
function safeDeleteFile($path) {
    if (file_exists($path) && is_file($path)) {
        return unlink($path);
    }
    return false;
}

/**
 * Enviar resposta JSON
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Enviar erro JSON
 */
function jsonError($message, $statusCode = 400) {
    jsonResponse([
        'success' => false,
        'message' => $message
    ], $statusCode);
}

/**
 * Enviar sucesso JSON
 */
function jsonSuccess($message, $data = null) {
    $response = [
        'success' => true,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    jsonResponse($response);
}

/**
 * Redirecionar com mensagem
 */
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = [
        'text' => $message,
        'type' => $type
    ];
    header('Location: ' . $url);
    exit;
}

/**
 * Obter e limpar mensagem flash
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Exibir mensagem flash
 */
function displayFlashMessage() {
    $message = getFlashMessage();
    
    if ($message) {
        $alertClass = $message['type'] === 'success' ? 'alert-success' : 'alert-danger';
        echo sprintf(
            '<div class="alert %s alert-dismissible fade show" role="alert">%s<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>',
            $alertClass,
            htmlspecialchars($message['text'])
        );
    }
}

/**
 * Truncar texto
 */
function truncate($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Validar campo obrigatório
 */
function required($value, $fieldName = 'Campo') {
    if (empty($value)) {
        return "$fieldName é obrigatório.";
    }
    return null;
}

/**
 * Validar range numérico
 */
function validateRange($value, $min, $max, $fieldName = 'Valor') {
    if ($value < $min || $value > $max) {
        return "$fieldName deve estar entre $min e $max.";
    }
    return null;
}

/**
 * Escapar HTML
 */
function e($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Debug (apenas em modo debug)
 */
function dd($var) {
    if (DEBUG_MODE) {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
        die();
    }
}

/**
 * Gerar anos para select
 */
function getYearOptions($startYear = null, $endYear = null) {
    $startYear = $startYear ?? date('Y') - 5;
    $endYear = $endYear ?? date('Y') + 1;
    
    $years = [];
    for ($year = $endYear; $year >= $startYear; $year--) {
        $years[] = $year;
    }
    
    return $years;
}

/**
 * Verificar se é requisição AJAX
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get IP do usuário
 */
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}
