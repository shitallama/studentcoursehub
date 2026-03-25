<?php
session_start();
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth_check.php';
require __DIR__ . '/../../includes/audit_log.php';
protectPage(['admin']); // Restricted to Admin only
include __DIR__ . '/../../includes/header.php';
echo '<link rel="stylesheet" href="../../assets/admin.css">';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../staff/controller/staff-login.php");
    exit;
}

// Handle User Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $target_id = (int)$_POST['user_id'];
    $current_user_id = (int)$_SESSION['user_id'];

    if ($target_id === $current_user_id) {
        $error = "You cannot delete your own account while logged in.";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM Users WHERE UserID = ?");
            $stmt->execute([$target_id]);
            $msg = "User deleted successfully.";
        } catch (PDOException $e) {
            $error = "Unable to delete this user. Remove related records first.";
        }
    }
}

// Handle Role Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $target_id = (int)$_POST['user_id'];
    $new_role = $_POST['new_role'] ?? '';
    $current_user_id = (int)$_SESSION['user_id'];

    $targetRoleStmt = $pdo->prepare("SELECT Role, Username, StaffID FROM Users WHERE UserID = ?");
    $targetRoleStmt->execute([$target_id]);
    $targetUser = $targetRoleStmt->fetch();
    $target_role = strtolower((string)($targetUser['Role'] ?? ''));
    $target_staff_id = (int)($targetUser['StaffID'] ?? 0);
    $target_username = trim((string)($targetUser['Username'] ?? 'Staff Member'));

    if (!in_array($new_role, ['staff', 'student'], true)) {
        $error = "Invalid role selected.";
    } elseif ($target_role === 'admin') {
        $error = "Admin accounts are locked and cannot be created or modified here.";
    } elseif ($target_id === $current_user_id) {
        $error = "You cannot change your own role while logged in.";
    } else {
        try {
            $pdo->beginTransaction();

            if ($new_role === 'staff') {
                $hasValidStaff = false;
                if ($target_staff_id > 0) {
                    $exists = $pdo->prepare("SELECT COUNT(*) FROM Staff WHERE StaffID = ?");
                    $exists->execute([$target_staff_id]);
                    $hasValidStaff = ((int)$exists->fetchColumn() > 0);
                }

                if (!$hasValidStaff) {
                    $nextId = (int)$pdo->query("SELECT COALESCE(MAX(StaffID), 0) + 1 FROM Staff")->fetchColumn();
                    $insertStaff = $pdo->prepare("INSERT INTO Staff (StaffID, Name) VALUES (?, ?)");
                    $insertStaff->execute([$nextId, $target_username !== '' ? $target_username : 'Staff Member']);
                    $target_staff_id = $nextId;
                }

                $stmt = $pdo->prepare("UPDATE Users SET Role = 'staff', StaffID = ? WHERE UserID = ?");
                $stmt->execute([$target_staff_id, $target_id]);
            } else {
                if ($target_staff_id > 0) {
                    $assignedProgrammeCount = $pdo->prepare("SELECT COUNT(*) FROM Programmes WHERE ProgrammeLeaderID = ?");
                    $assignedProgrammeCount->execute([$target_staff_id]);
                    $assignedModuleCount = $pdo->prepare("SELECT COUNT(*) FROM Modules WHERE ModuleLeaderID = ?");
                    $assignedModuleCount->execute([$target_staff_id]);

                    if ((int)$assignedProgrammeCount->fetchColumn() > 0 || (int)$assignedModuleCount->fetchColumn() > 0) {
                        throw new RuntimeException('Cannot move this staff member to student while still assigned to modules/programmes.');
                    }
                }

                $stmt = $pdo->prepare("UPDATE Users SET Role = 'student', StaffID = NULL WHERE UserID = ?");
                $stmt->execute([$target_id]);
            }

            $pdo->commit();
            writeAuditLog($pdo, 'role_changed', [
                'target_user_id' => $target_id,
                'from_role' => $target_role,
                'to_role' => $new_role
            ]);
            $msg = "User role updated to " . htmlspecialchars($new_role) . "!";
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e instanceof RuntimeException ? $e->getMessage() : 'Failed to update user role.';
        }
    }
}

$users = $pdo->query("SELECT UserID, Username, Email, Role FROM Users")->fetchAll();
?>

<title>Manage Users | Student Course Hub</title>

<div class="admin-wrapper">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <div class="admin-header">
            <h1>System Permissions</h1>
        </div>

        <?php if(isset($msg)): ?>
            <div class="auth-message success"><?= $msg ?></div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="auth-message" style="background:#fee2e2; color:#991b1b; margin-bottom:1rem;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="admin-card">
            <div class="admin-card-body">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Current Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <?php
                            $role = strtolower(trim((string)($u['Role'] ?? '')));
                            if (!in_array($role, ['admin', 'staff', 'student'], true)) {
                                $role = 'staff';
                            }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($u['Username']) ?></td>
                            <td><?= htmlspecialchars($u['Email']) ?></td>
                            <td>
                                <span class="admin-badge <?= $role === 'admin' ? 'badge-success' : (($role === 'staff') ? 'badge-warning' : 'badge-primary') ?>">
                                    <?= strtoupper($role) ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                                    <input type="hidden" name="user_id" value="<?= $u['UserID'] ?>">
                                    <?php if ($role === 'admin'): ?>
                                        <select class="admin-form-group" style="padding:5px; margin:0;" disabled>
                                            <option selected>Admin (Locked)</option>
                                        </select>
                                        <button type="button" class="admin-btn admin-btn-sm admin-btn-view" disabled style="opacity:0.55; cursor:not-allowed;"><span class="nav-icon"><span class="icon-svg icon-lock" aria-hidden="true"></span></span>Locked</button>
                                    <?php else: ?>
                                        <select name="new_role" class="admin-form-group" style="padding:5px; margin:0;">
                                            <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>Student</option>
                                            <option value="staff" <?= $role === 'staff' ? 'selected' : '' ?>>Staff</option>
                                        </select>
                                        <button type="submit" name="update_role" class="admin-btn admin-btn-sm admin-btn-edit"><span class="nav-icon"><span class="icon-svg icon-save" aria-hidden="true"></span></span>Update</button>
                                    <?php endif; ?>
                                    <button
                                        type="submit"
                                        name="delete_user"
                                        class="admin-btn admin-btn-sm admin-btn-delete"
                                        <?= ((int)$u['UserID'] === (int)$_SESSION['user_id'] || $role === 'admin') ? 'disabled title="This account cannot be deleted here" style="opacity:0.55; cursor:not-allowed;"' : 'onclick="return confirm(\'Delete this user account? This cannot be undone.\');"' ?>
                                    >
                                        <span class="nav-icon"><span class="icon-svg icon-delete" aria-hidden="true"></span></span>Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>






