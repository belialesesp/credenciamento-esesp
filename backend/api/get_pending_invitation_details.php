<?php
session_start();
require_once '../classes/database.class.php';

header('Content-Type: application/json');

$courseId = $_GET['course_id'] ?? null;
$isPostgraduate = $_GET['is_postgraduate'] ?? false;

if (!$courseId) {
    echo json_encode(['success' => false, 'message' => 'Course ID required']);
    exit;
}

try {
    $connection = new Database();
    $conn = $connection->connect();

    $teacherType = $isPostgraduate ? 'postgraduate' : 'regular';
    $tableName = $isPostgraduate ? 'postg_course_invitations' : 'course_invitations';

    $stmt = $conn->prepare("
        SELECT * FROM $tableName 
        WHERE course_id = :course_id 
        AND status = 'pending'
        AND expires_at > NOW()
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([':course_id' => $courseId]);
    $pendingInvitation = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'pending_invitation' => $pendingInvitation
    ]);
} catch (Exception $e) {
    error_log("Get pending invitation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}