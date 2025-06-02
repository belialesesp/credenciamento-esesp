<?php

require_once("../classes/database.class.php");
session_start();


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => false, 'message' => 'Método não permitido']);
    exit;
}

$conection = new Database();
$conn = $conection->connect();


$theme = $_POST["theme"] ?? '';
$goal = $_POST["goal"] ?? '';
$target = $_POST["target"] ?? '';
$content = $_POST["content"] ?? '';
$duration = $_POST["duration"] ?? '';
$format = $_POST["format"] ?? ''; 
$infos = $_POST["infos"] ?? '';
$teacher_id = $_POST["teacher_id"] ?? '';
$teacher_type = $_POST["teacher_type"] ?? '';


$sql = "INSERT INTO lecture (
  theme, goal, target, content, duration, format, infos, ";


if ($teacher_type === "teacher") {
    $sql .= "teacher_id) VALUES (
    :theme, :goal, :target, :content, :duration, :format, :infos, :teacher_id)";
} 
elseif ($teacher_type === "teacher_pos") { 
    $sql .= "postg_teacher_id) VALUES (
    :theme, :goal, :target, :content, :duration, :format, :infos, :teacher_id)";
}

$data = [
    ':theme' => $theme,
    ':goal' => $goal,
    ':target' => $target,
    ':content' => $content,
    ':duration' => $duration,
    ':format' => $format,
    ':infos' => $infos,
    ':teacher_id' => $teacher_id
];

$query_run = $conn->prepare($sql);
$query_execute = $query_run->execute($data);

if($query_execute) {
    $_SESSION['form_submitted'] = true;
        $response = [
      'success' => true,
      'redirect_url' =>  "https://credenciamento.esesp.es.gov.br"
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();

} else {
        $response = [
      'success' => false,
      'redirect_url' => 'https://credenciamento.esesp.es.gov.br/credenciamento/pages/error.html'
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}


?>