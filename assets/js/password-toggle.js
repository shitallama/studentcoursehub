(function () {
    'use strict';

    function renderIcon(toggle, isPasswordType) {
        toggle.innerHTML = isPasswordType
            ? '<span class="icon-svg icon-eye-off" aria-hidden="true"></span>'
            : '<span class="icon-svg icon-eye" aria-hidden="true"></span>';
    }

    document.querySelectorAll('.password-toggle').forEach(function (toggle) {
        var target = document.getElementById(toggle.getAttribute('data-target'));
        if (target) {
            renderIcon(toggle, target.type === 'password');
        }

        toggle.addEventListener('click', function () {
            var input = document.getElementById(this.getAttribute('data-target'));
            if (!input) {
                return;
            }

            var isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            renderIcon(this, !isPassword);
            this.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            this.setAttribute('title', isPassword ? 'Hide password' : 'Show password');
        });
    });
})();
