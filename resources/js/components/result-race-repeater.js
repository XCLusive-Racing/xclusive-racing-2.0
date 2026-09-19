export function initResultRaceRepeater() {
    document.querySelectorAll('[data-race-repeater]').forEach(repeater => {
        const container = repeater.parentElement;
        const addBtn     = container?.querySelector('[data-race-add]');
        const template   = container?.querySelector('[data-race-template]');
        if (!addBtn || !template) return;

        let index = repeater.querySelectorAll('[data-race-row]').length;

        function wireRemove(row) {
            row.querySelector('[data-race-remove]')?.addEventListener('click', () => row.remove());
        }

        repeater.querySelectorAll('[data-race-row]').forEach(wireRemove);

        addBtn.addEventListener('click', () => {
            const html = template.innerHTML.replaceAll('__INDEX__', String(index++));
            const holder = document.createElement('div');
            holder.innerHTML = html.trim();
            const row = holder.firstElementChild;
            repeater.appendChild(row);
            wireRemove(row);
        });
    });
}
