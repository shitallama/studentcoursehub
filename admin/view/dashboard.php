<?php
session_start();
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth_check.php'; 
protectPage(['admin', 'staff']);      // Both roles can enter
include __DIR__ . '/../../includes/header.php';

// Add admin.css for styling
echo '<link rel="stylesheet" href="../../assets/admin.css">';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../staff/controller/staff-login.php");
    exit;
}

// Get counts for dashboard
$programmes_count = $pdo->query("SELECT COUNT(*) FROM Programmes")->fetchColumn();
$modules_count = $pdo->query("SELECT COUNT(*) FROM Modules")->fetchColumn();
$staff_count = $pdo->query("SELECT COUNT(*) FROM Staff")->fetchColumn();
$students_count = $pdo->query("SELECT COUNT(*) FROM InterestedStudents")->fetchColumn();

// Get recent activities
// Get recent activities
$recent_students = $pdo->query("
    SELECT
        i.*,
        p.ProgrammeName,
        COALESCE(NULLIF(u.FullName, ''), NULLIF(i.StudentName, ''), NULLIF(u.Username, ''), 'Student') AS StudentDisplayName
    FROM InterestedStudents i
    JOIN Programmes p ON i.ProgrammeID = p.ProgrammeID
    LEFT JOIN Users u ON LOWER(u.Email) = LOWER(i.Email) AND u.Role = 'student'
    ORDER BY i.RegisteredAt DESC 
    LIMIT 5
")->fetchAll();
?>

<title>Admin Dashboard | Student Course Hub</title>
    <div class="admin-wrapper">
        <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>

        <main class="admin-content">
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <div class="admin-header">
                    <h1>Admin Overview</h1>
                    <div class="admin-actions">
                        <span class="admin-badge badge-success">Full System Access</span>
                        <a href="../../student/view/profile.php?from=staff" class="admin-btn admin-btn-view">My Profile</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="admin-header">
                    <h1>Staff Workspace</h1>
                    <div class="admin-actions">
                        <span class="admin-badge badge-warning">Student Management</span>
                        <a href="../../student/view/profile.php?from=staff" class="admin-btn admin-btn-view">My Profile</a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🎓</div>
                    <div class="stat-content">
                        <h3><?= $students_count ?></h3>
                        <p>Interested Students</p>
                        <a href="students.php" class="stat-action">View →</a>
                    </div>
                </div>

                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <div class="stat-card">
                        <div class="stat-icon">📚</div>
                        <div class="stat-content">
                            <h3><?= $programmes_count ?></h3>
                            <p>Programmes</p>
                            <a href="programmes.php" class="stat-action">Manage →</a>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📖</div>
                        <div class="stat-content">
                            <h3><?= $modules_count ?></h3>
                            <p>Modules</p>
                            <a href="modules.php" class="stat-action">Manage →</a>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-content">
                            <h3><?= $staff_count ?></h3>
                            <p>Staff</p>
                            <a href="staff.php" class="stat-action">Manage →</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($_SESSION['role'] === 'admin'): ?>
                <div class="quick-actions">
                    <h2>System Control</h2>
                    <div class="action-buttons">
                        <a href="../controller/programme-add.php" class="action-btn">➕ New Programme</a>
                        <a href="../controller/module-add.php" class="action-btn">➕ New Module</a>
                        <a href="../controller/staff-add.php" class="action-btn">➕ New Staff Member</a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="recent-activity">
                <h2>Recent Student Registrations</h2>
                <div class="activity-list">
                    <?php if ($recent_students): ?>
                        <?php foreach ($recent_students as $rs): ?>
                            <div class="activity-item">
                                <div class="activity-icon">🎓</div>
                                <div class="activity-details">
                                     <strong><?= htmlspecialchars($rs['StudentDisplayName']) ?></strong>
                                    <span><?= htmlspecialchars($rs['ProgrammeName']) ?></span>
                                    <small><?= date('d M Y', strtotime($rs['RegisteredAt'])) ?></small>
                                </div>
                                <a href="students.php?programme_id=<?= $rs['ProgrammeID'] ?>" class="activity-action">View</a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-activity">No recent registrations</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>






