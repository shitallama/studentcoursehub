<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$userRole = $_SESSION['role'] ?? '';
$username = $_SESSION['username'] ?? 'User';
$isAdmin = ($userRole === 'admin');
$dashboardPage = $isAdmin ? 'dashboard.php' : 'staff-dashboard.php';

function navIsActive(array $pages, string $currentPage): string {
    return in_array($currentPage, $pages, true) ? ' active' : '';
}
?>
<aside class="admin-sidebar">
    <div class="admin-sidebar-header">
        <h2><?= $isAdmin ? 'Admin Portal' : 'Staff Portal' ?></h2>
        <p>Welcome, <?= htmlspecialchars($username) ?></p>
    </div>

    <ul class="admin-nav">
        <li class="admin-nav-item">
            <a href="<?= $dashboardPage ?>" class="admin-nav-link<?= navIsActive(['dashboard.php', 'staff-dashboard.php'], $currentPage) ?>">
                <span class="nav-icon">📊</span> Dashboard
            </a>
        </li>

        <?php if ($isAdmin): ?>
            <li class="admin-nav-item">
                <a href="programmes.php" class="admin-nav-link<?= navIsActive(['programmes.php', 'programme-add.php', 'programme-edit.php', 'programme-view.php'], $currentPage) ?>">
                    <span class="nav-icon">📚</span> Programmes
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="modules.php" class="admin-nav-link<?= navIsActive(['modules.php', 'module-add.php', 'module-edit.php', 'module-view.php'], $currentPage) ?>">
                    <span class="nav-icon">📖</span> Modules
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="staff.php" class="admin-nav-link<?= navIsActive(['staff.php', 'staff-add.php', 'staff_edit.php'], $currentPage) ?>">
                    <span class="nav-icon">👥</span> Staff
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="manage-users.php" class="admin-nav-link<?= navIsActive(['manage-users.php'], $currentPage) ?>">
                    <span class="nav-icon">🛡️</span> Manage Roles
                </a>
            </li>
        <?php endif; ?>

        <li class="admin-nav-item">
            <a href="students.php" class="admin-nav-link<?= navIsActive(['students.php', 'students-edit.php'], $currentPage) ?>">
                <span class="nav-icon">🎓</span> Interested Students
            </a>
        </li>

        <li class="admin-nav-item">
            <a href="../logout.php" class="admin-nav-link">
                <span class="nav-icon">🚪</span> Logout
            </a>
        </li>
    </ul>
</aside>
