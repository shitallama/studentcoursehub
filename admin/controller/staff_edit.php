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

if (!isSuperAdminSession()) {
    header("Location: ../view/staff.php?error=superadmin_only");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

// Fetch staff data
$stmt = $pdo->prepare("SELECT * FROM Staff WHERE StaffID = ?");
$stmt->execute([$id]);
$staff = $stmt->fetch();

if (!$staff) {
    header("Location: ../view/staff.php");
    exit;
}

$allProgrammes = $pdo->query("SELECT ProgrammeID, ProgrammeName FROM Programmes ORDER BY ProgrammeName")->fetchAll();
$allModules = $pdo->query("SELECT ModuleID, ModuleName FROM Modules ORDER BY ModuleName")->fetchAll();

$assignedProgrammeIds = $pdo->prepare("SELECT ProgrammeID FROM Programmes WHERE ProgrammeLeaderID = ?");
$assignedProgrammeIds->execute([$id]);
$assignedProgrammeIds = array_map('intval', $assignedProgrammeIds->fetchAll(PDO::FETCH_COLUMN));

$assignedModuleIds = $pdo->prepare("SELECT ModuleID FROM Modules WHERE ModuleLeaderID = ?");
$assignedModuleIds->execute([$id]);
$assignedModuleIds = array_map('intval', $assignedModuleIds->fetchAll(PDO::FETCH_COLUMN));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldName = (string)$staff['Name'];
    $previousProgrammeIds = $assignedProgrammeIds;
    $previousModuleIds = $assignedModuleIds;
    $name = trim($_POST['name'] ?? '');
    $selectedProgrammeIds = array_map('intval', $_POST['programme_ids'] ?? []);
    $selectedModuleIds = array_map('intval', $_POST['module_ids'] ?? []);

    if ($name === '') {
        $error = 'Staff name is required.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("UPDATE Staff SET Name = ? WHERE StaffID = ?");
            $stmt->execute([$name, $id]);

            // Programmes: unassign those not selected; assign selected to this staff.
            if (!empty($selectedProgrammeIds)) {
                $ph = implode(',', array_fill(0, count($selectedProgrammeIds), '?'));
                $clearProgrammes = $pdo->prepare("UPDATE Programmes SET ProgrammeLeaderID = NULL WHERE ProgrammeLeaderID = ? AND ProgrammeID NOT IN ($ph)");
                $clearProgrammes->execute(array_merge([$id], $selectedProgrammeIds));

                $assignProgrammes = $pdo->prepare("UPDATE Programmes SET ProgrammeLeaderID = ? WHERE ProgrammeID IN ($ph)");
                $assignProgrammes->execute(array_merge([$id], $selectedProgrammeIds));
            } else {
                $clearProgrammes = $pdo->prepare("UPDATE Programmes SET ProgrammeLeaderID = NULL WHERE ProgrammeLeaderID = ?");
                $clearProgrammes->execute([$id]);
            }

            // Modules: unassign those not selected; assign selected to this staff.
            if (!empty($selectedModuleIds)) {
                $ph = implode(',', array_fill(0, count($selectedModuleIds), '?'));
                $clearModules = $pdo->prepare("UPDATE Modules SET ModuleLeaderID = NULL WHERE ModuleLeaderID = ? AND ModuleID NOT IN ($ph)");
                $clearModules->execute(array_merge([$id], $selectedModuleIds));

                $assignModules = $pdo->prepare("UPDATE Modules SET ModuleLeaderID = ? WHERE ModuleID IN ($ph)");
                $assignModules->execute(array_merge([$id], $selectedModuleIds));
            } else {
                $clearModules = $pdo->prepare("UPDATE Modules SET ModuleLeaderID = NULL WHERE ModuleLeaderID = ?");
                $clearModules->execute([$id]);
            }

            $pdo->commit();

            // Refresh page state.
            $stmt = $pdo->prepare("SELECT * FROM Staff WHERE StaffID = ?");
            $stmt->execute([$id]);
            $staff = $stmt->fetch();

            $assignedProgrammeIds = $pdo->prepare("SELECT ProgrammeID FROM Programmes WHERE ProgrammeLeaderID = ?");
            $assignedProgrammeIds->execute([$id]);
            $assignedProgrammeIds = array_map('intval', $assignedProgrammeIds->fetchAll(PDO::FETCH_COLUMN));

            $assignedModuleIds = $pdo->prepare("SELECT ModuleID FROM Modules WHERE ModuleLeaderID = ?");
            $assignedModuleIds->execute([$id]);
            $assignedModuleIds = array_map('intval', $assignedModuleIds->fetchAll(PDO::FETCH_COLUMN));

            writeAuditLog($pdo, 'staff_assignment_updated', [
                'target_staff_id' => $id,
                'name_changed' => $oldName !== $name,
                'old_name' => $oldName,
                'new_name' => $name,
                'assigned_programme_ids' => $assignedProgrammeIds,
                'assigned_module_ids' => $assignedModuleIds,
                'added_programme_ids' => array_values(array_diff($assignedProgrammeIds, $previousProgrammeIds)),
                'removed_programme_ids' => array_values(array_diff($previousProgrammeIds, $assignedProgrammeIds)),
                'added_module_ids' => array_values(array_diff($assignedModuleIds, $previousModuleIds)),
                'removed_module_ids' => array_values(array_diff($previousModuleIds, $assignedModuleIds))
            ]);

            $success = 'Staff details and teaching assignments updated successfully.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Failed to update assignments. Please try again.';
        }
    }
}
?>

