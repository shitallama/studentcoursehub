<?php
session_start();
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth_check.php';
protectPage(['admin', 'staff']);
include __DIR__ . '/../../includes/header.php';

echo '<link rel="stylesheet" href="../../assets/admin.css">';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../staff/controller/staff-login.php");
    exit;
}

$isStaff = (($_SESSION['role'] ?? '') === 'staff');
$currentStaffId = $isStaff ? ensureCurrentStaffId($pdo) : 0;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($isStaff) {
    $stmt = $pdo->prepare("\n        SELECT m.*, s.Name AS ModuleLeader\n        FROM Modules m\n        JOIN Staff s ON m.ModuleLeaderID = s.StaffID\n        WHERE m.ModuleID = ? AND m.ModuleLeaderID = ?\n    ");
    $stmt->execute([$id, $currentStaffId]);
} else {
    $stmt = $pdo->prepare("\n        SELECT m.*, s.Name AS ModuleLeader\n        FROM Modules m\n        JOIN Staff s ON m.ModuleLeaderID = s.StaffID\n        WHERE m.ModuleID = ?\n    ");
    $stmt->execute([$id]);
}
$module = $stmt->fetch();

if (!$module) {
    header("Location: modules.php");
    exit;
}

$programme_stmt = $pdo->prepare("\n    SELECT p.ProgrammeID, p.ProgrammeName, pm.Year\n    FROM ProgrammeModules pm\n    JOIN Programmes p ON pm.ProgrammeID = p.ProgrammeID\n    WHERE pm.ModuleID = ?\n    ORDER BY pm.Year, p.ProgrammeName\n");
$programme_stmt->execute([$id]);
$linked_programmes = $programme_stmt->fetchAll();
?>

<title>Module Details | Student Course Hub</title>

<div class="admin-wrapper">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <div class="admin-header">
            <h1><?= htmlspecialchars($module['ModuleName']) ?></h1>
            <div class="admin-actions">
                <a href="../controller/module-edit.php?id=<?= $id ?>" class="admin-btn admin-btn-edit">
                    <span class="nav-icon"><span class="icon-svg icon-edit" aria-hidden="true"></span></span> Edit
                </a>
                <a href="modules.php" class="admin-btn admin-btn-view">
                    <span class="nav-icon">←</span> Back
                </a>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
            <div>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>Module Details</h2>
                    </div>
                    <div class="admin-card-body">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="padding: 0.75rem; background: var(--warm-gray-100); font-weight: 600; width: 180px;">Module Name:</td>
                                <td style="padding: 0.75rem;"><?= htmlspecialchars($module['ModuleName']) ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 0.75rem; background: var(--warm-gray-100); font-weight: 600;">Module Leader:</td>
                                <td style="padding: 0.75rem;"><?= htmlspecialchars($module['ModuleLeader']) ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 0.75rem; background: var(--warm-gray-100); font-weight: 600;">Description:</td>
                                <td style="padding: 0.75rem;"><?= nl2br(htmlspecialchars($module['Description'])) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>Linked Programmes</h2>
                    </div>
                    <div class="admin-card-body">
                        <?php if ($linked_programmes): ?>
                            <?php foreach ($linked_programmes as $p): ?>
                                <div style="background: var(--warm-gray-100); padding: 0.85rem 1rem; border-radius: 8px; margin-bottom: 0.6rem; display:flex; justify-content:space-between; align-items:center; gap:1rem;">
                                    <div>
                                        <strong><?= htmlspecialchars($p['ProgrammeName']) ?></strong>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:0.5rem;">
                                        <span class="admin-badge badge-success">Year <?= (int)$p['Year'] ?></span>
                                        <a href="programme-view.php?id=<?= (int)$p['ProgrammeID'] ?>" class="admin-btn admin-btn-view admin-btn-sm">View Programme</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; padding: 1.25rem; color: var(--warm-gray-500);">This module is not linked to any programme yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>Module Stats</h2>
                    </div>
                    <div class="admin-card-body" style="text-align:center;">
                        <div style="font-size: 2.6rem; color: var(--navy-800); font-weight: 700;"><?= count($linked_programmes) ?></div>
                        <p style="margin: 0.4rem 0 1rem;">Linked Programme(s)</p>
                    </div>
                </div>

                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>Quick Actions</h2>
                    </div>
                    <div class="admin-card-body" style="display:flex; flex-direction:column; gap:0.6rem;">
                        <a href="../controller/module-edit.php?id=<?= $id ?>" class="admin-btn admin-btn-edit" style="width:100%;">
                            <span class="nav-icon"><span class="icon-svg icon-edit" aria-hidden="true"></span></span> Edit Module
                        </a>
                        <a href="modules.php" class="admin-btn admin-btn-view" style="width:100%;">
                            <span class="nav-icon"><span class="icon-svg icon-modules" aria-hidden="true"></span></span> All Modules
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>






