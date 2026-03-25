<?php
session_start();
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth_check.php'; // Load the gatekeeper
require __DIR__ . '/../../includes/audit_log.php';
protectPage(['admin']);               // ONLY 'admin' can enter
include __DIR__ . '/../../includes/header.php';

// Add admin.css for styling
echo '<link rel="stylesheet" href="../../assets/admin.css">';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../staff/controller/staff-login.php");
    exit;
}

$isSuperAdmin = isSuperAdminSession();

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Check if staff is assigned as programme leader
    $check = $pdo->prepare("SELECT COUNT(*) FROM Programmes WHERE ProgrammeLeaderID = ?");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        $error = "Cannot delete staff member assigned as Programme Leader.";
    } else {
        // Also check if assigned as module leader
        $check2 = $pdo->prepare("SELECT COUNT(*) FROM Modules WHERE ModuleLeaderID = ?");
        $check2->execute([$id]);
        if ($check2->fetchColumn() > 0) {
            $error = "Cannot delete staff member assigned as Module Leader.";
        } else {
            try {
                $pdo->beginTransaction();

                $linkedUsersStmt = $pdo->prepare("SELECT UserID, Username, Email FROM Users WHERE StaffID = ? AND Role = 'staff'");
                $linkedUsersStmt->execute([$id]);
                $linkedUsers = $linkedUsersStmt->fetchAll();

                $deleteUsersStmt = $pdo->prepare("DELETE FROM Users WHERE StaffID = ? AND Role = 'staff'");
                $deleteUsersStmt->execute([$id]);

                $deleteStaffStmt = $pdo->prepare("DELETE FROM Staff WHERE StaffID = ?");
                $deleteStaffStmt->execute([$id]);

                $pdo->commit();

                foreach ($linkedUsers as $deletedUser) {
                    writeAuditLog($pdo, 'staff_account_deleted', [
                        'target_user_id' => (int)$deletedUser['UserID'],
                        'target_staff_id' => $id,
                        'username' => (string)$deletedUser['Username'],
                        'email' => (string)$deletedUser['Email']
                    ]);
                }

                writeAuditLog($pdo, 'staff_deleted', [
                    'target_staff_id' => $id,
                    'linked_staff_accounts_deleted' => count($linkedUsers)
                ]);

                header("Location: staff.php?msg=deleted");
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = "Failed to delete staff member. Please try again.";
            }
        }
    }
}

// Fetch all staff
$staff = $pdo->query("SELECT * FROM Staff ORDER BY Name")->fetchAll();
?>

<title>Manage Staff | Student Course Hub</title>

<div class="admin-wrapper">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <div class="admin-header">
            <h1>Manage Staff</h1>
            <div class="admin-actions">
                <?php if ($isSuperAdmin): ?>
                    <a href="../controller/staff-add.php" class="admin-btn admin-btn-primary"><span class="nav-icon"><span class="icon-svg icon-add" aria-hidden="true"></span></span> Create Staff ID</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="auth-message error"><?= $error ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'superadmin_only'): ?>
            <div class="auth-message error" style="margin-bottom: 1rem;">Only SuperAdmin can create staff IDs.</div>
        <?php endif; ?>

        <?php if (!$isSuperAdmin): ?>
            <div class="auth-message" style="background:#fff7ed; color:#9a3412; margin-bottom:1rem;">
                Staff ID creation is restricted to SuperAdmin.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg'])): ?>
            <div class="auth-message success" style="margin-bottom: 1rem;">
                <?php 
                if ($_GET['msg'] == 'added') echo "Staff added successfully!";
                if ($_GET['msg'] == 'added_account') echo "Staff account created successfully!";
                if ($_GET['msg'] == 'updated') echo "Staff updated successfully!";
                if ($_GET['msg'] == 'deleted') echo "Staff deleted successfully!";
                ?>
            </div>
        <?php endif; ?>

        <div class="admin-card">
            <div class="admin-card-header">
                <h2>All Staff Members</h2>
                <span class="admin-badge badge-success">Total: <?= count($staff) ?></span>
            </div>
            <div class="admin-card-body">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Assigned Programmes</th>
                            <th>Assigned Modules</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff as $s): 
                            // Count programmes led
                            $prog_count = $pdo->prepare("SELECT COUNT(*) FROM Programmes WHERE ProgrammeLeaderID = ?");
                            $prog_count->execute([$s['StaffID']]);
                            $prog_total = $prog_count->fetchColumn();
                            
                            // Count modules led
                            $mod_count = $pdo->prepare("SELECT COUNT(*) FROM Modules WHERE ModuleLeaderID = ?");
                            $mod_count->execute([$s['StaffID']]);
                            $mod_total = $mod_count->fetchColumn();
                        ?>
                        <tr>
                            <td>#<?= $s['StaffID'] ?></td>
                            <td><strong><?= htmlspecialchars($s['Name']) ?></strong></td>
                            <td>
                                <?php if ($prog_total > 0): ?>
                                    <span class="admin-badge badge-success"><?= $prog_total ?> Programme(s)</span>
                                <?php else: ?>
                                    <span class="admin-badge badge-warning">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($mod_total > 0): ?>
                                    <span class="admin-badge badge-success"><?= $mod_total ?> Module(s)</span>
                                <?php else: ?>
                                    <span class="admin-badge badge-warning">None</span>
                                <?php endif; ?>
                            </td>
                            <td class="admin-table-actions">
                                <a href="../controller/staff_edit.php?id=<?= $s['StaffID'] ?>" class="admin-btn admin-btn-edit admin-btn-sm"><span class="nav-icon"><span class="icon-svg icon-edit" aria-hidden="true"></span></span>Edit</a>
                                <a href="?delete=<?= $s['StaffID'] ?>" 
                                   class="admin-btn admin-btn-delete admin-btn-sm" 
                                   onclick="return confirm('Are you sure you want to delete this staff member?')"><span class="nav-icon"><span class="icon-svg icon-delete" aria-hidden="true"></span></span>Delete</a>
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






