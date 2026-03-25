(function () {
    'use strict';

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
