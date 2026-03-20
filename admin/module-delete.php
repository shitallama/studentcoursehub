<?php
session_start();
require '../includes/db.php';
require '../includes/auth_check.php'; // Load the gatekeeper
protectPage(['admin']);               // ONLY 'admin' can enter
include '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../staff-login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: modules.php");
    exit;
}

$exists = $pdo->prepare("SELECT COUNT(*) FROM Modules WHERE ModuleID = ?");
$exists->execute([$id]);
if ((int)$exists->fetchColumn() === 0) {
    header("Location: modules.php");
    exit;
}

try {
    $pdo->beginTransaction();

    // Remove programme links first to satisfy FK constraints.
    $unlink = $pdo->prepare("DELETE FROM ProgrammeModules WHERE ModuleID = ?");
    $unlink->execute([$id]);
    $unlinked_count = $unlink->rowCount();

    $stmt = $pdo->prepare("DELETE FROM Modules WHERE ModuleID = ?");
    $stmt->execute([$id]);

    $pdo->commit();
    header("Location: modules.php?msg=deleted&unlinked=" . $unlinked_count);
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: modules.php");
    exit;
}
?>