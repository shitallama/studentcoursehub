<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$userRole = $_SESSION['role'] ?? '';
$username = $_SESSION['username'] ?? 'User';
$isAdmin = ($userRole === 'admin');

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$scriptName = '/' . ltrim($scriptName, '/');
$appRoot = preg_replace('#/(admin|staff|student)/(view|controller)/[^/]+$#', '', $scriptName);
if ($appRoot === $scriptName) {
    $appRoot = preg_replace('#/admin/[^/]+$#', '', $scriptName);
}
if ($appRoot === $scriptName) {
    $appRoot = preg_replace('#/[^/]+$#', '', $scriptName);
}
if ($appRoot === null || $appRoot === '/') {
    $appRoot = '';
}

$url = static function (string $path) use ($appRoot): string {
    return $appRoot . '/' . ltrim($path, '/');
};

$dashboardPage = $isAdmin ? $url('admin/view/dashboard.php') : $url('staff/view/staff-dashboard.php');

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
                <span class="nav-icon"><span class="icon-svg icon-dashboard" aria-hidden="true"></span></span> Dashboard
            </a>
        </li>

        <?php if ($isAdmin): ?>
            <li class="admin-nav-item">
                <a href="<?= $url('admin/view/programmes.php') ?>" class="admin-nav-link<?= navIsActive(['programmes.php', 'programme-add.php', 'programme-edit.php', 'programme-view.php'], $currentPage) ?>">
                    <span class="nav-icon"><span class="icon-svg icon-programmes" aria-hidden="true"></span></span> Programmes
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="<?= $url('admin/view/modules.php') ?>" class="admin-nav-link<?= navIsActive(['modules.php', 'module-add.php', 'module-edit.php', 'module-view.php'], $currentPage) ?>">
                    <span class="nav-icon"><span class="icon-svg icon-modules" aria-hidden="true"></span></span> Modules
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="<?= $url('admin/view/staff.php') ?>" class="admin-nav-link<?= navIsActive(['staff.php', 'staff-add.php', 'staff_edit.php'], $currentPage) ?>">
                    <span class="nav-icon"><span class="icon-svg icon-staff" aria-hidden="true"></span></span> Staff
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="<?= $url('admin/view/manage-users.php') ?>" class="admin-nav-link<?= navIsActive(['manage-users.php'], $currentPage) ?>">
                    <span class="nav-icon"><span class="icon-svg icon-roles" aria-hidden="true"></span></span> Manage Roles
                </a>
            </li>
        <?php endif; ?>

        <li class="admin-nav-item">
            <a href="<?= $url('admin/view/students.php') ?>" class="admin-nav-link<?= navIsActive(['students.php', 'students-edit.php'], $currentPage) ?>">
                <span class="nav-icon"><span class="icon-svg icon-students" aria-hidden="true"></span></span> Interested Students
            </a>
        </li>

        <li class="admin-nav-item">
            <a href="<?= $url('logout.php') ?>" class="admin-nav-link">
                <span class="nav-icon"><span class="icon-svg icon-logout" aria-hidden="true"></span></span> Logout
            </a>
        </li>
    </ul>
</aside>
