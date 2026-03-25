<?php
session_start();
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth_check.php';
protectPage(['admin', 'staff']); // Staff can access this one
include __DIR__ . '/../../includes/header.php';
echo '<link rel="stylesheet" href="../../assets/admin.css">';

// Get only student count for the staff view
$students_count = $pdo->query("SELECT COUNT(*) FROM InterestedStudents")->fetchColumn();
$staffId = ensureCurrentStaffId($pdo);
$my_modules_count = 0;
if ($staffId > 0) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Modules WHERE ModuleLeaderID = ?");
    $stmt->execute([$staffId]);
    $my_modules_count = (int)$stmt->fetchColumn();
}
?>

<title>Staff Dashboard | Student Course Hub</title>

<div class="admin-wrapper">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <div class="admin-header">
            <h1>Staff Dashboard</h1>
            <div class="admin-actions">
                <span class="admin-badge badge-success">Student Management Mode</span>
                <a href="../../student/view/profile.php?from=staff" class="admin-btn admin-btn-view">My Profile</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><span class="icon-svg icon-students" aria-hidden="true"></span></div>
                <div class="stat-content">
                    <h3><?= $students_count ?></h3>
                    <p>Interested Students</p>
                    <a href="../../admin/view/students.php" class="stat-action">View All →</a>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><span class="icon-svg icon-modules" aria-hidden="true"></span></div>
                <div class="stat-content">
                    <h3><?= $my_modules_count ?></h3>
                    <p>My Teaching Modules</p>
                    <a href="../../admin/view/modules.php" class="stat-action">Manage →</a>
                </div>
            </div>
        </div>
        
        </main>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>


