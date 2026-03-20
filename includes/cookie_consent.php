<?php
$consentValue = $_COOKIE['cookie_consent'] ?? '';
$showConsentBanner = ($consentValue !== 'accepted' && $consentValue !== 'rejected');
?>
<?php if ($showConsentBanner): ?>
<div id="cookie-banner" class="cookie-banner" role="dialog" aria-live="polite" aria-label="Cookie consent">
    <p>
        We use essential cookies for login sessions and security. Do you accept optional analytics cookies?
    </p>
    <div class="cookie-actions">
        <button type="button" class="cookie-btn cookie-btn-accept" data-choice="accepted">Accept</button>
        <button type="button" class="cookie-btn cookie-btn-reject" data-choice="rejected">Reject</button>
    </div>
</div>

<style>
.cookie-banner {
    position: fixed;
    right: 1rem;
    bottom: 1rem;
    max-width: 420px;
    background: #0f172a;
    color: #ffffff;
    padding: 1rem;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
    z-index: 9999;
    font-size: 0.95rem;
}

.cookie-banner p {
    margin: 0 0 0.8rem;
    line-height: 1.4;
}

.cookie-actions {
    display: flex;
    gap: 0.5rem;
}

.cookie-btn {
    border: none;
    border-radius: 7px;
    padding: 0.55rem 0.8rem;
    cursor: pointer;
    font-weight: 600;
}

.cookie-btn-accept {
    background: #22c55e;
    color: #052e16;
}

.cookie-btn-reject {
    background: #e2e8f0;
    color: #0f172a;
}

@media (max-width: 640px) {
    .cookie-banner {
        left: 0.75rem;
        right: 0.75rem;
        bottom: 0.75rem;
        max-width: none;
    }
}
</style>

<script>
(function () {
    var banner = document.getElementById('cookie-banner');
    if (!banner) {
        return;
    }

    banner.querySelectorAll('button[data-choice]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var choice = this.getAttribute('data-choice');
            var maxAge = 60 * 60 * 24 * 365;
            document.cookie = 'cookie_consent=' + choice + '; path=/; max-age=' + maxAge + '; SameSite=Lax';
            banner.remove();
        });
    });
})();
</script>
<?php endif; ?>
