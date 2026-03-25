<?php
$consentValue = $_COOKIE['cookie_consent'] ?? '';
$showConsentBanner = ($consentValue !== 'accepted' && $consentValue !== 'rejected');

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
<?php if ($showConsentBanner): ?>
<link rel="stylesheet" href="<?= $appRoot ?>/assets/cookie-consent.css">

<div id="cookie-banner" class="cookie-banner" role="dialog" aria-live="polite" aria-label="Cookie consent">
    <p>
        We use essential cookies for login sessions and security. Do you accept optional analytics cookies?
    </p>
    <div class="cookie-actions">
        <button type="button" class="cookie-btn cookie-btn-accept" data-choice="accepted">Accept</button>
        <button type="button" class="cookie-btn cookie-btn-reject" data-choice="rejected">Reject</button>
    </div>
</div>

<script src="<?= $appRoot ?>/assets/js/cookie-consent.js"></script>
<?php endif; ?>
