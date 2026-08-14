export function initTeamEventDriverPicker() {
    document.querySelectorAll('[data-driver-picker]').forEach(wrap => {
        const form   = wrap.closest('form');
        const select = form?.querySelector('[data-driver-picker-select]');
        const input  = wrap.querySelector('[data-driver-picker-input]');
        const groups = [...wrap.querySelectorAll('[data-driver-group]')];
        const cards  = [...wrap.querySelectorAll('[data-driver-card]')];
        if (!select || !input) return;

        const selected = new Set(
            cards.filter(c => c.classList.contains('is-selected')).map(c => c.dataset.driverId)
        );

        function updateInput() {
            input.value = JSON.stringify([...selected].map(Number));
        }

        function updateVisibility() {
            const match = groups.find(g => g.dataset.driverGroup === select.value);
            groups.forEach(g => { g.style.display = g === match ? '' : 'none'; });
            wrap.style.display = match ? '' : 'none';
        }

        cards.forEach(card => {
            card.addEventListener('click', () => {
                const id = card.dataset.driverId;
                if (selected.has(id)) {
                    selected.delete(id);
                    card.classList.remove('is-selected');
                } else {
                    selected.add(id);
                    card.classList.add('is-selected');
                }
                updateInput();
            });
        });

        select.addEventListener('change', updateVisibility);
        updateVisibility();
        updateInput();
    });
}
