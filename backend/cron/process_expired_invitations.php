<?php
// backend/cron/process_expired_invitations.php
require_once '../classes/database.class.php';

try {
    $connection = new Database();
    $conn = $connection->connect();

    // Find all expired pending invitations
    $stmt = $conn->prepare("
        SELECT * FROM course_invitations 
        WHERE status = 'pending' 
        AND expires_at < NOW()
    ");
    $stmt->execute();
    $expiredInvitations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($expiredInvitations as $invitation) {
        // Start transaction
        $conn->beginTransaction();

        try {
            // Update invitation status to expired
            $updateStmt = $conn->prepare("
                UPDATE course_invitations 
                SET status = 'expired', responded_at = NOW() 
                WHERE id = :id
            ");
            $updateStmt->bindParam(':id', $invitation['id']);
            $updateStmt->execute();

            // Update called_at in teacher_disciplines
            if ($invitation['teacher_type'] === 'postgraduate') {
                $table = 'postg_teacher_disciplines';
            } else {
                $table = 'teacher_disciplines';
            }

            // Check if the relationship exists
            $checkStmt = $conn->prepare("
                SELECT * FROM $table 
                WHERE teacher_id = :teacher_id AND discipline_id = :discipline_id
            ");
            $checkStmt->bindParam(':teacher_id', $invitation['teacher_id']);
            $checkStmt->bindParam(':discipline_id', $invitation['course_id']);
            $checkStmt->execute();

            if ($checkStmt->fetch()) {
                // Update existing record
                $updateCalledAt = $conn->prepare("
                    UPDATE $table 
                    SET called_at = NOW() 
                    WHERE teacher_id = :teacher_id AND discipline_id = :discipline_id
                ");
            } else {
                // Insert new record
                $updateCalledAt = $conn->prepare("
                    INSERT INTO $table (teacher_id, discipline_id, enabled, called_at, created_at) 
                    VALUES (:teacher_id, :discipline_id, 0, NOW(), NOW())
                ");
            }

            $updateCalledAt->bindParam(':teacher_id', $invitation['teacher_id']);
            $updateCalledAt->bindParam(':discipline_id', $invitation['course_id']);
            $updateCalledAt->execute();

            $conn->commit();
            
            echo "Processed expired invitation ID: {$invitation['id']}\n";
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Error processing expired invitation ID {$invitation['id']}: " . $e->getMessage());
        }
    }

    echo "Processed " . count($expiredInvitations) . " expired invitations\n";

} catch (Exception $e) {
    error_log("Cron job error: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
}