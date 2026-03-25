<?php
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
?>

<footer>
    <p>&copy; <?php echo date('Y'); ?> Student Course Hub. All rights reserved.</p>
    <p>
        <a href="<?= $appRoot ?>/index.php">Home</a>
        &nbsp;|&nbsp;
        <a href="mailto:59154@edu.nielsbrock.dk">Support</a>
        &nbsp;|&nbsp;
        <span style="font-size: 0.95em; color: rgba(235, 232, 224, 0.85);">Academic Project Platform</span>
    </p>
</footer>

<?php include __DIR__ . '/cookie_consent.php'; ?>

<script src="<?= $appRoot ?>/assets/js/header-user-menu.js"></script>
<script src="<?= $appRoot ?>/assets/js/admin-sidebar.js"></script>

</body>