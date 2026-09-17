import flatpickr from 'flatpickr';

export function initDateTimePickers() {
    document.querySelectorAll('[data-flatpickr]').forEach(el => {
        if (el._flatpickr) return;

        // data-flatpickr="date"/"time" split a combined datetime picker into two narrow
        // columns (e.g. the Import/Export preview table) — bare data-flatpickr keeps the
        // original combined behavior below, unchanged for every other page using this.
        if (el.dataset.flatpickr === 'date') {
            flatpickr(el, {
                allowInput: true,
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'D d M',
                minDate: el.dataset.minToday === 'true' ? 'today' : undefined,
                locale: { firstDayOfWeek: 1 },
            });
            return;
        }

        if (el.dataset.flatpickr === 'time') {
            flatpickr(el, {
                enableTime: true,
                noCalendar: true,
                time_24hr: true,
                allowInput: true,
                minuteIncrement: parseInt(el.dataset.minuteIncrement, 10) || 60,
                dateFormat: 'H:i',
            });
            return;
        }

        const minuteIncrement = parseInt(el.dataset.minuteIncrement, 10) || 60;
        const hourOnly        = minuteIncrement >= 60;

        flatpickr(el, {
            enableTime: true,
            time_24hr: true,
            allowInput: true,
            minuteIncrement,
            dateFormat: 'Y-m-d\\TH:i',
            altInput: true,
            altFormat: el.dataset.compact !== undefined ? 'd M · H:i' : 'D, d M Y \\a\\t H:i',
            minDate: el.dataset.minToday === 'true' ? 'today' : undefined,
            locale: { firstDayOfWeek: 1 }, // Monday, not flatpickr's US-default Sunday
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