<?php
session_start();
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth_check.php'; // Load the gatekeeper
protectPage(['admin', 'staff']);
include __DIR__ . '/../../includes/header.php';

// Add admin.css
echo '<link rel="stylesheet" href="../../assets/admin.css">';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../staff/controller/staff-login.php");
    exit;
}

// Handle Delete via GET (using the new delete file)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    header("Location: ../controller/module-delete.php?id=" . $id);
    exit;
}

// Fetch modules with role-aware filtering
$params = [];
$where = '';

if (($_SESSION['role'] ?? '') === 'staff') {
    $currentStaffId = ensureCurrentStaffId($pdo);
    if ($currentStaffId <= 0) {
        $modules = [];
    } else {
        $where = 'WHERE m.ModuleLeaderID = ?';
        $params[] = $currentStaffId;
    }
}

if (!isset($modules)) {
    $stmt = $pdo->prepare("
        SELECT m.*, s.Name as ModuleLeader,
               (SELECT COUNT(*) FROM ProgrammeModules WHERE ModuleID = m.ModuleID) as programme_count
        FROM Modules m
        JOIN Staff s ON m.ModuleLeaderID = s.StaffID
        {$where}
        ORDER BY m.ModuleName
    ");
    $stmt->execute($params);
    $modules = $stmt->fetchAll();
}
?>

<title>Manage Modules | Student Course Hub</title>

<div class="admin-wrapper">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <div class="admin-header">
            <h1><?= ($_SESSION['role'] ?? '') === 'staff' ? 'My Teaching Modules' : 'Manage Modules' ?></h1>
            <div class="admin-actions">
                <a href="../controller/module-add.php" class="admin-btn admin-btn-primary">
                    <span class="nav-icon"><span class="icon-svg icon-add" aria-hidden="true"></span></span> Add New Module
                </a>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="auth-message success" style="margin-bottom: 1rem;">
                <?php 
                if ($_GET['msg'] == 'added') echo "Module added successfully!";
                if ($_GET['msg'] == 'updated') echo "Module updated successfully!";
                if ($_GET['msg'] == 'deleted') {
                    $unlinked = isset($_GET['unlinked']) ? (int)$_GET['unlinked'] : 0;
                    if ($unlinked > 0) {
                        echo "Module deleted successfully! Removed {$unlinked} programme link(s).";
                    } else {
                        echo "Module deleted successfully!";
                    }
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="admin-card">
            <div class="admin-card-header">
                <h2><?= ($_SESSION['role'] ?? '') === 'staff' ? 'My Modules' : 'All Modules' ?></h2>
                <span class="admin-badge badge-success">Total: <?= count($modules) ?></span>
            </div>
            <div class="admin-card-body">
                <?php if ($modules): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Module Name</th>
                            <th>Module Leader</th>
                            <th>Programmes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $m): ?>
                        <tr>
                            <td>#<?= $m['ModuleID'] ?></td>
                            <td><strong><?= htmlspecialchars($m['ModuleName']) ?></strong></td>
                            <td><?= htmlspecialchars($m['ModuleLeader']) ?></td>
                            <td>
                                <?php if ($m['programme_count'] > 0): ?>
                                    <span class="admin-badge badge-success"><?= $m['programme_count'] ?> Programme(s)</span>
                                <?php else: ?>
                                    <span class="admin-badge badge-warning">Not linked</span>
                                <?php endif; ?>
                            </td>
                            <td class="admin-table-actions">
                                <a href="module-view.php?id=<?= $m['ModuleID'] ?>" class="admin-btn admin-btn-view admin-btn-sm">
                                    <span class="nav-icon"><span class="icon-svg icon-view" aria-hidden="true"></span></span> View
                                </a>
                                <a href="../controller/module-edit.php?id=<?= $m['ModuleID'] ?>" class="admin-btn admin-btn-edit admin-btn-sm">
                                    <span class="nav-icon"><span class="icon-svg icon-edit" aria-hidden="true"></span></span> Edit
                                </a>
                                <a href="?delete=<?= $m['ModuleID'] ?>" 
                                   class="admin-btn admin-btn-delete admin-btn-sm" 
                                   onclick="return confirm('Delete this module? Any programme links will be removed automatically.');">
                                    <span class="nav-icon"><span class="icon-svg icon-delete" aria-hidden="true"></span></span> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="text-align: center; padding: 2rem; color: var(--warm-gray-500);">
                    <?= ($_SESSION['role'] ?? '') === 'staff' ? 'No teaching modules assigned yet.' : 'No modules found.' ?>
                    <a href="../controller/module-add.php">Add your first module</a>.
                </p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>






