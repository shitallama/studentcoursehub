<?php
session_start();
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth_check.php';
protectPage(['admin']);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../staff/controller/staff-login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get programme details with joins - REMOVED s.Email since it doesn't exist
$stmt = $pdo->prepare("
    SELECT p.*, l.LevelName, s.Name as LeaderName 
    FROM Programmes p
    JOIN Levels l ON p.LevelID = l.LevelID
    JOIN Staff s ON p.ProgrammeLeaderID = s.StaffID
    WHERE p.ProgrammeID = ?
");
$stmt->execute([$id]);
$programme = $stmt->fetch();

if (!$programme) {
    header("Location: programmes.php");
    exit;
}

// Get modules for this programme
$modules = $pdo->prepare("
    SELECT m.*, pm.Year, s.Name as ModuleLeader 
    FROM ProgrammeModules pm
    JOIN Modules m ON pm.ModuleID = m.ModuleID
    JOIN Staff s ON m.ModuleLeaderID = s.StaffID
    WHERE pm.ProgrammeID = ?
    ORDER BY pm.Year, m.ModuleName
");
$modules->execute([$id]);
$module_list = $modules->fetchAll();

// Get interested students count
$students = $pdo->prepare("SELECT COUNT(*) FROM InterestedStudents WHERE ProgrammeID = ?");
$students->execute([$id]);
$student_count = $students->fetchColumn();

include __DIR__ . '/../../includes/header.php';

// Add admin.css
echo '<link rel="stylesheet" href="../../assets/admin.css">';
?>

<title>Programme Details | Student Course Hub</title>

<div class="admin-wrapper">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <div class="admin-header">
            <h1><?= htmlspecialchars($programme['ProgrammeName']) ?></h1>
            <div class="admin-actions">
                <a href="../controller/programme-edit.php?id=<?= $id ?>" class="admin-btn admin-btn-edit">
                    <span class="nav-icon"><span class="icon-svg icon-edit" aria-hidden="true"></span></span> Edit
                </a>
                <a href="programmes.php" class="admin-btn admin-btn-view">
                    <span class="nav-icon">←</span> Back
                </a>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
            <!-- Main Info -->
            <div>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>Programme Details</h2>
                    </div>
                    <div class="admin-card-body">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="padding: 0.75rem; background: var(--warm-gray-100); font-weight: 600; width: 150px;">Level:</td>
                                <td style="padding: 0.75rem;">
                                    <span class="admin-badge badge-success"><?= htmlspecialchars($programme['LevelName']) ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 0.75rem; background: var(--warm-gray-100); font-weight: 600;">Programme Leader:</td>
                                <td style="padding: 0.75rem;"><?= htmlspecialchars($programme['LeaderName']) ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 0.75rem; background: var(--warm-gray-100); font-weight: 600;">Description:</td>
                                <td style="padding: 0.75rem;"><?= nl2br(htmlspecialchars($programme['Description'])) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>Modules</h2>
                        <a href="../controller/module-add.php?programme_id=<?= $id ?>" class="admin-btn admin-btn-primary admin-btn-sm">
                            <span class="nav-icon"><span class="icon-svg icon-add" aria-hidden="true"></span></span> Add Module
                        </a>
                    </div>
                    <div class="admin-card-body">
                        <?php if ($module_list): ?>
                            <?php 
                            $current_year = 0;
                            foreach ($module_list as $mod): 
                                if ($current_year != $mod['Year']): 
                                    $current_year = $mod['Year'];
                                    echo "<h3 style='color: var(--navy-700); margin: 1.5rem 0 1rem 0; border-bottom: 2px solid var(--blue-light); padding-bottom: 0.5rem;'>Year $current_year</h3>";
                                endif;
                            ?>
                            <div style="background: var(--warm-gray-100); padding: 1rem; border-radius: 8px; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong><?= htmlspecialchars($mod['ModuleName']) ?></strong><br>
                                    <small>Led by: <?= htmlspecialchars($mod['ModuleLeader']) ?></small>
                                </div>
                                <div>
                                    <a href="../controller/module-edit.php?id=<?= $mod['ModuleID'] ?>" class="admin-btn admin-btn-edit admin-btn-sm"><span class="icon-svg icon-edit" aria-hidden="true"></span></a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; padding: 2rem; color: var(--warm-gray-500);">No modules assigned yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar Info -->
            <div>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>Statistics</h2>
                    </div>
                    <div class="admin-card-body">
                        <div style="text-align: center; padding: 1rem;">
                            <div style="font-size: 3rem; color: var(--navy-800);"><?= $student_count ?></div>
                            <p style="margin: 0.5rem 0 1rem;">Interested Students</p>
                            <a href="students.php?programme_id=<?= $id ?>" class="admin-btn admin-btn-view">
                                <span class="nav-icon"><span class="icon-svg icon-students" aria-hidden="true"></span></span> View Students
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>Quick Actions</h2>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <a href="../controller/programme-edit.php?id=<?= $id ?>" class="admin-btn admin-btn-edit" style="width: 100%;">
                                <span class="nav-icon"><span class="icon-svg icon-edit" aria-hidden="true"></span></span> Edit Programme
                            </a>
                            <a href="../controller/module-add.php?programme_id=<?= $id ?>" class="admin-btn admin-btn-primary" style="width: 100%;">
                                <span class="nav-icon"><span class="icon-svg icon-add" aria-hidden="true"></span></span> Add Module
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>






