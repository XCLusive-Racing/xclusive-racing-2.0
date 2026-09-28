// Event times in the viewer's own timezone. The server renders every <x-local-time>
// in UK time (the fallback without JS); this rewrites each one into the timezone
// from the viewer's profile (<meta name="xcl-timezone">), or else the browser's own.

function validTimeZone(tz) {
    if (!tz) return null;
    try {
        new Intl.DateTimeFormat('en-US', { timeZone: tz });
        return tz;
    } catch {
        return null;
    }
}

export function viewerTimeZone() {
    const fromProfile = document.querySelector('meta[name="xcl-timezone"]')?.content;

    return validTimeZone(fromProfile)
        || validTimeZone(Intl.DateTimeFormat().resolvedOptions().timeZone)
        || 'Europe/London';
}

// "CEST", "EDT", "AEST" where the browser knows a name for it, else "GMT+2".
function zoneAbbreviation(date, timeZone) {
    const name = locale => new Intl.DateTimeFormat(locale, { timeZone, timeZoneName: 'short' })
        .formatToParts(date).find(p => p.type === 'timeZoneName')?.value ?? '';
    const names = ['en-US', 'en-GB', 'en-AU'].map(name);
    return names.find(n => n && !n.startsWith('GMT') && !n.startsWith('UTC')) ?? names[0];
}

// 24-hour by default; 12-hour (AM/PM) when the viewer picked that in their profile.
function uses12HourClock() {
    return document.querySelector('meta[name="xcl-clock"]')?.content === '12h';
}

function dateParts(date, timeZone, clock12) {
    const parts = Object.fromEntries(
        new Intl.DateTimeFormat('en-US', {
            timeZone, weekday: 'long', year: 'numeric', month: 'short', day: '2-digit',
            hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
        }).formatToParts(date).map(p => [p.type, p.value])
    );
    const hour = Number(parts.hour) % 24;

    return {
        weekdayLong:  parts.weekday,
        weekdayShort: parts.weekday.slice(0, 3),
        monthShort:   parts.month,
        day2:         parts.day,
        year:         parts.year,
        // "20:00", or "8:00 PM" on the 12-hour clock.
        time:         clock12
            ? `${hour % 12 || 12}:${parts.minute} ${hour < 12 ? 'AM' : 'PM'}`
            : `${String(hour).padStart(2, '0')}:${parts.minute}`,
        tz:           zoneAbbreviation(date, timeZone),
    };
}

// Keep in sync with the PHP fallback formats in components/local-time.blade.php.
const FORMATS = {
    'weekday':         p => p.weekdayLong,
    'date-short':      p => `${p.weekdayShort}, ${p.monthShort} ${p.day2}`,
    'full':            p => `${p.weekdayShort} ${p.day2} ${p.monthShort} ${p.year} · ${p.time} ${p.tz}`,
    'sidebar':         p => `${p.weekdayShort}, ${p.monthShort} ${p.day2}, ${p.time} ${p.tz}`,
    'date-time':       p => `${p.day2} ${p.monthShort} ${p.year} · ${p.time} ${p.tz}`,
    'date-time-comma': p => `${p.day2} ${p.monthShort} ${p.year}, ${p.time} ${p.tz}`,
    'dm-time':         p => `${p.day2} ${p.monthShort} · ${p.time} ${p.tz}`,
    'time':            p => p.time,
    'time-tz':         p => `${p.time} ${p.tz}`,
    'day':             p => p.day2,
    'month-year':      p => `${p.monthShort} ${p.year}`,
};

export function initLocalTimes(root = document) {
    const timeZone = viewerTimeZone();
    const clock12  = uses12HourClock();

    root.querySelectorAll('[data-local-time]').forEach(el => {
        const format = FORMATS[el.dataset.localTime];
        const date   = new Date(el.getAttribute('datetime'));
        if (!format || isNaN(date)) return;

        const text = format(dateParts(date, timeZone, clock12));
        el.textContent = el.hasAttribute('data-local-upper') ? text.toUpperCase() : text;
        el.title = timeZone.replace(/_/g, ' ');
    });

    // "Times shown in Europe/Amsterdam" hints, and the profile form's Automatic option.
    root.querySelectorAll('[data-viewer-tz]').forEach(el => {
        el.textContent = timeZone.replace(/_/g, ' ');
    });
    const browserTz = validTimeZone(Intl.DateTimeFormat().resolvedOptions().timeZone);
    root.querySelectorAll('[data-tz-auto-option]').forEach(el => {
        if (browserTz) el.textContent = `Automatic (detected: ${browserTz.replace(/_/g, ' ')})`;
    });
}
