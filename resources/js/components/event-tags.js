import { toast } from '../lib/swal.js';

export function initEventTags(wrap) {
    if (!wrap) return;

    const config      = JSON.parse(wrap.dataset.config);
    const select      = wrap.querySelector('[data-tags-select]');
    const addPanel    = wrap.querySelector('[data-tags-add-panel]');
    const toggleBtn   = wrap.querySelector('[data-tags-toggle]');
    const nameInput   = wrap.querySelector('[data-tags-name]');
    const colorInput  = wrap.querySelector('[data-tags-color]');
    const saveBtn     = wrap.querySelector('[data-tags-save]');
    const errorEl     = wrap.querySelector('[data-tags-error]');

    let tags = config.tags || [];
    const selectedTag = config.selectedTag || '';

    function renderOptions() {
        const current = select.value || selectedTag;
        Array.from(select.options).filter(o => o.dataset.tag).forEach(o => o.remove());
        tags.forEach(t => {
            const opt = new Option(t.name, t.slug);
            opt.dataset.tag = '1';
            if (t.slug === current) opt.selected = true;
            select.appendChild(opt);
        });
    }

    renderOptions();

    toggleBtn?.addEventListener('click', () => {
        const open = addPanel.style.display !== 'none';
        addPanel.style.display = open ? 'none' : 'block';
        toggleBtn.textContent = open ? '+ New' : '✕ Cancel';
        if (open && errorEl) { errorEl.textContent = ''; errorEl.style.display = 'none'; }
        if (!open) nameInput?.focus();
    });

    saveBtn?.addEventListener('click', save);
    nameInput?.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); save(); } });

    async function save() {
        const name  = nameInput.value.trim();
        const color = colorInput?.value ?? '#7B2FBE';
        if (!name) return;

        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving…';
        if (errorEl) errorEl.textContent = '';

        try {
            const res = await fetch(config.storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ name, color }),
            });
            const data = await res.json();
            if (res.ok) {
                tags.push(data);
                renderOptions();
                select.value = data.slug;
                nameInput.value = '';
                if (colorInput) colorInput.value = '#7B2FBE';
                if (errorEl) { errorEl.textContent = ''; errorEl.style.display = 'none'; }
                addPanel.style.display = 'none';
                if (toggleBtn) toggleBtn.textContent = '+ New';
            } else {
                if (errorEl) { errorEl.textContent = data.errors?.name?.[0] || data.message || 'Failed to save.'; errorEl.style.display = 'block'; }
            }
        } catch {
            if (errorEl) { errorEl.textContent = 'Network error.'; errorEl.style.display = 'block'; }
        }

        saveBtn.disabled = false;
        saveBtn.textContent = 'Add';
    }
}
