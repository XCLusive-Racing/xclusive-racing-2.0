export function initMulticlass(wrap) {
    if (!wrap) return;
    const flagInput    = wrap.querySelector('[data-multiclass-flag]');
    const jsonInput    = wrap.querySelector('[data-multiclass-json]');
    const driversWrap  = wrap.querySelector('[data-mc-drivers-wrap]');
    const hint         = wrap.querySelector('[data-mc-hint]');

    // The race's own overall driver cap (a plain input on a Custom Race, or a hidden
    // field auto-filled from the selected track on a format-based one) -- when set,
    // newly-selected classes default to an even split of it instead of being left
    // blank/uncapped. A class the event manager has actually typed a number into is
    // left alone; only untouched ones get overwritten when the total or the set of
    // selected classes changes.
    const totalInput = wrap.dataset.mcTotalInput
        ? document.getElementById(wrap.dataset.mcTotalInput)
        : null;
    const touched = new Set();

    function evenSplit(total, count) {
        const base = Math.floor(total / count);
        const remainder = total % count;
        // Front-load the remainder (e.g. 50 across 3 classes -> 17/17/16) so the
        // shares always sum exactly back to the race's own total.
        return Array.from({ length: count }, (_, i) => base + (i < remainder ? 1 : 0));
    }

    function autoSplitDrivers() {
        const total = parseInt(totalInput?.value, 10);
        if (!totalInput || !Number.isFinite(total) || total <= 0) return;

        const checked = Array.from(wrap.querySelectorAll('[data-mc-class]:checked'));
        if (!checked.length) return;

        const shares = evenSplit(total, checked.length);

        checked.forEach((cb, i) => {
            const key = cb.dataset.mcClass;
            if (touched.has(key)) return;

            const maxEl = driversWrap?.querySelector(`[data-mc-drivers="${key}"]`);
            if (maxEl) maxEl.value = shares[i];
        });
    }

    const CLASS_DEFS = {
        GT3: { name: 'GT3', color: '#7c3aed', car_class: 'GT3' },
        GT4: { name: 'GT4', color: '#2563eb', car_class: 'GT4' },
        GT2: { name: 'GT2', color: '#db2777', car_class: 'GT2' },
        TCX: { name: 'TCX', color: '#16a34a', car_class: 'TCX' },
        GTC: { name: 'GTC', color: '#ea580c', car_class: 'GTC' },
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
                    // A class re-selected in an edit form with a real saved cap keeps
                    // it -- only a genuinely blank one is left to auto-split.
                    if (ex.max_drivers) touched.add(key);

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
                    panel.querySelector(`[data-mc-drivers="${key}"]`).addEventListener('input', () => {
                        touched.add(key);
                        sync();
                    });
                    panel.querySelectorAll('[data-mc-sr], [data-mc-rating]').forEach(el => el.addEventListener('input', sync));
                    driversWrap.appendChild(panel);
                }
            } else {
                label.style.borderColor = '#e5e7eb';
                label.style.background  = '#fff';
                label.style.color       = '#374151';
                driversWrap?.querySelector(`[data-mc-panel="${key}"]`)?.remove();
                // Unchecking resets this class back to "untouched" -- if it's picked
                // again later, it starts from a fresh auto-split rather than whatever
                // number happened to be sitting in the removed panel.
                touched.delete(key);
            }
        }

        cb.addEventListener('change', () => {
            applyStyle();
            autoSplitDrivers();
            sync();
        });
        if (cb.checked) applyStyle(); // restore on page reload
    });

    if (totalInput) {
        totalInput.addEventListener('input', () => { autoSplitDrivers(); sync(); });
        totalInput.addEventListener('change', () => { autoSplitDrivers(); sync(); });
    }

    // Fills in any class left blank by the loop above (e.g. every class on a fresh
    // multiclass race, or one whose saved cap was lost) before the first sync().
    autoSplitDrivers();
    sync();
}