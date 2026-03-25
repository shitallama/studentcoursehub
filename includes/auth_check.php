<?php
function appUrl(string $path): string {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptName = '/' . ltrim($scriptName, '/');

    $base = preg_replace('#/(admin|staff|student)/(view|controller)/[^/]+$#', '', $scriptName);
    if ($base === $scriptName) {
        $base = preg_replace('#/admin/[^/]+$#', '', $scriptName);
    }
    if ($base === $scriptName) {
        $base = preg_replace('#/[^/]+$#', '', $scriptName);
    }

    if ($base === null || $base === '/') {
        $base = '';
    }

    return $base . '/' . ltrim($path, '/');
}

function isUniversityEmail(string $email): bool {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $parts = explode('@', strtolower(trim($email)));
    if (count($parts) !== 2) {
        return false;
    }

    return $parts[1] === 'edu.nielsbrock.dk';
}

function protectPage($allowedRoles) {
    if (session_status() === PHP_SESSION_NONE) { 
        session_start(); 
    }

    // 1. If not logged in, send to root login
    if (!isset($_SESSION['role'])) {
        header("Location: " . appUrl('staff/controller/staff-login.php?error=not_logged_in'));
        exit;
    }

    // 2. If role is not allowed, send them to their designated dashboard
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        if ($_SESSION['role'] === 'staff') {
            header("Location: " . appUrl('staff/view/staff-dashboard.php?error=unauthorized'));
        } elseif ($_SESSION['role'] === 'student') {
            header("Location: " . appUrl('student/view/student-dashboard.php?error=unauthorized'));
        } else {
            header("Location: " . appUrl('admin/view/dashboard.php?error=unauthorized'));
        }
        exit;
    }
}

function ensureCurrentStaffId(PDO $pdo): int {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId <= 0 || ($_SESSION['role'] ?? '') !== 'staff') {
        return 0;
    }

    $stmt = $pdo->prepare("SELECT StaffID, Username FROM Users WHERE UserID = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    if (!$row) {
        return 0;
    }

    if (!empty($row['StaffID'])) {
        return (int)$row['StaffID'];
    }

    $pdo->beginTransaction();
    try {
        $nextId = (int)$pdo->query("SELECT COALESCE(MAX(StaffID), 0) + 1 FROM Staff")->fetchColumn();
        $name = trim((string)$row['Username']) !== '' ? trim((string)$row['Username']) : 'Staff Member';

        $insertStaff = $pdo->prepare("INSERT INTO Staff (StaffID, Name) VALUES (?, ?)");
        $insertStaff->execute([$nextId, $name]);

        $updateUser = $pdo->prepare("UPDATE Users SET StaffID = ? WHERE UserID = ?");
        $updateUser->execute([$nextId, $userId]);

        $pdo->commit();
        return $nextId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return 0;
    }
}

function isSuperAdminSession(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return strtolower(trim((string)($_SESSION['username'] ?? ''))) === 'superadmin';
}

function syncStaffUsersToStaffTable(PDO $pdo): void {
    $stmt = $pdo->query("\n        SELECT u.UserID, u.Username, u.StaffID, s.StaffID AS ExistingStaffRow\n        FROM Users u\n        LEFT JOIN Staff s ON u.StaffID = s.StaffID\n        WHERE u.Role = 'staff'\n    ");

    $rows = $stmt->fetchAll();
    if (!$rows) {
        return;
    }

    foreach ($rows as $row) {
        $hasValidStaff = !empty($row['StaffID']) && !empty($row['ExistingStaffRow']);
        if ($hasValidStaff) {
            continue;
        }

        $pdo->beginTransaction();
        try {
            $nextId = (int)$pdo->query("SELECT COALESCE(MAX(StaffID), 0) + 1 FROM Staff")->fetchColumn();
            $name = trim((string)$row['Username']) !== '' ? trim((string)$row['Username']) : 'Staff Member';

            $insertStaff = $pdo->prepare("INSERT INTO Staff (StaffID, Name) VALUES (?, ?)");
            $insertStaff->execute([$nextId, $name]);

            $updateUser = $pdo->prepare("UPDATE Users SET StaffID = ? WHERE UserID = ?");
            $updateUser->execute([$nextId, (int)$row['UserID']]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
    }
}
?>