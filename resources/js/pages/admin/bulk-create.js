export function initBulkCreate(wrap) {
    if (!wrap) return;

    const startDateInput  = wrap.querySelector('[data-bulk-start-date]');
    const startTimeInput  = wrap.querySelector('[data-bulk-start-time]');
    const weekCountInput  = wrap.querySelector('[data-bulk-week-count]');
    const dayCheckboxes   = wrap.querySelectorAll('[data-bulk-day]');
    const generateBtn     = wrap.querySelector('[data-bulk-generate]');
    const noDateHint      = wrap.querySelector('[data-bulk-no-date]');
    const eventsSection   = wrap.querySelector('[data-bulk-events-section]');
    const countDisplays   = wrap.querySelectorAll('[data-bulk-count-display]');
    const addRowBtn       = wrap.querySelector('[data-bulk-add-row]');
    const tbody           = wrap.querySelector('[data-bulk-tbody]');
    const defaultWeatherEl = wrap.querySelector('select[name="weather"]');
    const defaultTimeEl    = wrap.querySelector('select[name="time_of_day"]');

    const WEATHER_OPTIONS = [
        ['', '— Not set —'], ['dry', 'Dry'], ['wet', 'Wet'], ['mixed', 'Mixed'], ['random', 'Random'],
    ];
    const TIME_OPTIONS = [
        ['', '— Not set —'], ['day', 'Day'], ['dusk', 'Dusk'], ['night', 'Night'], ['dynamic', 'Dynamic'],
    ];

    function getDefaultWeather() { return defaultWeatherEl?.value || ''; }
    function getDefaultTime()    { return defaultTimeEl?.value || ''; }

    let events = [];

    function getDefaultTrack() {
        const sel = document.getElementById('ce-track-select');
        const txt = document.getElementById('ce-track-text');
        if (sel && sel.style.display !== 'none' && sel.value) return sel.value;
        if (txt && txt.style.display !== 'none' && txt.value) return txt.value;
        return '';
    }

    function esc(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function pad(n) { return String(n).padStart(2, '0'); }

    function formatDate(d) {
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate())
             + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    function hasDate()  { return !!startDateInput?.value; }
    function hasDays()  { return Array.from(dayCheckboxes).some(cb => cb.checked); }
    function canGenerate() { return hasDate() && hasDays(); }

    function updateCounts() {
        countDisplays.forEach(el => { el.textContent = events.length; });
        const ok = canGenerate();
        if (generateBtn) generateBtn.disabled = !ok;
        if (noDateHint) {
            if (ok) {
                noDateHint.style.display = 'none';
            } else {
                noDateHint.style.display = '';
                noDateHint.textContent   = !hasDate()
                    ? 'Pick a start date first'
                    : 'Select at least one race day';
            }
        }
    }

    // Day checkbox pill styling
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

    // Row rendering — title hidden, mirrors track
    function renderRow(i) {
        const ev = events[i];
        const weatherOptions = WEATHER_OPTIONS.map(([v, label]) =>
            `<option value="${v}" ${ev.weather === v ? 'selected' : ''}>${label}</option>`).join('');
        const timeOptions = TIME_OPTIONS.map(([v, label]) =>
            `<option value="${v}" ${ev.time_of_day === v ? 'selected' : ''}>${label}</option>`).join('');

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="ps-4 text-secondary fw-bold" style="font-size:.8rem">${i + 1}</td>
            <td>
                <input type="hidden" name="events[${i}][title]" data-field="title" value="${esc(ev.title)}">
                <input type="text" name="events[${i}][track]" value="${esc(ev.track)}"
                       class="form-control form-control-sm" data-field="track" required>
            </td>
            <td>
                <input type="datetime-local" name="events[${i}][scheduled_at]" value="${esc(ev.scheduled_at)}"
                       step="3600" class="form-control form-control-sm" data-field="scheduled_at" required>
            </td>
            <td>
                <select name="events[${i}][weather]" class="form-select form-select-sm" data-field="weather">
                    ${weatherOptions}
                </select>
            </td>
            <td>
                <select name="events[${i}][time_of_day]" class="form-select form-select-sm" data-field="time_of_day">
                    ${timeOptions}
                </select>
            </td>
            <td class="pe-4">
                <button type="button" data-remove
                        class="btn btn-sm d-flex align-items-center justify-content-center"
                        style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;width:28px;height:28px;padding:0;font-size:.85rem">
                    ✕
                </button>
            </td>
        `;

        const titleHidden  = tr.querySelector('[data-field="title"]');
        const trackInput   = tr.querySelector('[data-field="track"]');
        const dateInput    = tr.querySelector('[data-field="scheduled_at"]');
        const weatherInput = tr.querySelector('[data-field="weather"]');
        const timeInput    = tr.querySelector('[data-field="time_of_day"]');

        // Track drives title automatically
        trackInput.addEventListener('input', () => {
            events[i].track = trackInput.value;
            events[i].title = trackInput.value;
            titleHidden.value = trackInput.value;
        });
        dateInput.addEventListener('input', () => {
            // Keep whole-hour only, even if the browser lets a stray minute value through
            const d = new Date(dateInput.value);
            if (!isNaN(d) && d.getMinutes() !== 0) {
                d.setMinutes(0, 0, 0);
                dateInput.value = formatDate(d);
            }
            events[i].scheduled_at = dateInput.value;
        });
        weatherInput.addEventListener('change', () => { events[i].weather = weatherInput.value; });
        timeInput.addEventListener('change', () => { events[i].time_of_day = timeInput.value; });

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

    // Generate: find each selected day on or after start date, repeat for N weeks
    function generate() {
        if (!canGenerate()) return;

        const checkedDays = Array.from(dayCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => parseInt(cb.dataset.bulkDay)) // 0=Mon … 6=Sun
            .sort((a, b) => a - b);

        const nWeeks  = Math.min(Math.max(parseInt(weekCountInput?.value) || 1, 1), 52);
        const time    = startTimeInput?.value || '20:00';
        const [th]    = time.split(':').map(Number); // whole-hour only, minutes always :00
        const defTrack = getDefaultTrack();

        // Find Monday of the week containing start date
        const seed   = new Date(startDateInput.value + 'T12:00');
        const jsDay  = seed.getDay(); // 0=Sun…6=Sat
        const toMon  = jsDay === 0 ? -6 : 1 - jsDay;
        const monday = new Date(seed);
        monday.setDate(monday.getDate() + toMon);

        const defWeather = getDefaultWeather();
        const defTime    = getDefaultTime();

        events = [];
        for (let w = 0; w < nWeeks; w++) {
            checkedDays.forEach(dayOffset => {
                const d = new Date(monday);
                d.setDate(d.getDate() + w * 7 + dayOffset);
                d.setHours(th, 0, 0, 0);
                // Skip dates before start date
                if (d < seed) return;
                events.push({
                    title: defTrack, track: defTrack, scheduled_at: formatDate(d),
                    weather: defWeather, time_of_day: defTime,
                });
            });
        }

        if (eventsSection) eventsSection.style.display = '';
        render();
    }

    function addRow() {
        const last = events[events.length - 1];
        let nextDate = '';
        if (last?.scheduled_at) {
            const d = new Date(last.scheduled_at);
            d.setDate(d.getDate() + 7);
            nextDate = formatDate(d);
        }
        const defTrack = getDefaultTrack();
        events.push({
            title: defTrack, track: defTrack, scheduled_at: nextDate,
            weather: getDefaultWeather(), time_of_day: getDefaultTime(),
        });
        render();
    }

    generateBtn?.addEventListener('click', generate);
    addRowBtn?.addEventListener('click', addRow);

    ['input', 'change'].forEach(ev => startDateInput?.addEventListener(ev, updateCounts));
    ['input', 'change'].forEach(ev => weekCountInput?.addEventListener(ev, updateCounts));

    updateCounts();
}
