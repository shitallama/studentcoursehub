(function () {
    'use strict';

    function closeAllMenus() {
        document.querySelectorAll('.user-menu.is-open').forEach(function (menu) {
            menu.classList.remove('is-open');
            var toggle = menu.querySelector('.user-menu-toggle');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function initUserMenu() {
        var menus = document.querySelectorAll('.user-menu');
        if (!menus.length) {
            return;
        }

        menus.forEach(function (menu) {
            var toggle = menu.querySelector('.user-menu-toggle');
            if (!toggle) {
                return;
            }

            toggle.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                var isOpen = menu.classList.contains('is-open');
                closeAllMenus();

                if (!isOpen) {
                    menu.classList.add('is-open');
                    toggle.setAttribute('aria-expanded', 'true');
                }
            });
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('.user-menu')) {
                closeAllMenus();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeAllMenus();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initUserMenu);
    } else {
        initUserMenu();
    }
})();
