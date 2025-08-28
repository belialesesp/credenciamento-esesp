<?php
// backend/api/check_course_invitation_status.php
session_start();
require_once '../classes/database.class.php';

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$isAdmin = false;
if (isset($_SESSION['user_roles']) && is_array($_SESSION['user_roles'])) {
  $isAdmin = in_array('admin', $_SESSION['user_roles']);
}

$courseId = $_GET['course_id'] ?? null;
$isPostgraduate = $_GET['is_postgraduate'] ?? false;
$isStaff = $_GET['is_staff'] ?? false;
$userId = $_GET['user_id'] ?? null;

// Validate based on request type
if ($isStaff === 'true') {
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID required for staff check']);
        exit;
    }
} elseif (!$courseId) {
    echo json_encode(['success' => false, 'message' => 'Course ID required']);
    exit;
}

try {
    $connection = new Database();
    $conn = $connection->connect();

    if ($isStaff === 'true') {
        // Staff invitation logic (unchanged)
        error_log("DEBUG: Checking staff invitation for user_id: $userId");

        $stmt = $conn->prepare("
            SELECT * FROM staff_invitations 
            WHERE user_id = :user_id 
            AND (status = 'pending' OR status = 'accepted' OR status = 'rejected')
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $userId]);
        $invitation = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($invitation) {
            $hours_passed = null;
            if ($invitation['sent_at'] || $invitation['created_at']) {
                $reference_time = $invitation['sent_at'] ? $invitation['sent_at'] : $invitation['created_at'];
                $stmt = $conn->prepare("SELECT TIMESTAMPDIFF(HOUR, :ref_time, NOW()) as hours_passed");
                $stmt->execute([':ref_time' => $reference_time]);
                $hours_result = $stmt->fetch(PDO::FETCH_ASSOC);
                $hours_passed = $hours_result['hours_passed'];
            }

            $response = [
                'success' => true,
                'has_pending' => $invitation['status'] === 'pending',
                'is_accepted' => $invitation['status'] === 'accepted',
                'is_rejected' => $invitation['status'] === 'rejected',
                'contract_info' => $invitation['contract_info'] ?? null,
                'hours_passed' => $hours_passed,
                'created_at' => $invitation['created_at'] ?? null,
                'expires_at' => $invitation['expires_at'] ?? null
            ];
        } else {
            $response = [
                'success' => true,
                'has_pending' => false,
                'is_accepted' => false,
                'is_rejected' => false,
                'contract_info' => null,
                'hours_passed' => null,
                'created_at' => null,
                'expires_at' => null
            ];
        }
    } else {
        // FIXED: Course invitation status check with better logic
        $teacherType = $isPostgraduate === 'true' ? 'postgraduate' : 'regular';

        error_log("DEBUG: Checking course invitations for course_id: $courseId, teacher_type: $teacherType");

        // 1. First, update any expired pending invitations to 'expired' status
        $stmt = $conn->prepare("
            UPDATE course_invitations 
            SET status = 'expired' 
            WHERE course_id = :course_id 
            AND teacher_type = :teacher_type
            AND status = 'pending' 
            AND expires_at < NOW()
        ");
        $stmt->execute([':course_id' => $courseId, ':teacher_type' => $teacherType]);
        $expiredCount = $stmt->rowCount();

        if ($expiredCount > 0) {
            error_log("DEBUG: Updated $expiredCount expired invitations to 'expired' status");
        }

        // 2. Get all CURRENTLY pending invitations (not expired)
        $stmt = $conn->prepare("
    SELECT user_id, 
           TIMESTAMPDIFF(HOUR, created_at, NOW()) as hours_passed,
           created_at,
           expires_at,
           status,
           id
    FROM course_invitations 
    WHERE course_id = :course_id 
    AND teacher_type = :teacher_type
    AND status = 'pending'
    AND (expires_at IS NULL OR expires_at > NOW())
    ORDER BY created_at ASC
");
        $stmt->execute([':course_id' => $courseId, ':teacher_type' => $teacherType]);
        $pendingInvitations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // DEBUG: Log each pending invitation
        error_log("DEBUG: Raw pending invitations from database:");
        foreach ($pendingInvitations as $invitation) {
            error_log("Invitation ID: {$invitation['id']}, user_id: {$invitation['user_id']} (type: " . gettype($invitation['user_id']) . "), status: {$invitation['status']}");
        }

        // Convert to integers and filter out any invalid IDs
        $pendingTeacherIds = [];
        foreach ($pendingInvitations as $invitation) {
            $userId = intval($invitation['user_id']);
            if ($userId > 0) {  // Only include valid user IDs
                $pendingTeacherIds[] = $userId;
            } else {
                error_log("WARNING: Invalid user_id found in course_invitations: " . $invitation['user_id']);
            }
        }

        error_log("DEBUG: Final pending teacher IDs: " . json_encode($pendingTeacherIds));


        // 3. Get all expired teacher IDs (for frontend display)
        $stmt = $conn->prepare("
            SELECT DISTINCT user_id 
            FROM course_invitations 
            WHERE course_id = :course_id 
            AND teacher_type = :teacher_type
            AND status = 'expired'
        ");
        $stmt->execute([':course_id' => $courseId, ':teacher_type' => $teacherType]);
        $expiredTeachers = array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'user_id'));

        // 4. Get last invitation created_at (for 24-hour rule calculation)
        $stmt = $conn->prepare("
            SELECT created_at 
            FROM course_invitations 
            WHERE course_id = :course_id 
            AND teacher_type = :teacher_type 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([':course_id' => $courseId, ':teacher_type' => $teacherType]);
        $lastInvitationCreatedAt = $stmt->fetchColumn();

        // 5. Calculate hours since last invitation (based on created_at)
        $hoursSinceLastInvitation = null;
        if ($lastInvitationCreatedAt) {
            $stmt = $conn->prepare("SELECT TIMESTAMPDIFF(HOUR, :created_at, NOW()) as hours_passed");
            $stmt->execute([':created_at' => $lastInvitationCreatedAt]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $hoursSinceLastInvitation = intval($result['hours_passed']);
        }

        error_log("DEBUG: Hours since last invitation: $hoursSinceLastInvitation");

        // 6. FIXED: Determine if we can send next invitation
        $canSendNext = false;
        if (count($pendingInvitations) === 0) {
            // No pending invitations - can send
            $canSendNext = true;
            error_log("DEBUG: Can send next - no pending invitations");
        } elseif ($hoursSinceLastInvitation !== null && $hoursSinceLastInvitation >= 24) {
            // 24+ hours have passed since last invitation - can send even if there are pending
            $canSendNext = true;
            error_log("DEBUG: Can send next - 24+ hours passed ($hoursSinceLastInvitation hours)");
        } else {
            // There are pending invitations and less than 24 hours have passed
            $canSendNext = false;
            error_log("DEBUG: Cannot send next - pending invitations exist and only $hoursSinceLastInvitation hours passed");
        }

        // 7. Get all accepted teachers with contract info
        $tableName = $teacherType === 'postgraduate' ? 'postg_teacher_disciplines' : 'teacher_disciplines';

        $stmt = $conn->prepare("
            SELECT ci.user_id, td.contract_info
            FROM course_invitations ci
            LEFT JOIN $tableName td ON td.user_id = ci.user_id AND td.discipline_id = ci.course_id
            WHERE ci.course_id = :course_id 
            AND ci.teacher_type = :teacher_type
            AND ci.status = 'accepted'
        ");
        $stmt->execute([':course_id' => $courseId, ':teacher_type' => $teacherType]);

        $acceptedTeachers = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $acceptedTeachers[(string)$row['user_id']] = $row['contract_info'];
        }

        // 8. Get all rejected teachers (including manually rejected ones)
        $stmt = $conn->prepare("
            SELECT user_id
            FROM course_invitations 
            WHERE course_id = :course_id 
            AND teacher_type = :teacher_type
            AND status = 'rejected'
        ");
        $stmt->execute([':course_id' => $courseId, ':teacher_type' => $teacherType]);
        $rejectedTeacherIds = array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'user_id'));

        // 9. Combine rejected and expired teachers
        $allRejectedTeachers = array_unique(array_merge($rejectedTeacherIds, $expiredTeachers));

        // 10. Build response
        $response = [
            'success' => true,
            'can_send_next' => $canSendNext,
            'pending_teachers' => $pendingTeacherIds,
            'accepted_teachers' => $acceptedTeachers,
            'rejected_teachers' => $allRejectedTeachers,
            'expired_teachers' => $expiredTeachers,
            'hours_since_last_invitation' => $hoursSinceLastInvitation,
            'last_invitation_created_at' => $lastInvitationCreatedAt
        ];

        // Enhanced debug logging
        error_log("DEBUG Course Invitation Status Response:");
        error_log("Course ID: $courseId, Teacher Type: $teacherType");
        error_log("Pending teachers count: " . count($pendingTeacherIds));
        error_log("Pending teacher IDs: " . json_encode($pendingTeacherIds));
        error_log("Accepted teachers count: " . count($acceptedTeachers));
        error_log("Rejected teachers count: " . count($allRejectedTeachers));
        error_log("Expired teachers count: " . count($expiredTeachers));
        error_log("Hours since last invitation: $hoursSinceLastInvitation");
        error_log("Can send next: " . ($canSendNext ? 'true' : 'false'));
    }

    echo json_encode($response);
} catch (Exception $e) {
    error_log("Check invitation status error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
