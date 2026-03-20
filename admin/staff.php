<?php
session_start();
require '../includes/db.php';
require '../includes/auth_check.php'; // Load the gatekeeper
protectPage(['admin']);               // ONLY 'admin' can enter
include '../includes/header.php';

// Add admin.css for styling
echo '<link rel="stylesheet" href="../assets/admin.css">';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../staff-login.php");
    exit;
}

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
            $stmt = $pdo->prepare("DELETE FROM Staff WHERE StaffID = ?");
            $stmt->execute([$id]);
            header("Location: staff.php?msg=deleted");
            exit;
        }
    }
}

// Fetch all staff
$staff = $pdo->query("SELECT * FROM Staff ORDER BY Name")->fetchAll();
?>


<div class="admin-wrapper">
    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <div class="admin-header">
            <h1>Manage Staff</h1>
            <div class="admin-actions">
                <a href="staff-add.php" class="admin-btn admin-btn-primary">+ Add New Staff</a>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="auth-message error"><?= $error ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['msg'])): ?>
            <div class="auth-message success" style="margin-bottom: 1rem;">
                <?php 
                if ($_GET['msg'] == 'added') echo "Staff added successfully!";
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
                                <a href="staff_edit.php?id=<?= $s['StaffID'] ?>" class="admin-btn admin-btn-edit admin-btn-sm">✏️ Edit</a>
                                <a href="?delete=<?= $s['StaffID'] ?>" 
                                   class="admin-btn admin-btn-delete admin-btn-sm" 
                                   onclick="return confirm('Are you sure you want to delete this staff member?')">🗑️ Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>


