<?php
session_start();
require '../includes/db.php';
require '../includes/auth_check.php'; // Load the gatekeeper
protectPage(['admin']);               // ONLY 'admin' can enter
include '../includes/header.php';

// Add admin.css
echo '<link rel="stylesheet" href="../assets/admin.css">';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../staff-login.php");
    exit;
}

// Handle Delete via GET (using the new delete file)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    header("Location: module-delete.php?id=" . $id);
    exit;
}

// Fetch all modules with leader names and programme counts
$modules = $pdo->query("
    SELECT m.*, s.Name as ModuleLeader,
           (SELECT COUNT(*) FROM ProgrammeModules WHERE ModuleID = m.ModuleID) as programme_count
    FROM Modules m
    JOIN Staff s ON m.ModuleLeaderID = s.StaffID
    ORDER BY m.ModuleName
")->fetchAll();
?>

<div class="admin-wrapper">
    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <div class="admin-header">
            <h1>Manage Modules</h1>
            <div class="admin-actions">
                <a href="module-add.php" class="admin-btn admin-btn-primary">
                    <span class="nav-icon">➕</span> Add New Module
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
                <h2>All Modules</h2>
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
                                    <span class="nav-icon">👁️</span> View
                                </a>
                                <a href="module-edit.php?id=<?= $m['ModuleID'] ?>" class="admin-btn admin-btn-edit admin-btn-sm">
                                    <span class="nav-icon">✏️</span> Edit
                                </a>
                                <a href="?delete=<?= $m['ModuleID'] ?>" 
                                   class="admin-btn admin-btn-delete admin-btn-sm" 
                                   onclick="return confirm('Delete this module? Any programme links will be removed automatically.');">
                                    <span class="nav-icon">🗑️</span> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="text-align: center; padding: 2rem; color: var(--warm-gray-500);">No modules found. <a href="module-add.php">Add your first module</a>.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>


