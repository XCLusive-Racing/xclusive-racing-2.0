export function initCheckboxToggles() {
    document.querySelectorAll('[data-controls]').forEach(checkbox => {
        const target = document.getElementById(checkbox.dataset.controls);
        if (!target) return;

        checkbox.addEventListener('change', () => {
            target.style.display = checkbox.checked ? '' : 'none';
        });
    });
}
