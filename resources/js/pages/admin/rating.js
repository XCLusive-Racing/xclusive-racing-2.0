export function initRatingRows() {
    document.querySelectorAll('[data-rating-row]').forEach(row => {
        const key     = row.dataset.ratingKey;
        const step    = parseFloat(row.dataset.ratingStep);
        const display = row.querySelector('[data-rating-display]');
        const editSec = row.querySelector('[data-rating-edit-section]');
        const input   = row.querySelector('[data-rating-input]');
        const saveBtn = row.querySelector('[data-rating-save]');
        const errorEl = row.querySelector('[data-rating-error]');

        if (!display || !editSec || !input || !saveBtn) return;

        let currentValue = parseFloat(row.dataset.ratingValue);

        const suffix  = display.dataset.ratingSuffix ?? '';
        const valueEl = display.querySelector('[data-rating-value-text]');

        function setDisplay(v) {
            if (valueEl) { valueEl.textContent = v; }
            else { display.textContent = v + suffix; }
        }

        function startEdit() {
            display.style.display = 'none';
            editSec.style.display = '';
            input.value = currentValue;
            input.step  = String(step);
            input.focus();
        }

        function cancelEdit() {
            display.style.display = '';
            editSec.style.display = 'none';
            if (errorEl) { errorEl.textContent = ''; errorEl.style.display = 'none'; }
        }

        async function save() {
            const val = parseFloat(input.value);
            saveBtn.disabled    = true;
            saveBtn.textContent = '…';
            if (errorEl) { errorEl.textContent = ''; errorEl.style.display = 'none'; }

            try {
                const r = await fetch(`/admin/rating-config/${key}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ value: val }),
                });
                const data = await r.json();
                if (r.ok) {
                    currentValue = data.value;
                    setDisplay(data.value);
                    cancelEdit();
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Saved', type: 'success' } }));
                } else {
                    if (errorEl) { errorEl.textContent = data.message || 'Failed to save'; errorEl.style.display = ''; }
                }
            } catch {
                if (errorEl) { errorEl.textContent = 'Network error'; errorEl.style.display = ''; }
            }

            saveBtn.disabled    = false;
            saveBtn.textContent = '✓';
        }

        display.addEventListener('click', startEdit);
        saveBtn.addEventListener('click', save);
        input.addEventListener('keydown', e => {
            if (e.key === 'Enter')  { e.preventDefault(); save(); }
            if (e.key === 'Escape') cancelEdit();
        });
    });
}
