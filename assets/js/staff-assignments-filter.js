(function () {
    'use strict';

    function setupAssignmentFilter(inputId, listId) {
        var input = document.getElementById(inputId);
        var list = document.getElementById(listId);
        if (!input || !list) {
            return;
        }

        var items = Array.prototype.slice.call(list.querySelectorAll('.assignment-checkbox-item'));

        input.addEventListener('input', function () {
            var query = input.value.toLowerCase().trim();

            items.forEach(function (item) {
                var text = item.getAttribute('data-filter-text') || '';
                item.style.display = text.indexOf(query) !== -1 ? '' : 'none';
            });
        });
    }

    setupAssignmentFilter('programme_filter', 'programme_list');
    setupAssignmentFilter('module_filter', 'module_list');
})();
