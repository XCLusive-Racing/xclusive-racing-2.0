import { initDateTimePickers } from '../../components/datetime-picker.js';

export function initImportExport(wrap) {
    if (!wrap) return;

    const fileInput     = wrap.querySelector('[data-ie-file]');
    const importBtn     = wrap.querySelector('[data-ie-import]');
    const addRowBtn     = wrap.querySelector('[data-ie-add-row]');
    const downloadBtn   = wrap.querySelector('[data-ie-download]');
    const tbody         = wrap.querySelector('[data-ie-tbody]');
    const eventsSection = wrap.querySelector('[data-ie-events-section]');
    const countDisplay  = wrap.querySelector('[data-ie-count-display]');
    const errorsBox     = wrap.querySelector('[data-ie-errors]');
    const gameSelect    = wrap.querySelector('[data-ie-game]');
    const importUrl     = wrap.dataset.ieImportUrl;

    const WEATHER_OPTIONS = [
        ['', '— Not set —'], ['dry', 'Dry'], ['wet', 'Wet'], ['mixed', 'Mixed'], ['random', 'Random'],
    ];

    let events = [];

    function esc(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function maxDriversForTrack(track) {
        return window.__ceTracks?.[track]?.max ?? '';
    }

    // Mirrors the same format-driven auto-select already used on the Create Race page
    // (see admin/races/form.blade.php): short = Server 1 (any hour), medium splits
    // even/odd hour across Server 2/3, long = Server 4 (manual restart, any time).
    function serverNumberFor(group, hour) {
        if (group === 'short')  return 1;
        if (group === 'long')   return 4;
        if (group === 'medium') return hour % 2 === 0 ? 2 : 3;
        return null;
    }

    function formatById(id) {
        return (window.__ieFormats || []).find(f => f.value === String(id || ''));
    }

    // Auto-fills a row's Event Tag / Server from its own Format's server_group /
    // default_event_tag — but only for fields the row hasn't already got a value for
    // (an explicit CSV column or a manual pick always wins over auto-detection).
    function autoFillRow(ev) {
        const fmt = formatById(ev.event_format_id);
        if (!fmt) return;

        if (!ev.event_tag && fmt.default_event_tag) {
            ev.event_tag = fmt.default_event_tag;
        }

        if (!ev.ftp_server_id && fmt.server_group && ev.scheduled_at) {
            const d = new Date(ev.scheduled_at);
            if (!isNaN(d)) {
                const number = serverNumberFor(fmt.server_group, d.getHours());
                const srv = (window.__ieServers || []).find(s => String(s.number) === String(number));
                if (srv) ev.ftp_server_id = srv.value;
            }
        }
    }

    function tagOptions(selected) {
        const opts = [['', '— Use shared —'], ...(window.__ieTags || []).map(t => [t.value, t.label])];
        return opts.map(([v, label]) => `<option value="${v}" ${selected === v ? 'selected' : ''}>${esc(label)}</option>`).join('');
    }

    function formatOptions(selected) {
        const opts = [['', '— Use shared —'], ...(window.__ieFormats || []).map(f => [f.value, f.label])];
        return opts.map(([v, label]) => `<option value="${v}" ${selected === v ? 'selected' : ''}>${esc(label)}</option>`).join('');
    }

    function serverOptions(selected) {
        const opts = [['', '— Use shared —'], ...(window.__ieServers || []).map(s => [s.value, s.label])];
        return opts.map(([v, label]) => `<option value="${v}" ${selected === v ? 'selected' : ''}>${esc(label)}</option>`).join('');
    }

    function showErrors(list) {
        if (!errorsBox) return;
        if (!list || !list.length) {
            errorsBox.style.display = 'none';
            errorsBox.innerHTML = '';
            return;
        }
        errorsBox.style.display = '';
        errorsBox.innerHTML = '<div class="fw-bold mb-1">Some rows needed attention:</div><ul class="mb-0" style="padding-left:1.1rem">'
            + list.map(e => `<li>${esc(e)}</li>`).join('')
            + '</ul>';
    }

    function updateCount() {
        if (countDisplay) countDisplay.textContent = events.length;
    }

    function renderRow(i) {
        const ev = events[i];
        const weatherOptions = WEATHER_OPTIONS.map(([v, label]) =>
            `<option value="${v}" ${ev.weather === v ? 'selected' : ''}>${label}</option>`).join('');

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="ps-4 text-secondary fw-bold" style="font-size:.8rem">${i + 1}</td>
            <td>
                <input type="hidden" name="events[${i}][title]" data-field="title" value="${esc(ev.title)}">
                <input type="hidden" name="events[${i}][max_drivers]" data-field="max_drivers" value="${esc(maxDriversForTrack(ev.track))}">
                <input type="text" name="events[${i}][track]" value="${esc(ev.track)}"
                       class="form-control form-control-sm" data-field="track" required>
            </td>
            <td>
                <input type="text" name="events[${i}][scheduled_at]" value="${esc(ev.scheduled_at)}"
                       data-flatpickr data-min-today="true"
                       class="form-control form-control-sm" data-field="scheduled_at" required>
            </td>
            <td>
                <select name="events[${i}][event_tag]" class="form-select form-select-sm" data-field="event_tag">
                    ${tagOptions(ev.event_tag || '')}
                </select>
            </td>
            <td>
                <select name="events[${i}][event_format_id]" class="form-select form-select-sm" data-field="event_format_id">
                    ${formatOptions(ev.event_format_id || '')}
                </select>
            </td>
            <td>
                <select name="events[${i}][ftp_server_id]" class="form-select form-select-sm" data-field="ftp_server_id">
                    ${serverOptions(ev.ftp_server_id || '')}
                </select>
            </td>
            <td>
                <select name="events[${i}][weather]" class="form-select form-select-sm" data-field="weather">
                    ${weatherOptions}
                </select>
            </td>
            <td>
                <input type="time" name="events[${i}][time_of_day]" value="${esc(ev.time_of_day)}"
                       class="form-control form-control-sm" data-field="time_of_day" step="3600">
            </td>
            <td>
                <input type="number" name="events[${i}][ambient_temp]" value="${esc(ev.ambient_temp)}"
                       class="form-control form-control-sm" data-field="ambient_temp" placeholder="Default">
            </td>
            <td class="pe-4">
                <button type="button" data-remove
                        class="btn btn-sm d-flex align-items-center justify-content-center"
                        style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;width:28px;height:28px;padding:0;font-size:.85rem">
                    ✕
                </button>
            </td>
        `;

        const titleHidden      = tr.querySelector('[data-field="title"]');
        const maxDriversHidden = tr.querySelector('[data-field="max_drivers"]');
        const trackInput       = tr.querySelector('[data-field="track"]');
        const dateInput        = tr.querySelector('[data-field="scheduled_at"]');
        const tagInput         = tr.querySelector('[data-field="event_tag"]');
        const formatInput      = tr.querySelector('[data-field="event_format_id"]');
        const serverInput      = tr.querySelector('[data-field="ftp_server_id"]');
        const weatherInput     = tr.querySelector('[data-field="weather"]');
        const timeInput        = tr.querySelector('[data-field="time_of_day"]');
        const ambientTempInput = tr.querySelector('[data-field="ambient_temp"]');

        trackInput.addEventListener('input', () => {
            events[i].track = trackInput.value;
            events[i].title = trackInput.value;
            titleHidden.value = trackInput.value;
            maxDriversHidden.value = maxDriversForTrack(trackInput.value);
        });
        dateInput.addEventListener('change', () => {
            events[i].scheduled_at = dateInput.value;
            autoFillRow(events[i]);
            render();
        });
        tagInput.addEventListener('change', () => { events[i].event_tag = tagInput.value; });
        formatInput.addEventListener('change', () => {
            events[i].event_format_id = formatInput.value;
            autoFillRow(events[i]);
            render();
        });
        serverInput.addEventListener('change', () => { events[i].ftp_server_id = serverInput.value; });
        weatherInput.addEventListener('change', () => { events[i].weather = weatherInput.value; });
        timeInput.addEventListener('change', () => { events[i].time_of_day = timeInput.value; });
        ambientTempInput.addEventListener('input', () => { events[i].ambient_temp = ambientTempInput.value; });

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
        updateCount();
        initDateTimePickers();
        if (eventsSection) eventsSection.style.display = events.length ? '' : 'none';
    }

    function addRow() {
        events.push({
            title: '', track: '', scheduled_at: '',
            event_tag: '', event_format_id: '', ftp_server_id: '',
            weather: '', time_of_day: '', ambient_temp: '',
        });
        render();
    }

    function csvCell(v) {
        const s = String(v ?? '');
        return /[",\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
    }

    // Exports the current (possibly hand-edited) preview table back out as a CSV in the
    // same format bulkImportCsv() accepts — so a batch can be tweaked here and re-used,
    // or handed off, without re-typing it from scratch.
    function downloadCsv() {
        if (!events.length) return;

        const header = ['track', 'date', 'time', 'format', 'event_tag', 'server', 'weather', 'time_of_day', 'ambient_temp'];
        const lines = [header.join(',')];

        events.forEach(ev => {
            const [date, time] = (ev.scheduled_at || '').split('T');
            const format = (window.__ieFormats || []).find(f => f.value === String(ev.event_format_id || ''));
            const server = (window.__ieServers || []).find(s => s.value === String(ev.ftp_server_id || ''));

            lines.push([
                ev.track || '', date || '', time || '',
                format?.label || '', ev.event_tag || '', server ? (server.number ?? server.label) : '',
                ev.weather || '', ev.time_of_day || '', ev.ambient_temp ?? '',
            ].map(csvCell).join(','));
        });

        const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = 'xcl-races-' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    }

    async function importFile() {
        const file = fileInput?.files?.[0];
        if (!file) return;

        const original = importBtn.textContent;
        importBtn.disabled = true;
        importBtn.textContent = 'Importing…';

        try {
            const formData = new FormData();
            formData.append('file', file);
            if (gameSelect?.value) formData.append('game', gameSelect.value);

            const res = await fetch(importUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                body: formData,
            });
            const data = await res.json();

            if (!res.ok) {
                showErrors(data.errors || ['Import failed.']);
                return;
            }

            const newRows = data.rows || [];
            newRows.forEach(autoFillRow);
            events = events.concat(newRows);
            showErrors(data.errors);
            render();
            fileInput.value = '';
        } catch (e) {
            showErrors(['Something went wrong reading that file — check it\'s a valid CSV.']);
        } finally {
            importBtn.disabled = false;
            importBtn.textContent = original;
        }
    }

    importBtn?.addEventListener('click', importFile);
    addRowBtn?.addEventListener('click', addRow);
    downloadBtn?.addEventListener('click', downloadCsv);

    updateCount();
}
