/* ===========================================================================
   CYNA — Interactions du back-office (JavaScript natif).
   Sélection groupée des lignes et confirmation des actions destructives.
   =========================================================================== */
(function () {
    'use strict';

    // Case « tout sélectionner » d'un tableau.
    var selectAll = document.querySelector('[data-select-all]');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('input[name="ids[]"]').forEach(function (box) {
                box.checked = selectAll.checked;
            });
        });
    }

    // Confirmation avant soumission d'une action destructive.
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('submit', function (event) {
            if (!window.confirm(el.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
        // Boutons hors formulaire portant data-confirm.
        if (el.tagName === 'BUTTON') {
            el.addEventListener('click', function (event) {
                if (!window.confirm(el.getAttribute('data-confirm'))) {
                    event.preventDefault();
                }
            });
        }
    });
})();
