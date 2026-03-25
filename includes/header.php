<?php
if (ob_get_level() === 0 && !headers_sent()) {
    ob_start();
}

if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    } else {
        // Fallback for pages that include the header after HTML output begins.
        $_SESSION = $_SESSION ?? [];
    }
}

// FIX: Define the variable before using it
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? '';

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$scriptName = '/' . ltrim($scriptName, '/');

// Compute app base URL reliably for both:
// - /studentcoursehub-main/*.php
// - /studentcoursehub-main/admin/*.php
// - /*.php (vhost root)
$appRoot = preg_replace('#/(admin|staff|student)/(view|controller)/[^/]+$#', '', $scriptName);
if ($appRoot === $scriptName) {
    $appRoot = preg_replace('#/admin/[^/]+$#', '', $scriptName);
}
if ($appRoot === $scriptName) {
    $appRoot = preg_replace('#/[^/]+$#', '', $scriptName);
}
if ($appRoot === null) {
    $appRoot = '';
}
if ($appRoot === '/') {
    $appRoot = '';
}

$url = static function (string $path) use ($appRoot): string {
    return $appRoot . '/' . ltrim($path, '/');
};

$isAdminPage = strpos($_SERVER['PHP_SELF'] ?? '', '/admin/') !== false;
$isPortalPage = (bool)preg_match('#/(admin|staff)/(view|controller)/#', $scriptName);
$showSidebarMenuToggle = $isLoggedIn && in_array($userRole, ['admin', 'staff'], true) && $isPortalPage;
$isAdminOrStaff = $isLoggedIn && in_array($userRole, ['admin', 'staff'], true);

// Determine the correct dashboard link based on role
$dashboardTarget = 'staff/controller/staff-login.php'; // Default for guests
if ($isLoggedIn) {
    if ($userRole === 'admin') {
        $dashboardTarget = 'admin/view/dashboard.php';
    } elseif ($userRole === 'staff') {
        $dashboardTarget = 'staff/view/staff-dashboard.php';
    } elseif ($userRole === 'student') {
        $dashboardTarget = 'student/view/student-dashboard.php';
    }
}

$profileTarget = 'student/view/profile.php';
if ($isLoggedIn && $userRole === 'student') {
    $profileTarget = 'student/view/profile.php?from=student';
} elseif ($isLoggedIn && ($userRole === 'admin' || $userRole === 'staff')) {
    $profileTarget = 'student/view/profile.php?from=staff';
}
?>

<script src="<?= $url('assets/js/admin-viewport.js') ?>"></script>

<header class="site-header">
    <div class="logo">
        <a href="<?= $url('index.php') ?>"><legend>Student<span>Course</span>Hub</legend></a>
    </div>
    
    <nav>
        <a href="<?= $url('index.php') ?>">Home</a>
        <a href="<?= $url('home.php') ?>">Programmes</a>
        
        <?php if ($isLoggedIn): ?>
            <a href="<?= $url($dashboardTarget) ?>" class="dashboard-link<?= $isAdminOrStaff ? ' dashboard-link-admin-staff' : '' ?>">Dashboard</a>
            
            <div class="user-menu">
                <button class="user-menu-toggle" aria-label="Open user menu" title="User menu" aria-haspopup="true" aria-expanded="false">
                    <img src="<?= $url('assets/icons/user-icon.svg') ?>" alt="User" class="header-user-icon">
                </button>
                <div class="dropdown-content">
                    <a href="<?= $url($dashboardTarget) ?>">Dashboard</a>
                    <a href="<?= $url($profileTarget) ?>">Profile</a>
                    <a href="<?= $url('logout.php') ?>">Logout (<?= htmlspecialchars($_SESSION['username']) ?>)</a>
                </div>
            </div>
        <?php else: ?>
            <div class="user-menu">
                <button class="user-menu-toggle" aria-label="Open user menu" title="User menu" aria-haspopup="true" aria-expanded="false">
                    <img src="<?= $url('assets/icons/user-icon.svg') ?>" alt="User" class="header-user-icon">
                </button>
                <div class="dropdown-content">
                    <a href="<?= $url('student/controller/student-login.php') ?>">Student Login</a>
                    <a href="<?= $url('student/controller/student-signup.php') ?>">Student Sign Up</a>
                </div>
            </div>
        <?php endif; ?>
    </nav>
</header>