export function initBulkCreate(wrap) {
    if (!wrap) return;

    // Regular mode elements
    const countInput          = wrap.querySelector('[data-bulk-count]');
    const startDateInput      = wrap.querySelector('[data-bulk-start-date]');
    const startTimeInput      = wrap.querySelector('[data-bulk-start-time]');
    const intervalSelect      = wrap.querySelector('[data-bulk-interval]');
    const customIntervalWrap  = wrap.querySelector('[data-bulk-custom-interval-wrap]');
    const customIntervalInput = wrap.querySelector('[data-bulk-custom-interval]');

    // Week schedule elements
    const weekStartInput  = wrap.querySelector('[data-bulk-week-start]');
    const weekTimeInput   = wrap.querySelector('[data-bulk-week-time]');
    const weekCountInput  = wrap.querySelector('[data-bulk-week-count]');
    const dayCheckboxes   = wrap.querySelectorAll('[data-bulk-day]');
    const regularPanel    = wrap.querySelector('[data-bulk-regular-panel]');
    const weekPanel       = wrap.querySelector('[data-bulk-week-panel]');
    const modeBtns        = wrap.querySelectorAll('[data-bulk-mode]');

    // Shared elements
    const baseNameInput     = wrap.querySelector('[data-bulk-base-name]');
    const defaultTrackInput = wrap.querySelector('[data-bulk-default-track]');
    const generateBtn       = wrap.querySelector('[data-bulk-generate]');
    const noDateHint        = wrap.querySelector('[data-bulk-no-date]');
    const eventsSection     = wrap.querySelector('[data-bulk-events-section]');
    const countDisplays     = wrap.querySelectorAll('[data-bulk-count-display]');
    const addRowBtn         = wrap.querySelector('[data-bulk-add-row]');
    const tbody             = wrap.querySelector('[data-bulk-tbody]');

    let events = [];
    let mode   = 'regular';

    function esc(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function pad(n) { return String(n).padStart(2, '0'); }

    function formatDate(d) {
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate())
             + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    function intervalDays() {
        const v = intervalSelect?.value ?? '7';
        return v === 'custom' ? (parseInt(customIntervalInput?.value) || 7) : parseInt(v) || 7;
    }

    function canGenerate() {
        if (mode === 'week') {
            return !!weekStartInput?.value && Array.from(dayCheckboxes).some(cb => cb.checked);
        }
        return !!startDateInput?.value;
    }

    function updateCounts() {
        countDisplays.forEach(el => { el.textContent = events.length; });
        const ok = canGenerate();
        if (generateBtn) generateBtn.disabled = !ok;
        if (noDateHint) {
            noDateHint.style.display = ok ? 'none' : '';
            noDateHint.textContent   = mode === 'week'
                ? 'Pick a start date and select at least one day'
                : 'Pick a start date first';
        }
    }

    // ── Mode switching ──────────────────────────────────────────────────
    function setMode(m) {
        mode = m;
        if (regularPanel) regularPanel.style.display = m === 'regular' ? '' : 'none';
        if (weekPanel)    weekPanel.style.display     = m === 'week'    ? '' : 'none';

        modeBtns.forEach(btn => {
            const active = btn.dataset.bulkMode === m;
            btn.style.background   = active ? '#7c3aed' : 'transparent';
            btn.style.color        = active ? '#fff'    : '#9ca3af';
            btn.style.borderColor  = active ? '#7c3aed' : '#e5e7eb';
        });

        updateCounts();
    }

    modeBtns.forEach(btn => {
        btn.addEventListener('click', () => setMode(btn.dataset.bulkMode));
    });

    // ── Day checkbox pill styling ────────────────────────────────────────
    dayCheckboxes.forEach(cb => {
        const label = cb.closest('[data-bulk-day-label]');
        cb.addEventListener('change', () => {
            if (label) {
                label.style.background  = cb.checked ? 'rgba(124,58,237,.1)' : '#fff';
                label.style.borderColor = cb.checked ? '#7c3aed'             : '#e5e7eb';
                label.style.color       = cb.checked ? '#7c3aed'             : '#374151';
            }
            updateCounts();
        });
    });

    // ── Row rendering ────────────────────────────────────────────────────
    function renderRow(i) {
        const ev = events[i];
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="ps-4 text-secondary fw-bold" style="font-size:.8rem">${i + 1}</td>
            <td>
                <input type="text" name="events[${i}][title]" value="${esc(ev.title)}"
                       class="form-control form-control-sm" data-field="title" required>
            </td>
            <td>
                <input type="text" name="events[${i}][track]" value="${esc(ev.track)}"
                       class="form-control form-control-sm" data-field="track" required>
            </td>
            <td>
                <input type="datetime-local" name="events[${i}][scheduled_at]" value="${esc(ev.scheduled_at)}"
                       class="form-control form-control-sm" data-field="scheduled_at" required>
            </td>
            <td class="pe-4">
                <button type="button" data-remove
                        class="btn btn-sm d-flex align-items-center justify-content-center"
                        style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;width:28px;height:28px;padding:0;font-size:.85rem">
                    ✕
                </button>
            </td>
        `;
        tr.querySelectorAll('[data-field]').forEach(input => {
            input.addEventListener('input', () => { events[i][input.dataset.field] = input.value; });
        });
        tr.querySelector('[data-remove]').addEventListener('click', () => {
            events.splice(i, 1);
            render();
        });
        return tr;
    }

    function render() {
        if (!tbody) return;
        tbody.innerHTML = '';
        events.forEach((_, i) => tbody.appendChild(renderRow(i)));
        updateCounts();
    }

    // ── Generate: regular mode ───────────────────────────────────────────
    function generate() {
        if (!startDateInput?.value) return;
        const n        = Math.min(Math.max(parseInt(countInput?.value) || 1, 1), 20);
        const date     = startDateInput.value;
        const time     = startTimeInput?.value || '20:00';
        const baseName = baseNameInput?.value ?? 'Round';
        const defTrack = defaultTrackInput?.value ?? '';
        const days     = intervalDays();

        events = Array.from({ length: n }, (_, i) => {
            const d = new Date(date + 'T' + time);
            d.setDate(d.getDate() + i * days);
            return { title: baseName ? baseName + ' ' + (i + 1) : '', track: defTrack, scheduled_at: formatDate(d) };
        });

        if (eventsSection) eventsSection.style.display = '';
        render();
    }

    // ── Generate: week schedule mode ─────────────────────────────────────
    function generateWeek() {
        if (!weekStartInput?.value) return;

        const checkedDays = Array.from(dayCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => parseInt(cb.dataset.bulkDay))
            .sort((a, b) => a - b);

        if (checkedDays.length === 0) return;

        const time     = weekTimeInput?.value || '20:00';
        const nWeeks   = Math.min(Math.max(parseInt(weekCountInput?.value) || 1, 1), 12);
        const baseName = baseNameInput?.value ?? 'Round';
        const defTrack = defaultTrackInput?.value ?? '';
        const [th, tm] = time.split(':').map(Number);

        // Find Monday of the week containing weekStartInput.value
        const seed    = new Date(weekStartInput.value + 'T12:00');
        const jsDay   = seed.getDay(); // 0=Sun … 6=Sat
        const toMon   = jsDay === 0 ? -6 : 1 - jsDay;
        const monday  = new Date(seed);
        monday.setDate(monday.getDate() + toMon);

        events = [];
        let counter = 1;
        for (let w = 0; w < nWeeks; w++) {
            checkedDays.forEach(dayOffset => {
                const d = new Date(monday);
                d.setDate(d.getDate() + w * 7 + dayOffset);
                d.setHours(th, tm, 0, 0);
                events.push({
                    title:        baseName ? baseName + ' ' + counter : '',
                    track:        defTrack,
                    scheduled_at: formatDate(d),
                });
                counter++;
            });
        }

        if (eventsSection) eventsSection.style.display = '';
        render();
    }

    // ── Add row ──────────────────────────────────────────────────────────
    function addRow() {
        const last   = events[events.length - 1];
        let nextDate = '';
        if (last?.scheduled_at) {
            const d = new Date(last.scheduled_at);
            d.setDate(d.getDate() + (mode === 'week' ? 7 : intervalDays()));
            nextDate = formatDate(d);
        }
        const baseName = baseNameInput?.value ?? 'Round';
        events.push({
            title:        baseName ? baseName + ' ' + (events.length + 1) : '',
            track:        defaultTrackInput?.value ?? '',
            scheduled_at: nextDate,
        });
        render();
    }

    // ── Event listeners ──────────────────────────────────────────────────
    intervalSelect?.addEventListener('change', () => {
        if (customIntervalWrap) {
            customIntervalWrap.style.display = intervalSelect.value === 'custom' ? '' : 'none';
        }
    });

    startDateInput?.addEventListener('input', updateCounts);
    weekStartInput?.addEventListener('input', updateCounts);
    weekCountInput?.addEventListener('input', updateCounts);

    generateBtn?.addEventListener('click', () => {
        if (mode === 'week') generateWeek();
        else generate();
    });

    addRowBtn?.addEventListener('click', addRow);

    updateCounts();
}