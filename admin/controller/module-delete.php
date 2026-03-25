<?php
session_start();
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth_check.php'; // Load the gatekeeper
protectPage(['admin', 'staff']);
include __DIR__ . '/../../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../staff/controller/staff-login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isStaff = (($_SESSION['role'] ?? '') === 'staff');
$currentStaffId = $isStaff ? ensureCurrentStaffId($pdo) : 0;

if ($id <= 0) {
    header("Location: ../view/modules.php");
    exit;
}

$exists = $pdo->prepare("SELECT COUNT(*) FROM Modules WHERE ModuleID = ?");
$existsSql = "SELECT COUNT(*) FROM Modules WHERE ModuleID = ?";
if ($isStaff) {
    $existsSql .= " AND ModuleLeaderID = ?";
    $exists = $pdo->prepare($existsSql);
    $exists->execute([$id, $currentStaffId]);
} else {
    $exists = $pdo->prepare($existsSql);
    $exists->execute([$id]);
}
if ((int)$exists->fetchColumn() === 0) {
    header("Location: ../view/modules.php");
    exit;
}

try {
    $pdo->beginTransaction();

    // Remove programme links first to satisfy FK constraints.
    $unlink = $pdo->prepare("DELETE FROM ProgrammeModules WHERE ModuleID = ?");
    $unlink->execute([$id]);
    $unlinked_count = $unlink->rowCount();

    if ($isStaff) {
        $stmt = $pdo->prepare("DELETE FROM Modules WHERE ModuleID = ? AND ModuleLeaderID = ?");
        $stmt->execute([$id, $currentStaffId]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM Modules WHERE ModuleID = ?");
        $stmt->execute([$id]);
    }

    $pdo->commit();
    header("Location: ../view/modules.php?msg=deleted&unlinked=" . $unlinked_count);
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: ../view/modules.php");
    exit;
}
?>



