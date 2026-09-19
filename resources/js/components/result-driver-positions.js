// Shows the driver-position rows for whichever game matches the selected
// subject — same visibility rule as team-event-driver-picker.js's
// updateVisibility(), just without that module's click-to-select card
// behavior (every driver here always has its own always-visible input).
export function initResultDriverPositions() {
    document.querySelectorAll('[data-result-fields="esports"]').forEach(wrap => {
        const form   = wrap.closest('form');
        const select = form?.querySelector('[data-driver-picker-select]');
        const groups = [...wrap.querySelectorAll('[data-driver-group]')];
        if (!select) return;

        function updateVisibility() {
            const match = groups.find(g => g.dataset.driverGroup === select.value);
            groups.forEach(g => { g.style.display = g === match ? '' : 'none'; });
        }

        select.addEventListener('change', updateVisibility);
        updateVisibility();
    });
}
