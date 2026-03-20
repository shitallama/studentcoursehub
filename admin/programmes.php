<?php
session_start();
require '../includes/db.php';
require '../includes/auth_check.php'; // Load the gatekeeper
protectPage(['admin']);               // ONLY 'admin' can enter
include '../includes/header.php';

// Add admin.css for styling
echo '<link rel="stylesheet" href="../assets/admin.css">';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM Programmes WHERE ProgrammeID = ?");
    $stmt->execute([$id]);
    header("Location: programmes.php?msg=deleted");
    exit;
}

// Fetch all programmes with level and leader names
$programmes = $pdo->query("
    SELECT p.*, l.LevelName, s.Name as LeaderName 
    FROM Programmes p
    JOIN Levels l ON p.LevelID = l.LevelID
    JOIN Staff s ON p.ProgrammeLeaderID = s.StaffID
    ORDER BY p.ProgrammeID DESC
")->fetchAll();
?>


<div class="admin-wrapper">
    <!-- Sidebar (same as dashboard) -->
    <?php include '../includes/admin_sidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-content">
        <div class="admin-header">
            <h1>Manage Programmes</h1>
            <div class="admin-actions">
                <a href="programme-add.php" class="admin-btn admin-btn-primary">+ Add New Programme</a>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="auth-message success" style="margin-bottom: 1rem;">
                <?php 
                if ($_GET['msg'] == 'added') echo "Programme added successfully!";
                if ($_GET['msg'] == 'updated') echo "Programme updated successfully!";
                if ($_GET['msg'] == 'deleted') echo "Programme deleted successfully!";
                ?>
            </div>
        <?php endif; ?>

        <div class="admin-card">
            <div class="admin-card-header">
                <h2>All Programmes</h2>
            </div>
            <div class="admin-card-body">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Programme Name</th>
                            <th>Level</th>
                            <th>Programme Leader</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($programmes as $p): ?>
                        <tr>
                            <td>#<?= $p['ProgrammeID'] ?></td>
                            <td><strong><?= htmlspecialchars($p['ProgrammeName']) ?></strong></td>
                            <td><span class="admin-badge badge-success"><?= htmlspecialchars($p['LevelName']) ?></span></td>
                            <td><?= htmlspecialchars($p['LeaderName']) ?></td>
                            <td class="admin-table-actions">
                                <a href="programme-view.php?id=<?= $p['ProgrammeID'] ?>" class="admin-btn admin-btn-view admin-btn-sm">👁️ View</a>
                                <a href="programme-edit.php?id=<?= $p['ProgrammeID'] ?>" class="admin-btn admin-btn-edit admin-btn-sm">✏️ Edit</a>
                                <a href="?delete=<?= $p['ProgrammeID'] ?>" 
                                   class="admin-btn admin-btn-delete admin-btn-sm" 
                                   onclick="return confirm('Are you sure you want to delete this programme?')">🗑️ Delete</a>
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


