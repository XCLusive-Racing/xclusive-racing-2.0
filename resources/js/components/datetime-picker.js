import flatpickr from 'flatpickr';

export function initDateTimePickers() {
    document.querySelectorAll('[data-flatpickr]').forEach(el => {
        if (el._flatpickr) return;

        const minuteIncrement = parseInt(el.dataset.minuteIncrement, 10) || 60;
        const hourOnly        = minuteIncrement >= 60;

        flatpickr(el, {
            enableTime: true,
            time_24hr: true,
            allowInput: true,
            minuteIncrement,
            dateFormat: 'Y-m-d\\TH:i',
            altInput: true,
            altFormat: 'D, d M Y \\a\\t H:i',
            minDate: el.dataset.minToday === 'true' ? 'today' : undefined,
            onReady: (selectedDates, dateStr, instance) => {
                if (!hourOnly) return;
                // Minutes are locked to :00 in this mode — hide the minute spinner
                // and its ":" separator entirely instead of showing a dead "00".
                instance.minuteElement?.closest('.numInputWrapper')?.classList.add('d-none');
                instance.calendarContainer.querySelector('.flatpickr-time-separator')?.classList.add('d-none');
            },
        });
    });
}