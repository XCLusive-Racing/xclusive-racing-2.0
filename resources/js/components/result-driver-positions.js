import { syncDisabled } from './result-form-sync.js';

// Same interaction as the Team Event driver picker: the subject decides which
// game's drivers show, clicking a card selects that driver, and each selected
// driver gets a position (and points) row underneath.
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

        wrap.querySelectorAll('[data-driver-card]').forEach(card => {
            card.addEventListener('click', () => {
                const selected = card.classList.toggle('is-selected');
                const row = card.closest('[data-driver-group]')
                    .querySelector(`[data-result-row][data-driver-id="${card.dataset.driverId}"]`);
                if (row) row.style.display = selected ? '' : 'none';
                syncDisabled(form);
            });
        });

        select.addEventListener('change', updateVisibility);
        updateVisibility();
    });
}
