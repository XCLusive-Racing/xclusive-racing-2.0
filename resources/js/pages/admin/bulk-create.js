import { initDateTimePickers } from '../../components/datetime-picker.js';

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
    const defaultCarClassEl    = wrap.querySelector('select[name="car_class"]');

    function getDefaultCarClass()   { return defaultCarClassEl?.value || ''; }

    const CAR_CLASS_OPTIONS = ['GT3', 'GT4', 'GT2', 'GTC', 'TCX'];

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

    function maxDriversForTrack(track) {
        return window.__ceTracks?.[track]?.max ?? '';
    }

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

    const WEATHER_OPTIONS = ['dry', 'wet', 'mixed', 'random'];

    function weatherLabel(v) {
        return v.charAt(0).toUpperCase() + v.slice(1);
    }

    // Row rendering — title hidden, mirrors track
    function renderRow(i) {
        const ev = events[i];
        const carClassOptions = CAR_CLASS_OPTIONS.map(v =>
            `<option value="${v}" ${ev.car_class === v ? 'selected' : ''}>${v}</option>`).join('');
        const weatherOptions = WEATHER_OPTIONS.map(v =>
            `<option value="${v}" ${ev.weather === v ? 'selected' : ''}>${weatherLabel(v)}</option>`).join('');
        const rainNeeded = ev.weather === 'wet' || ev.weather === 'mixed';

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
                       data-flatpickr data-min-today="true" data-compact
                       class="form-control form-control-sm" data-field="scheduled_at" required>
            </td>
            <td>
                <select name="events[${i}][car_class]" class="form-select form-select-sm" data-field="car_class">
                    ${carClassOptions}
                </select>
            </td>
            <td>
                <select name="events[${i}][weather]" class="form-select form-select-sm mb-1" data-field="weather">
                    ${weatherOptions}
                </select>
                <input type="number" name="events[${i}][rain_level]" data-field="rain_level"
                       min="0" max="1" step="0.1" value="${esc(ev.rain_level ?? '')}"
                       placeholder="0.0–1.0"
                       class="form-control form-control-sm"
                       style="display:${rainNeeded ? '' : 'none'};font-size:.75rem">
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
        const carClassInput    = tr.querySelector('[data-field="car_class"]');
        const weatherInput     = tr.querySelector('[data-field="weather"]');
        const rainInput        = tr.querySelector('[data-field="rain_level"]');

        // Track drives title and max-drivers automatically, same as single mode
        trackInput.addEventListener('input', () => {
            events[i].track = trackInput.value;
            events[i].title = trackInput.value;
            titleHidden.value = trackInput.value;
            maxDriversHidden.value = maxDriversForTrack(trackInput.value);
        });
        // flatpickr's own minuteIncrement:60/hour-only mode keeps this whole-hour already
        dateInput.addEventListener('change', () => { events[i].scheduled_at = dateInput.value; });
        carClassInput.addEventListener('change', () => { events[i].car_class = carClassInput.value; });
        weatherInput.addEventListener('change', () => {
            events[i].weather = weatherInput.value;
            const needsRain = weatherInput.value === 'wet' || weatherInput.value === 'mixed';
            rainInput.style.display = needsRain ? '' : 'none';
            if (!needsRain) {
                events[i].rain_level = ''; rainInput.value = '';
            } else if (!rainInput.value) {
                events[i].rain_level = '0.3'; rainInput.value = '0.3';
            }
        });
        rainInput.addEventListener('input', () => { events[i].rain_level = rainInput.value; });

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
        initDateTimePickers();
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

        // Iterate day-by-day from start date for nWeeks * 7 days,
        // picking each day whose weekday matches a selected day (0=Mon…6=Sun).
        const seed = new Date(startDateInput.value + 'T12:00');

        events = [];
        for (let day = 0; day < nWeeks * 7; day++) {
            const d = new Date(seed);
            d.setDate(d.getDate() + day);
            d.setHours(th, 0, 0, 0);
            // Convert JS getDay() (0=Sun…6=Sat) to our offset (0=Mon…6=Sun)
            const jsDay  = d.getDay();
            const ourDay = jsDay === 0 ? 6 : jsDay - 1;
            if (!checkedDays.includes(ourDay)) continue;
            events.push({
                title: defTrack, track: defTrack, scheduled_at: formatDate(d),
                car_class: getDefaultCarClass() || 'GT3',
                weather: 'dry', rain_level: '',
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
            car_class: getDefaultCarClass(),
            weather: 'dry', rain_level: '',
        });
        render();
    }

    generateBtn?.addEventListener('click', generate);
    addRowBtn?.addEventListener('click', addRow);

    ['input', 'change'].forEach(ev => startDateInput?.addEventListener(ev, updateCounts));
    ['input', 'change'].forEach(ev => weekCountInput?.addEventListener(ev, updateCounts));

    updateCounts();
}
