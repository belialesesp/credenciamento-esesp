<?php
// backend/api/check_course_invitation_status.php
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

    $teacherType = $isPostgraduate === 'true' ? 'postgraduate' : 'regular';

    // Get all pending invitations
    $stmt = $conn->prepare("
        SELECT teacher_id, TIMESTAMPDIFF(HOUR, sent_at, NOW()) as hours_passed
        FROM course_invitations 
        WHERE course_id = :course_id 
        AND teacher_type = :teacher_type
        AND status = 'pending'
        AND expires_at > NOW()
    ");
    $stmt->execute([':course_id' => $courseId, ':teacher_type' => $teacherType]);
    
    $pendingInvitations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $pendingTeacherIds = array_column($pendingInvitations, 'teacher_id');
    
    // Get all accepted teachers with contract info
    $tableName = $teacherType === 'postgraduate' ? 'postg_teacher_disciplines' : 'teacher_disciplines';
    
    $stmt = $conn->prepare("
        SELECT ci.teacher_id, td.contract_info
        FROM course_invitations ci
        LEFT JOIN $tableName td ON td.teacher_id = ci.teacher_id AND td.discipline_id = ci.course_id
        WHERE ci.course_id = :course_id 
        AND ci.teacher_type = :teacher_type
        AND ci.status = 'accepted'
    ");
    $stmt->execute([':course_id' => $courseId, ':teacher_type' => $teacherType]);
    
    $acceptedTeachers = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Always use string keys for consistency
        $acceptedTeachers[(string)$row['teacher_id']] = $row['contract_info'];
    }

    $response = [
        'success' => true,
        'can_send_next' => count($pendingInvitations) === 0,
        'pending_teachers' => array_map('intval', $pendingTeacherIds),
        'accepted_teachers' => $acceptedTeachers,
        'hours_passed' => $pendingInvitations[0]['hours_passed'] ?? null
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Check invitation status error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}