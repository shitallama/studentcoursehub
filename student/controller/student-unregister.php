<?php
session_start();
require __DIR__ . '/../../includes/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header('Location: student-login.php?error=not_logged_in');
    exit;
}

$programmeId = (int)($_GET['programme_id'] ?? 0);
$email = strtolower(trim($_SESSION['email'] ?? ''));

if ($programmeId > 0 && $email !== '') {
    $stmt = $pdo->prepare('DELETE FROM InterestedStudents WHERE ProgrammeID = ? AND LOWER(Email) = ?');
    $stmt->execute([$programmeId, $email]);
}

header('Location: ../view/student-dashboard.php?msg=unregistered');
exit;
