(function () {
    'use strict';

    function initGlobalMobileSidebarToggle() {
        var headerToggle = document.getElementById('headerSidebarToggle');
        var sidebar = document.getElementById('adminSidebar');
        var backdrop = document.querySelector('[data-sidebar-backdrop]');
        var mobileSidebarQuery = window.matchMedia('(max-width: 820px)');

        if (!headerToggle || !sidebar) {
            return;
        }

        function closeMobileSidebar() {
            document.body.classList.remove('admin-sidebar-open');
            headerToggle.setAttribute('aria-expanded', 'false');
        }

        function toggleMobileSidebar() {
            if (!mobileSidebarQuery.matches) {
                return;
            }

            var willOpen = !document.body.classList.contains('admin-sidebar-open');
            document.body.classList.toggle('admin-sidebar-open', willOpen);
            headerToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        }

        if (!headerToggle.dataset.sidebarGlobalBound) {
            headerToggle.dataset.sidebarGlobalBound = '1';
            headerToggle.addEventListener('click', function (event) {
                event.preventDefault();
                toggleMobileSidebar();
            });
        }

        if (backdrop && !backdrop.dataset.sidebarBackdropGlobalBound) {
            backdrop.dataset.sidebarBackdropGlobalBound = '1';
            backdrop.addEventListener('click', closeMobileSidebar);
        }

        if (!document.body.dataset.sidebarEscapeGlobalBound) {
            document.body.dataset.sidebarEscapeGlobalBound = '1';
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && document.body.classList.contains('admin-sidebar-open')) {
                    closeMobileSidebar();
                }
            });
        }

        window.addEventListener('resize', function () {
            if (!mobileSidebarQuery.matches) {
                closeMobileSidebar();
            }
        });
    }

    function initAdminSidebar() {
        initGlobalMobileSidebarToggle();

        var wrappers = document.querySelectorAll('.admin-wrapper');
        if (!wrappers.length) {
            return;
        }

        var minWidth = 220;
        var maxWidth = 380;
        var defaultWidth = 260;
        var widthKey = 'adminSidebarWidth';
        var collapsedKey = 'adminSidebarCollapsed';
        var desktopQuery = window.matchMedia('(min-width: 821px)');
        var mobileSidebarQuery = window.matchMedia('(max-width: 820px)');

        wrappers.forEach(function (wrapper) {
            var sidebar = wrapper.querySelector('.admin-sidebar');
            if (!sidebar) {
                return;
            }

            var backdrop = wrapper.querySelector('[data-sidebar-backdrop]');
            var headerToggle = document.getElementById('headerSidebarToggle');

            function closeMobileSidebar() {
                document.body.classList.remove('admin-sidebar-open');
                if (headerToggle) {
                    headerToggle.setAttribute('aria-expanded', 'false');
                }
            }

            function toggleMobileSidebar() {
                var willOpen = !document.body.classList.contains('admin-sidebar-open');
                document.body.classList.toggle('admin-sidebar-open', willOpen);
                if (headerToggle) {
                    headerToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                }
            }

            if (!wrapper.style.getPropertyValue('--sidebar-width')) {
                wrapper.style.setProperty('--sidebar-width', defaultWidth + 'px');
            }

            var toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.className = 'sidebar-toggle-btn';
            toggleBtn.setAttribute('aria-label', 'Toggle sidebar');
            wrapper.appendChild(toggleBtn);

            var resizeHandle = document.createElement('div');
            resizeHandle.className = 'sidebar-resize-handle';
            resizeHandle.setAttribute('title', 'Drag to resize sidebar');
            wrapper.appendChild(resizeHandle);

            var savedWidth = parseInt(localStorage.getItem(widthKey) || '', 10);
            if (!Number.isNaN(savedWidth)) {
                savedWidth = Math.min(maxWidth, Math.max(minWidth, savedWidth));
                wrapper.style.setProperty('--sidebar-width', savedWidth + 'px');
            }

            var isCollapsed = localStorage.getItem(collapsedKey) === '1';

            function setSidebarWidth(width) {
                var clamped = Math.min(maxWidth, Math.max(minWidth, width));
                wrapper.style.setProperty('--sidebar-width', clamped + 'px');
                return clamped;
            }

            function syncToggleIcon() {
                toggleBtn.textContent = wrapper.classList.contains('sidebar-collapsed') ? '☰' : '◀';
                toggleBtn.setAttribute('aria-expanded', wrapper.classList.contains('sidebar-collapsed') ? 'false' : 'true');
            }

            function applyResponsiveState() {
                if (!desktopQuery.matches) {
                    wrapper.classList.remove('sidebar-collapsed');
                    wrapper.style.removeProperty('--sidebar-width');
                    syncToggleIcon();
                    if (!mobileSidebarQuery.matches) {
                        closeMobileSidebar();
                    }
                    return;
                }

                closeMobileSidebar();

                if (wrapper.style.getPropertyValue('--sidebar-width') === '') {
                    wrapper.style.setProperty('--sidebar-width', defaultWidth + 'px');
                }

                wrapper.classList.toggle('sidebar-collapsed', isCollapsed);
                syncToggleIcon();
            }

            toggleBtn.addEventListener('click', function () {
                if (!desktopQuery.matches) {
                    return;
                }

                isCollapsed = !wrapper.classList.contains('sidebar-collapsed');
                wrapper.classList.toggle('sidebar-collapsed', isCollapsed);
                if (!isCollapsed) {
                    var current = parseInt(getComputedStyle(wrapper).getPropertyValue('--sidebar-width'), 10) || defaultWidth;
                    setSidebarWidth(current);
                }
                localStorage.setItem(collapsedKey, isCollapsed ? '1' : '0');
                syncToggleIcon();
            });

            var startX = 0;
            var startWidth = defaultWidth;

            function onMouseMove(event) {
                if (!desktopQuery.matches) {
                    return;
                }

                var next = startWidth + (event.clientX - startX);
                setSidebarWidth(next);
            }

            function onMouseUp() {
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
                wrapper.classList.remove('is-resizing');
                document.body.style.cursor = '';
                document.body.style.userSelect = '';

                var computed = getComputedStyle(wrapper).getPropertyValue('--sidebar-width').trim();
                var currentWidth = parseInt(computed, 10);
                if (!Number.isNaN(currentWidth)) {
                    localStorage.setItem(widthKey, String(currentWidth));
                }
            }

            resizeHandle.addEventListener('mousedown', function (event) {
                if (!desktopQuery.matches || wrapper.classList.contains('sidebar-collapsed')) {
                    return;
                }

                startX = event.clientX;
                startWidth = parseInt(getComputedStyle(wrapper).getPropertyValue('--sidebar-width'), 10) || defaultWidth;
                wrapper.classList.add('is-resizing');
                document.body.style.cursor = 'col-resize';
                document.body.style.userSelect = 'none';

                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
                event.preventDefault();
            });

            resizeHandle.addEventListener('dblclick', function () {
                if (!desktopQuery.matches) {
                    return;
                }

                setSidebarWidth(defaultWidth);
                localStorage.setItem(widthKey, String(defaultWidth));
            });

            if (backdrop) {
                backdrop.addEventListener('click', closeMobileSidebar);
            }

            if (!document.body.dataset.sidebarEscapeBound) {
                document.body.dataset.sidebarEscapeBound = '1';
                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && document.body.classList.contains('admin-sidebar-open')) {
                        closeMobileSidebar();
                    }
                });
            }

            sidebar.querySelectorAll('.admin-nav-link').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (mobileSidebarQuery.matches) {
                        closeMobileSidebar();
                    }
                });
            });

            applyResponsiveState();
            window.addEventListener('resize', applyResponsiveState);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAdminSidebar);
    } else {
        initAdminSidebar();
    }
})();
