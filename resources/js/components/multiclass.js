export function initMulticlass(wrap) {
    if (!wrap) return;
    const flagInput    = wrap.querySelector('[data-multiclass-flag]');
    const jsonInput    = wrap.querySelector('[data-multiclass-json]');
    const driversWrap  = wrap.querySelector('[data-mc-drivers-wrap]');
    const hint         = wrap.querySelector('[data-mc-hint]');

    const CLASS_DEFS = {
        GT3: { name: 'GT3', color: '#7c3aed', car_class: 'GT3' },
        GT4: { name: 'GT4', color: '#2563eb', car_class: 'GT4' },
        GT2: { name: 'GT2', color: '#db2777', car_class: 'GT2' },
        M2:  { name: 'M2',  color: '#16a34a', car_class: 'M2'  },
    };

    function sync() {
        const selected = Array.from(wrap.querySelectorAll('[data-mc-class]:checked'))
            .map(cb => {
                const key = cb.dataset.mcClass;
                const def = CLASS_DEFS[key] || {};
                const maxEl = driversWrap?.querySelector(`[data-mc-drivers="${key}"]`);
                return {
                    name:        def.name || key,
                    color:       def.color || '#7c3aed',
                    car_class:   def.car_class || key,
                    max_drivers: maxEl ? (maxEl.value || null) : null,
                };
            });

        if (jsonInput) jsonInput.value = JSON.stringify(selected);
        if (flagInput) flagInput.value = selected.length > 0 ? '1' : '0';
        if (driversWrap) driversWrap.style.display = selected.length > 0 ? '' : 'none';
        if (hint) hint.style.display = selected.length > 0 ? 'none' : '';
    }

    wrap.querySelectorAll('[data-mc-class]').forEach(cb => {
        const label = cb.closest('[data-mc-label]');
        const color = cb.dataset.mcColor;
        const key   = cb.dataset.mcClass;

        function applyStyle() {
            if (!label) return;
            if (cb.checked) {
                label.style.borderColor = color;
                label.style.background  = color + '18';
                label.style.color       = color;
                // Add max drivers input if not exists
                if (driversWrap && !driversWrap.querySelector(`[data-mc-drivers="${key}"]`)) {
                    const div = document.createElement('div');
                    div.innerHTML = `
                        <label style="font-size:.75rem;font-weight:700;color:${color};display:block;margin-bottom:2px">${key} — Max Drivers</label>
                        <input type="number" data-mc-drivers="${key}" min="1" max="100"
                               class="form-control form-control-sm" style="width:110px"
                               placeholder="e.g. 30">
                    `;
                    div.querySelector('input').addEventListener('input', sync);
                    driversWrap.appendChild(div);
                }
            } else {
                label.style.borderColor = '#e5e7eb';
                label.style.background  = '#fff';
                label.style.color       = '#374151';
                // Remove max drivers input
                driversWrap?.querySelector(`[data-mc-drivers="${key}"]`)?.closest('div')?.remove();
            }
        }

        cb.addEventListener('change', () => { applyStyle(); sync(); });
        if (cb.checked) applyStyle(); // restore on page reload
    });

    sync();
}
