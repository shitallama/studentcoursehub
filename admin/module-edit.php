<?php
session_start();
require '../includes/db.php';
require '../includes/auth_check.php';
protectPage(['admin']);
include '../includes/header.php';

echo '<link rel="stylesheet" href="../assets/admin.css">';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../staff-login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM Modules WHERE ModuleID = ?");
$stmt->execute([$id]);
$module = $stmt->fetch();

if (!$module) {
    header("Location: modules.php");
    exit;
}

$staff = $pdo->query("SELECT * FROM Staff ORDER BY Name")->fetchAll();

$linked_programmes = $pdo->prepare("
    SELECT pm.*, p.ProgrammeName
    FROM ProgrammeModules pm
    JOIN Programmes p ON pm.ProgrammeID = p.ProgrammeID
    WHERE pm.ModuleID = ?
    ORDER BY pm.Year, p.ProgrammeName
");
$linked_programmes->execute([$id]);
$linked = $linked_programmes->fetchAll();

$all_programmes = $pdo->query("SELECT * FROM Programmes ORDER BY ProgrammeName")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_module') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $leader_id = (int)($_POST['leader_id'] ?? 0);

        if ($name === '' || $description === '' || $leader_id <= 0) {
            $error = "Please fill in all module fields.";
        } else {
            $stmt = $pdo->prepare("UPDATE Modules SET ModuleName = ?, Description = ?, ModuleLeaderID = ? WHERE ModuleID = ?");
            if ($stmt->execute([$name, $description, $leader_id, $id])) {
                $success = "Module updated successfully!";
                $stmt = $pdo->prepare("SELECT * FROM Modules WHERE ModuleID = ?");
                $stmt->execute([$id]);
                $module = $stmt->fetch();
            }
        }
    }

    if ($_POST['action'] === 'add_link') {
        $programme_id = (int)($_POST['programme_id'] ?? 0);
        $year = (int)($_POST['year'] ?? 0);

        if ($programme_id <= 0 || $year < 1 || $year > 3) {
            $error = "Please select a valid programme and year.";
        } else {
            $check = $pdo->prepare("SELECT COUNT(*) FROM ProgrammeModules WHERE ProgrammeID = ? AND ModuleID = ?");
            $check->execute([$programme_id, $id]);

            if ((int)$check->fetchColumn() === 0) {
                $stmt = $pdo->prepare("INSERT INTO ProgrammeModules (ProgrammeID, ModuleID, Year) VALUES (?, ?, ?)");
                $stmt->execute([$programme_id, $id, $year]);
                $success = "Module linked to programme successfully!";
            } else {
                $error = "Module is already linked to this programme.";
            }
        }
    }

    if ($_POST['action'] === 'remove_link') {
        $link_id = (int)($_POST['link_id'] ?? 0);
        if ($link_id > 0) {
            $stmt = $pdo->prepare("DELETE FROM ProgrammeModules WHERE ProgrammeModuleID = ? AND ModuleID = ?");
            $stmt->execute([$link_id, $id]);
            $success = "Module unlinked from programme successfully!";
        }
    }

    $linked_programmes->execute([$id]);
    $linked = $linked_programmes->fetchAll();
}
?>

<div class="admin-wrapper">
    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <div class="admin-header">
            <h1>Edit Module</h1>
            <div class="admin-actions">
                <a href="module-view.php?id=<?= $id ?>" class="admin-btn admin-btn-view">
                    <span class="nav-icon"></span> View
                </a>
                <a href="modules.php" class="admin-btn admin-btn-view">
                    <span class="nav-icon"></span> Back
                </a>
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <div class="auth-message success" style="margin-bottom: 1rem;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="auth-message" style="background:#fee2e2; color:#991b1b; margin-bottom:1rem;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 1.5rem;">
            <div>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>Module Details</h2>
                    </div>
                    <div class="admin-card-body">
                        <form method="POST" class="admin-form" style="padding: 0;">
                            <input type="hidden" name="action" value="update_module">

                            <div class="admin-form-group">
                                <label for="name">Module Name</label>
                                <input type="text" id="name" name="name" value="<?= htmlspecialchars($module['ModuleName']) ?>" required>
                            </div>

                            <div class="admin-form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" rows="4" required><?= htmlspecialchars($module['Description']) ?></textarea>
                            </div>

                            <div class="admin-form-group">
                                <label for="leader_id">Module Leader</label>
                                <select id="leader_id" name="leader_id" required>
                                    <option value="">Select Module Leader</option>
                                    <?php foreach ($staff as $s): ?>
                                        <option value="<?= $s['StaffID'] ?>" <?= $s['StaffID'] == $module['ModuleLeaderID'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($s['Name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="admin-form-actions">
                                <button type="submit" class="admin-btn admin-btn-primary">
                                    <span class="nav-icon"></span> Update Module
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>Link to Programmes</h2>
                    </div>
                    <div class="admin-card-body">
                        <form method="POST" class="admin-form" style="padding: 0; margin-bottom: 1.5rem;">
                            <input type="hidden" name="action" value="add_link">

                            <div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 0.5rem; align-items: end;">
                                <div class="admin-form-group" style="margin-bottom: 0;">
                                    <label for="programme_id">Programme</label>
                                    <select id="programme_id" name="programme_id" required>
                                        <option value="">Select Programme</option>
                                        <?php foreach ($all_programmes as $p): ?>
                                            <option value="<?= $p['ProgrammeID'] ?>"><?= htmlspecialchars($p['ProgrammeName']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="admin-form-group" style="margin-bottom: 0;">
                                    <label for="year">Year</label>
                                    <select id="year" name="year" required>
                                        <option value="1">Year 1</option>
                                        <option value="2">Year 2</option>
                                        <option value="3">Year 3</option>
                                    </select>
                                </div>

                                <button type="submit" class="admin-btn admin-btn-primary" style="margin-bottom: 1px;">
                                    <span class="nav-icon"></span> Add
                                </button>
                            </div>
                        </form>

                        <h3 style="color: var(--navy-700); margin: 1rem 0 0.5rem;">Current Programme Links</h3>

                        <?php if ($linked): ?>
                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                <?php foreach ($linked as $link): ?>
                                    <div style="background: var(--warm-gray-100); padding: 0.75rem; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; gap: 0.75rem;">
                                        <div>
                                            <strong><?= htmlspecialchars($link['ProgrammeName']) ?></strong>
                                            <span class="admin-badge badge-success" style="margin-left: 0.5rem;">Year <?= $link['Year'] ?></span>
                                        </div>
                                        <form method="POST" onsubmit="return confirm('Remove this module from the programme?');">
                                            <input type="hidden" name="action" value="remove_link">
                                            <input type="hidden" name="link_id" value="<?= $link['ProgrammeModuleID'] ?>">
                                            <button type="submit" class="admin-btn admin-btn-delete admin-btn-sm">
                                                <span class="nav-icon"></span> Remove
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="text-align: center; padding: 1rem; color: var(--warm-gray-500);">Not linked to any programmes yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>


