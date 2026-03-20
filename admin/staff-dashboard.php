<?php
session_start();
require '../includes/db.php';
require '../includes/auth_check.php';
protectPage(['admin', 'staff']); // Staff can access this one
include '../includes/header.php';
echo '<link rel="stylesheet" href="../assets/admin.css">';

// Get only student count for the staff view
$students_count = $pdo->query("SELECT COUNT(*) FROM InterestedStudents")->fetchColumn();
?>

<div class="admin-wrapper">
    <?php include '../includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <div class="admin-header">
            <h1>Staff Dashboard</h1>
            <span class="admin-badge badge-success">Student Management Mode</span>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🎓</div>
                <div class="stat-content">
                    <h3><?= $students_count ?></h3>
                    <p>Interested Students</p>
                    <a href="students.php" class="stat-action">View All →</a>
                </div>
            </div>
        </div>
        
        </main>
</div>
<?php include '../includes/footer.php'; ?>


