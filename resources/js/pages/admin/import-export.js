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

    const RATING_OPTIONS = [
        ['', '—'], ['all', 'All'], ['rookie', 'Rookie'], ['bronze', 'Bronze'],
        ['silver', 'Silver'], ['gold', 'Gold'], ['platinum', 'Platinum'], ['alien', 'Alien'],
    ];
    const SR_OPTIONS = [['', '—'], ['3', '3'], ['4', '4'], ['5', '5'], ['6', '6'], ['7', '7'], ['8', '8'], ['9', '9']];

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

    // Auto-fills a row's Server from its own Format's server_group — only when the row
    // hasn't already got one (a manual pick always wins). Event Tag isn't handled here
    // any more at all — it's never picked, the backend derives it 1:1 from the format.
    function autoFillRow(ev) {
        const fmt = formatById(ev.event_format_id);
        if (!fmt) return;

        if (!ev.ftp_server_id && fmt.server_group && ev.scheduled_at) {
            const d = new Date(ev.scheduled_at);
            if (!isNaN(d)) {
                const number = serverNumberFor(fmt.server_group, d.getHours());
                const srv = (window.__ieServers || []).find(s => String(s.number) === String(number));
                if (srv) ev.ftp_server_id = srv.value;
            }
        }
    }

    function formatOptions(selected) {
        const opts = [['', '— Use shared —'], ...(window.__ieFormats || []).map(f => [f.value, f.label])];
        return opts.map(([v, label]) => `<option value="${v}" ${selected === v ? 'selected' : ''}>${esc(label)}</option>`).join('');
    }

    function serverOptions(selected) {
        const opts = [['', '— Use shared —'], ...(window.__ieServers || []).map(s => [s.value, s.label])];
        return opts.map(([v, label]) => `<option value="${v}" ${selected === v ? 'selected' : ''}>${esc(label)}</option>`).join('');
    }

    function timeMultiplierSelect(i, field, selected) {
        const sel = String(selected ?? '1');
        let opts = '';
        for (let m = 1; m <= 24; m++) {
            opts += `<option value="${m}" ${sel === String(m) ? 'selected' : ''}>${m}×</option>`;
        }
        return `<select name="events[${i}][${field}]" class="form-select form-select-sm" data-field="${field}">${opts}</select>`;
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

        const [datePart, timePart] = (ev.scheduled_at || '').split('T');
        const classes = ev.classes || [];
        // Read-only preview only — GT3 (+1) for a 2-class multiclass row, etc. The real
        // submitted value is the hidden car_class field below; this span never has a
        // `name`, so it can't itself corrupt what actually gets sent.
        const carClassDisplay = ev.car_class
            ? esc(ev.car_class) + (classes.length > 1 ? ` <span style="color:#9ca3af">+${classes.length - 1}</span>` : '')
            : '<span style="color:#9ca3af">—</span>';

        const ratingOptions = (selected) => RATING_OPTIONS
            .map(([v, label]) => `<option value="${v}" ${selected === v ? 'selected' : ''}>${label}</option>`).join('');
        const srOptions = (selected) => SR_OPTIONS
            .map(([v, label]) => `<option value="${v}" ${selected === v ? 'selected' : ''}>${label}</option>`).join('');

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
                <input type="hidden" name="events[${i}][scheduled_at]" data-field="scheduled_at" value="${esc(ev.scheduled_at)}">
                <input type="text" value="${esc(datePart || '')}"
                       data-flatpickr="date" data-min-today="true"
                       class="form-control form-control-sm" data-field="date_part" required>
            </td>
            <td>
                <input type="text" value="${esc(timePart || '')}"
                       data-flatpickr="time"
                       class="form-control form-control-sm" data-field="time_part" required>
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
            <td>
                ${timeMultiplierSelect(i, 'practice_time_multiplier', ev.practice_time_multiplier)}
            </td>
            <td>
                ${timeMultiplierSelect(i, 'qualifying_time_multiplier', ev.qualifying_time_multiplier)}
            </td>
            <td>
                ${timeMultiplierSelect(i, 'race_time_multiplier', ev.race_time_multiplier)}
            </td>
            <td>
                <select name="events[${i}][min_rating]" class="form-select form-select-sm" data-field="min_rating" style="font-size:.72rem;padding:.25rem .3rem">
                    ${ratingOptions(ev.min_rating || '')}
                </select>
            </td>
            <td>
                <select name="events[${i}][sr_requirement]" class="form-select form-select-sm" data-field="sr_requirement" style="font-size:.72rem;padding:.25rem .3rem">
                    ${srOptions(ev.sr_requirement || '')}
                </select>
            </td>
            <td>
                <select name="events[${i}][max_rating]" class="form-select form-select-sm" data-field="max_rating" style="font-size:.72rem;padding:.25rem .3rem">
                    ${ratingOptions(ev.max_rating || '')}
                </select>
            </td>
            <td>
                <input type="hidden" name="events[${i}][car_class]" data-field="car_class" value="${esc(ev.car_class || '')}">
                <span style="font-size:.72rem;white-space:nowrap">${carClassDisplay}</span>
            </td>
            <td class="pe-4">
                <button type="button" data-remove
                        class="btn btn-sm d-flex align-items-center justify-content-center"
                        style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;width:28px;height:28px;padding:0;font-size:.85rem">
                    ✕
                </button>
            </td>
        `;

        // These fields (imported from a CSV column, or blank) have no editable cell of
        // their own in this table — it's already wide — so they ride along as hidden
        // inputs in the last cell, preserved on submit exactly as imported. Edit them
        // by fixing the CSV and re-uploading, not in this preview.
        const lastCell = tr.querySelector('td:last-child');
        ['weather_randomness', 'has_practice_server', 'description'].forEach(field => {
            const hidden = document.createElement('input');
            hidden.type  = 'hidden';
            hidden.name  = `events[${i}][${field}]`;
            hidden.value = ev[field] ?? '';
            lastCell.appendChild(hidden);
        });

        // Multiclass (2-3 car classes, e.g. from car_class_2/car_class_3 in a CSV) — same
        // shape RaceController::syncRaceClasses() expects, carried as one JSON blob since
        // it's a list, not a scalar field.
        const classesHidden = document.createElement('input');
        classesHidden.type  = 'hidden';
        classesHidden.name  = `events[${i}][classes_json]`;
        classesHidden.value = JSON.stringify(classes);
        lastCell.appendChild(classesHidden);

        const titleHidden      = tr.querySelector('[data-field="title"]');
        const maxDriversHidden = tr.querySelector('[data-field="max_drivers"]');
        const trackInput       = tr.querySelector('[data-field="track"]');
        const scheduledHidden  = tr.querySelector('[data-field="scheduled_at"]');
        const dateInput        = tr.querySelector('[data-field="date_part"]');
        const timeInput2       = tr.querySelector('[data-field="time_part"]');
        const formatInput      = tr.querySelector('[data-field="event_format_id"]');
        const serverInput      = tr.querySelector('[data-field="ftp_server_id"]');
        const weatherInput     = tr.querySelector('[data-field="weather"]');
        const timeInput        = tr.querySelector('[data-field="time_of_day"]');
        const ambientTempInput = tr.querySelector('[data-field="ambient_temp"]');
        const practiceMultInput   = tr.querySelector('[data-field="practice_time_multiplier"]');
        const qualifyingMultInput = tr.querySelector('[data-field="qualifying_time_multiplier"]');
        const raceMultInput       = tr.querySelector('[data-field="race_time_multiplier"]');
        const minRatingInput      = tr.querySelector('[data-field="min_rating"]');
        const srRequirementInput  = tr.querySelector('[data-field="sr_requirement"]');
        const maxRatingInput      = tr.querySelector('[data-field="max_rating"]');

        trackInput.addEventListener('input', () => {
            events[i].track = trackInput.value;
            events[i].title = trackInput.value;
            titleHidden.value = trackInput.value;
            maxDriversHidden.value = maxDriversForTrack(trackInput.value);
        });
        function recombineDateTime() {
            events[i].scheduled_at = dateInput.value && timeInput2.value ? `${dateInput.value}T${timeInput2.value}` : '';
            scheduledHidden.value = events[i].scheduled_at;
        }
        dateInput.addEventListener('change', () => {
            recombineDateTime();
            autoFillRow(events[i]);
            render();
        });
        timeInput2.addEventListener('change', () => {
            recombineDateTime();
            autoFillRow(events[i]);
            render();
        });
        formatInput.addEventListener('change', () => {
            events[i].event_format_id = formatInput.value;
            autoFillRow(events[i]);
            render();
        });
        serverInput.addEventListener('change', () => { events[i].ftp_server_id = serverInput.value; });
        weatherInput.addEventListener('change', () => { events[i].weather = weatherInput.value; });
        timeInput.addEventListener('change', () => { events[i].time_of_day = timeInput.value; });
        ambientTempInput.addEventListener('input', () => { events[i].ambient_temp = ambientTempInput.value; });
        practiceMultInput.addEventListener('change', () => { events[i].practice_time_multiplier = practiceMultInput.value; });
        qualifyingMultInput.addEventListener('change', () => { events[i].qualifying_time_multiplier = qualifyingMultInput.value; });
        raceMultInput.addEventListener('change', () => { events[i].race_time_multiplier = raceMultInput.value; });
        minRatingInput.addEventListener('change', () => { events[i].min_rating = minRatingInput.value; });
        srRequirementInput.addEventListener('change', () => { events[i].sr_requirement = srRequirementInput.value; });
        maxRatingInput.addEventListener('change', () => { events[i].max_rating = maxRatingInput.value; });

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
            event_format_id: '', ftp_server_id: '',
            weather: '', time_of_day: '', ambient_temp: '',
            practice_time_multiplier: '1', qualifying_time_multiplier: '1', race_time_multiplier: '1',
            weather_randomness: '', has_practice_server: '', sr_requirement: '',
            min_rating: '', max_rating: '', car_class: '', description: '', classes: [],
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
    // Same column order as RaceController::CSV_COLUMNS (game excluded — that's the
    // page's shared selector, not a per-row column) — so a downloaded-then-re-uploaded
    // batch round-trips losslessly.
    function downloadCsv() {
        if (!events.length) return;

        const header = [
            'format', 'track', 'weather', 'date', 'time', 'time_of_day',
            'ambient_temp', 'practice_time_multiplier', 'qualifying_time_multiplier', 'race_time_multiplier',
            'weather_randomness', 'has_practice_server', 'server',
            'sr_requirement', 'min_rating', 'max_rating', 'car_class', 'car_class_2', 'car_class_3', 'description',
        ];
        const lines = [header.join(',')];

        events.forEach(ev => {
            const [date, time] = (ev.scheduled_at || '').split('T');
            const format = (window.__ieFormats || []).find(f => f.value === String(ev.event_format_id || ''));
            const server = (window.__ieServers || []).find(s => s.value === String(ev.ftp_server_id || ''));
            const classes = ev.classes || [];

            lines.push([
                format?.label || '', ev.track || '', ev.weather || '', date || '', time || '', ev.time_of_day || '',
                ev.ambient_temp ?? '', ev.practice_time_multiplier || '1', ev.qualifying_time_multiplier || '1', ev.race_time_multiplier || '1',
                ev.weather_randomness || '', ev.has_practice_server === '1' ? 'on' : (ev.has_practice_server === '0' ? 'off' : ''),
                server ? (server.number ?? server.label) : '',
                ev.sr_requirement || '', ev.min_rating || '', ev.max_rating || '',
                classes[0]?.car_class || ev.car_class || '', classes[1]?.car_class || '', classes[2]?.car_class || '',
                ev.description || '',
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
