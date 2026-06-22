export function initBulkCreate(wrap) {
    if (!wrap) return;

    const countInput         = wrap.querySelector('[data-bulk-count]');
    const startDateInput     = wrap.querySelector('[data-bulk-start-date]');
    const startTimeInput     = wrap.querySelector('[data-bulk-start-time]');
    const intervalSelect     = wrap.querySelector('[data-bulk-interval]');
    const customIntervalWrap = wrap.querySelector('[data-bulk-custom-interval-wrap]');
    const customIntervalInput = wrap.querySelector('[data-bulk-custom-interval]');
    const baseNameInput      = wrap.querySelector('[data-bulk-base-name]');
    const defaultTrackInput  = wrap.querySelector('[data-bulk-default-track]');
    const generateBtn        = wrap.querySelector('[data-bulk-generate]');
    const noDateHint         = wrap.querySelector('[data-bulk-no-date]');
    const eventsSection      = wrap.querySelector('[data-bulk-events-section]');
    const countDisplays      = wrap.querySelectorAll('[data-bulk-count-display]');
    const addRowBtn          = wrap.querySelector('[data-bulk-add-row]');
    const tbody              = wrap.querySelector('[data-bulk-tbody]');

    let events = [];

    function esc(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function intervalDays() {
        const v = intervalSelect?.value ?? '7';
        return v === 'custom' ? (parseInt(customIntervalInput?.value) || 7) : parseInt(v) || 7;
    }

    function formatDate(d) {
        const pad = n => String(n).padStart(2, '0');
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate())
             + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    function updateCounts() {
        countDisplays.forEach(el => { el.textContent = events.length; });
        if (generateBtn) generateBtn.disabled = !startDateInput?.value;
        if (noDateHint)  noDateHint.style.display = startDateInput?.value ? 'none' : '';
    }

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
            input.addEventListener('input', () => {
                events[i][input.dataset.field] = input.value;
            });
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

    function generate() {
        if (!startDateInput?.value) return;
        const n         = Math.min(Math.max(parseInt(countInput?.value) || 1, 1), 20);
        const startDate = startDateInput.value;
        const startTime = startTimeInput?.value || '20:00';
        const baseName  = baseNameInput?.value ?? 'Round';
        const defTrack  = defaultTrackInput?.value ?? '';
        const days      = intervalDays();

        events = Array.from({ length: n }, (_, i) => {
            const d = new Date(startDate + 'T' + startTime);
            d.setDate(d.getDate() + i * days);
            return { title: baseName ? baseName + ' ' + (i + 1) : '', track: defTrack, scheduled_at: formatDate(d) };
        });

        if (eventsSection) eventsSection.style.display = '';
        render();
    }

    function addRow() {
        const last    = events[events.length - 1];
        let nextDate  = '';
        if (last?.scheduled_at) {
            const d = new Date(last.scheduled_at);
            d.setDate(d.getDate() + intervalDays());
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

    intervalSelect?.addEventListener('change', () => {
        if (customIntervalWrap) {
            customIntervalWrap.style.display = intervalSelect.value === 'custom' ? '' : 'none';
        }
    });

    startDateInput?.addEventListener('input', updateCounts);
    generateBtn?.addEventListener('click', generate);
    addRowBtn?.addEventListener('click', addRow);

    updateCounts();
}
