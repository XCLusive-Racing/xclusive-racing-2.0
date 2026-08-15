export function initGportalSlotPicker() {
    const serverEl = document.getElementById('gp-server');
    if (!serverEl) return;

    const schedNote = document.getElementById('gp-scheduled-note');

    function onServerChange() {
        schedNote.style.display = serverEl.value ? '' : 'none';
    }

    serverEl.addEventListener('change', onServerChange);
    onServerChange();
}
