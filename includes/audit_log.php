<?php

function writeAuditLog(PDO $pdo, string $actionType, array $context = []): void {
    if ($actionType === '') {
        return;
    }

    $actorUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $targetUserId = isset($context['target_user_id']) ? (int)$context['target_user_id'] : null;
    $targetStaffId = isset($context['target_staff_id']) ? (int)$context['target_staff_id'] : null;

    $details = $context;
    unset($details['target_user_id'], $details['target_staff_id']);

    $detailsJson = null;
    if (!empty($details)) {
        $encoded = json_encode($details, JSON_UNESCAPED_SLASHES);
        if ($encoded !== false) {
            $detailsJson = $encoded;
        }
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO AuditLogs (ActorUserID, ActionType, TargetUserID, TargetStaffID, Details) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$actorUserId, $actionType, $targetUserId, $targetStaffId, $detailsJson]);
    } catch (Throwable $e) {
        // Logging must not block the user action.
    }
}
