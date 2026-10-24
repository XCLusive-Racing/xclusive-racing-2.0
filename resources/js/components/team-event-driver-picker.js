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
                } else {
                    selected.add(id);
                }
                // A driver on more than one team has a card in each team's group.
                cards.filter(c => c.dataset.driverId === id)
                    .forEach(c => c.classList.toggle('is-selected', selected.has(id)));
                updateInput();
            });
        });

        select.addEventListener('change', updateVisibility);
        updateVisibility();
        updateInput();
    });
}
