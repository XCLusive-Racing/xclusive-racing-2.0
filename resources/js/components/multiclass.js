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

    const SR_OPTIONS     = ['3', '4', '5', '6', '7', '8', '9'];
    const RATING_OPTIONS = ['rookie', 'bronze', 'silver', 'gold', 'platinum', 'alien'];

    // Existing per-class values (edit forms only) — keyed by car_class, e.g. {"GT3": {max_drivers, sr_requirement, min_rating}}
    let existing = {};
    try { existing = JSON.parse(wrap.dataset.mcExisting || '{}'); } catch { existing = {}; }

    function sync() {
        const selected = Array.from(wrap.querySelectorAll('[data-mc-class]:checked'))
            .map(cb => {
                const key   = cb.dataset.mcClass;
                const def   = CLASS_DEFS[key] || {};
                const panel = driversWrap?.querySelector(`[data-mc-panel="${key}"]`);
                const maxEl = panel?.querySelector(`[data-mc-drivers="${key}"]`);
                const srEl  = panel?.querySelector(`[data-mc-sr="${key}"]`);
                const ratEl = panel?.querySelector(`[data-mc-rating="${key}"]`);
                return {
                    name:           def.name || key,
                    color:          def.color || '#7c3aed',
                    car_class:      def.car_class || key,
                    max_drivers:    maxEl ? (maxEl.value || null) : null,
                    sr_requirement: srEl  ? (srEl.value  || null) : null,
                    min_rating:     ratEl ? (ratEl.value || null) : null,
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

                if (driversWrap && !driversWrap.querySelector(`[data-mc-panel="${key}"]`)) {
                    const ex = existing[key] || {};
                    const srOptions  = SR_OPTIONS.map(v => `<option value="${v}" ${String(ex.sr_requirement) === v ? 'selected' : ''}>${v}.0+</option>`).join('');
                    const ratOptions = RATING_OPTIONS.map(v => `<option value="${v}" ${ex.min_rating === v ? 'selected' : ''}>${v.charAt(0).toUpperCase() + v.slice(1)}+</option>`).join('');

                    const panel = document.createElement('div');
                    panel.dataset.mcPanel = key;
                    panel.style.cssText = 'min-width:230px;border:1px solid #e5e7eb;border-radius:8px;padding:10px;background:#fafafa';
                    panel.innerHTML = `
                        <div style="font-size:.72rem;font-weight:900;color:${color};margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em">${key}</div>
                        <div class="d-flex flex-wrap gap-2">
                            <div>
                                <label style="font-size:.66rem;font-weight:700;color:#6b7280;display:block;margin-bottom:2px">Max Drivers</label>
                                <input type="number" data-mc-drivers="${key}" min="1" max="100"
                                       class="form-control form-control-sm" style="width:88px" placeholder="—"
                                       value="${ex.max_drivers ?? ''}">
                            </div>
                            <div>
                                <label style="font-size:.66rem;font-weight:700;color:#6b7280;display:block;margin-bottom:2px">Min SR</label>
                                <select data-mc-sr="${key}" class="form-select form-select-sm" style="width:90px">
                                    <option value="">—</option>
                                    ${srOptions}
                                </select>
                            </div>
                            <div>
                                <label style="font-size:.66rem;font-weight:700;color:#6b7280;display:block;margin-bottom:2px">Min Rating</label>
                                <select data-mc-rating="${key}" class="form-select form-select-sm" style="width:110px">
                                    <option value="">—</option>
                                    ${ratOptions}
                                </select>
                            </div>
                        </div>
                    `;
                    panel.querySelectorAll('input, select').forEach(el => el.addEventListener('input', sync));
                    driversWrap.appendChild(panel);
                }
            } else {
                label.style.borderColor = '#e5e7eb';
                label.style.background  = '#fff';
                label.style.color       = '#374151';
                driversWrap?.querySelector(`[data-mc-panel="${key}"]`)?.remove();
            }
        }

        cb.addEventListener('change', () => { applyStyle(); sync(); });
        if (cb.checked) applyStyle(); // restore on page reload
    });

    sync();
}