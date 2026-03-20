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

$isAdminPage = strpos($_SERVER['PHP_SELF'] ?? '', '/admin/') !== false;
$basePrefix = $isAdminPage ? '../' : '';

// Determine the correct dashboard link based on role
$dashboardTarget = 'staff-login.php'; // Default for guests
if ($isLoggedIn) {
    $dashboardTarget = ($userRole === 'admin') ? 'admin/dashboard.php' : 'admin/staff-dashboard.php';
}
?>

<?php if ($isAdminPage): ?>
<script>
(function () {
    if (!document.querySelector('meta[name="viewport"]')) {
        var meta = document.createElement('meta');
        meta.name = 'viewport';
        meta.content = 'width=device-width, initial-scale=1.0';
        document.head.appendChild(meta);
    }
})();
</script>
<?php endif; ?>

<header>
    <div class="logo">
        <legend>Student<span>Course</span><span1>Hub</span1></legend>
    </div>
    
    <nav>
        <a href="<?= $basePrefix ?>index.php">Home</a>
        <a href="<?= $basePrefix ?>home.php">Programmes</a>
        
        <?php if ($isLoggedIn): ?>
            <a href="<?= $basePrefix . $dashboardTarget ?>" class="dashboard-link">Dashboard</a>
            
            <div class="user-menu">
                <button class="user-menu-toggle">👤</button>
                <div class="dropdown-content">
                    <a href="<?= $basePrefix . $dashboardTarget ?>">My Panel</a>
                    <a href="<?= $basePrefix ?>logout.php">Logout (<?= htmlspecialchars($_SESSION['username']) ?>)</a>
                </div>
            </div>
        <?php else: ?>
            <div class="user-menu">
                <button class="user-menu-toggle">👤</button>
                <div class="dropdown-content">
                    <a href="<?= $basePrefix ?>staff-login.php">Staff & Admin Login</a>
                    <a href="<?= $basePrefix ?>home.php">Register Interest</a>
                </div>
            </div>
        <?php endif; ?>
    </nav>
</header>