<title>Edit Staff | Student Course Hub</title>


<div class="admin-wrapper">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <div class="admin-header">
            <h1>Edit Staff & Assign Teaching</h1>
            <div class="admin-actions">
                <a href="../view/staff.php" class="admin-btn admin-btn-view">← Back to Staff</a>
            </div>
        </div>

        <?php if ($success !== ''): ?>
            <div class="auth-message success" style="margin-bottom: 1rem;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="auth-message error" style="margin-bottom: 1rem;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="admin-card">
            <div class="admin-card-body">
                <form method="POST" class="admin-form">
                    <div class="admin-form-group">
                        <label for="name">Staff Name</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($staff['Name']) ?>" placeholder="Enter staff name" required>
                    </div>

                    <div class="admin-form-group">
                        <label for="programme_ids">Assigned Programmes</label>
                        <input type="text" id="programme_filter" class="assignment-filter" placeholder="Search programmes..." autocomplete="off">
                        <div class="assignment-checkbox-list" id="programme_list">
                            <?php foreach ($allProgrammes as $programme): ?>
                                <label class="assignment-checkbox-item" data-filter-text="<?= htmlspecialchars(strtolower($programme['ProgrammeName'])) ?>">
                                    <input
                                        type="checkbox"
                                        name="programme_ids[]"
                                        value="<?= (int)$programme['ProgrammeID'] ?>"
                                        <?= in_array((int)$programme['ProgrammeID'], $assignedProgrammeIds, true) ? 'checked' : '' ?>
                                    >
                                    <span><?= htmlspecialchars($programme['ProgrammeName']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="admin-form-group">
                        <label for="module_ids">Assigned Modules</label>
                        <input type="text" id="module_filter" class="assignment-filter" placeholder="Search modules..." autocomplete="off">
                        <div class="assignment-checkbox-list" id="module_list">
                            <?php foreach ($allModules as $module): ?>
                                <label class="assignment-checkbox-item" data-filter-text="<?= htmlspecialchars(strtolower($module['ModuleName'])) ?>">
                                    <input
                                        type="checkbox"
                                        name="module_ids[]"
                                        value="<?= (int)$module['ModuleID'] ?>"
                                        <?= in_array((int)$module['ModuleID'], $assignedModuleIds, true) ? 'checked' : '' ?>
                                    >
                                    <span><?= htmlspecialchars($module['ModuleName']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="admin-form-actions">
                        <button type="submit" class="admin-btn admin-btn-primary"><span class="nav-icon"><span class="icon-svg icon-save" aria-hidden="true"></span></span> Save</button>
                        <a href="../view/staff.php" class="admin-btn admin-btn-view">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script src="../../assets/js/staff-assignments-filter.js"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>






