import { syncDisabled } from './result-form-sync.js';

// The subject decides which game's drivers show. Selecting a driver (a checkbox
// inside its card) and revealing that driver's row is pure CSS, in the
// _esports-fields partial.
export function initResultDriverPositions() {
    document.querySelectorAll('[data-result-fields="esports"]').forEach(wrap => {
        const form   = wrap.closest('form');
        const select = form?.querySelector('[data-driver-picker-select]');
        const groups = [...wrap.querySelectorAll('[data-driver-group]')];
        if (!select) return;

        function updateVisibility() {
            const match = groups.find(g => g.dataset.driverGroup === select.value);
            groups.forEach(g => { g.style.display = g === match ? '' : 'none'; });
            syncDisabled(form);
        }

        select.addEventListener('change', updateVisibility);
        updateVisibility();
    });
}
